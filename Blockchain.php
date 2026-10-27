<?php
/**
 * A tiny blockchain stored in MySQL.
 *  - Each product event (manufactured, shipped, delivered...) becomes a block.
 *  - Each block stores the hash of the previous block, so editing any old
 *    row breaks every block after it.
 *  - Proof-of-work: a block is only valid if its hash starts with N zeros.
 */
class Blockchain
{
    private PDO $pdo;
    private int $difficulty;

    public function __construct(PDO $pdo, int $difficulty = 3)
    {
        $this->pdo = $pdo;
        $this->difficulty = $difficulty;
    }

    /** SHA-256 over every field of the block (order fixed, types normalised). */
    public static function calculateHash(array $b): string
    {
        return hash('sha256', json_encode([
            (int)$b['block_index'],
            (string)$b['timestamp'],
            (string)$b['product_code'],
            (string)$b['event_type'],
            (string)$b['location'],
            (string)$b['handler'],
            (string)$b['details'],
            (string)$b['previous_hash'],
            (int)$b['nonce'],
        ], JSON_UNESCAPED_UNICODE));
    }

    /** Proof of work: keep changing nonce until the hash has leading zeros. */
    private function mine(array $block): array
    {
        $prefix = str_repeat('0', $this->difficulty);
        $block['nonce'] = 0;
        do {
            $block['nonce']++;
            $hash = self::calculateHash($block);
        } while (strncmp($hash, $prefix, $this->difficulty) !== 0);
        $block['hash'] = $hash;
        return $block;
    }

    /** Append a block. Caller must already be inside a transaction. */
    private function appendBlock(string $code, string $event, string $location, string $handler, string $details): array
    {
        // Lock the last row so two users can't append at the same time.
        $last = $this->pdo->query('SELECT block_index, hash FROM blocks ORDER BY block_index DESC LIMIT 1 FOR UPDATE')->fetch();

        $block = [
            'block_index'   => $last ? (int)$last['block_index'] + 1 : 0,
            'timestamp'     => date('Y-m-d H:i:s'),
            'product_code'  => $code,
            'event_type'    => $event,
            'location'      => $location,
            'handler'       => $handler,
            'details'       => $details,
            'previous_hash' => $last ? $last['hash'] : str_repeat('0', 64), // genesis block
        ];
        $block = $this->mine($block);

        $stmt = $this->pdo->prepare(
            'INSERT INTO blocks (block_index, product_code, event_type, location, handler, details, timestamp, previous_hash, hash, nonce)
             VALUES (:block_index, :product_code, :event_type, :location, :handler, :details, :timestamp, :previous_hash, :hash, :nonce)'
        );
        $stmt->execute($block);
        return $block;
    }

    /** Register a new product and write its first block (MANUFACTURED). */
    public function registerProduct(string $code, string $name, string $maker, string $location, string $details): array
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO products (product_code, name, manufacturer, created_at) VALUES (?,?,?,NOW())')
                      ->execute([$code, $name, $maker]);
            $block = $this->appendBlock($code, 'MANUFACTURED', $location, $maker, $details);
            $this->pdo->commit();
            return $block;
        } catch (Throwable $t) {
            $this->pdo->rollBack();
            throw $t;
        }
    }

    /** Add a tracking event for an existing product. */
    public function addEvent(string $code, string $event, string $location, string $handler, string $details): array
    {
        $this->pdo->beginTransaction();
        try {
            $exists = $this->pdo->prepare('SELECT 1 FROM products WHERE product_code = ?');
            $exists->execute([$code]);
            if (!$exists->fetchColumn()) {
                throw new RuntimeException('Product code not found.');
            }
            $block = $this->appendBlock($code, $event, $location, $handler, $details);
            $this->pdo->commit();
            return $block;
        } catch (Throwable $t) {
            $this->pdo->rollBack();
            throw $t;
        }
    }

    public function getProductHistory(string $code): array
    {
        $s = $this->pdo->prepare('SELECT * FROM blocks WHERE product_code = ? ORDER BY block_index ASC');
        $s->execute([$code]);
        return $s->fetchAll();
    }

    /**
     * Re-check the whole chain.
     * Returns ['valid' => bool, 'checked' => int, 'errors' => [block_index => message]]
     */
    public function validate(): array
    {
        $blocks = $this->pdo->query('SELECT * FROM blocks ORDER BY block_index ASC')->fetchAll();
        $errors = [];
        $prefix = str_repeat('0', $this->difficulty);
        $prev = null;

        foreach ($blocks as $i => $b) {
            $idx = (int)$b['block_index'];

            if ($i === 0 && $b['previous_hash'] !== str_repeat('0', 64)) {
                $errors[$idx] = 'Genesis block has a wrong previous hash.';
            }
            if ($prev !== null) {
                if ($idx !== (int)$prev['block_index'] + 1) {
                    $errors[$idx] = 'Block number is out of sequence (a block was removed?).';
                } elseif ($b['previous_hash'] !== $prev['hash']) {
                    $errors[$idx] = 'Link broken: previous hash does not match the block before it.';
                }
            }
            if (self::calculateHash($b) !== $b['hash']) {
                $errors[$idx] = 'Data was modified: stored hash does not match block contents.';
            } elseif (strncmp($b['hash'], $prefix, $this->difficulty) !== 0) {
                $errors[$idx] = 'Proof of work is missing.';
            }
            $prev = $b;
        }
        return ['valid' => !$errors, 'checked' => count($blocks), 'errors' => $errors];
    }
}
