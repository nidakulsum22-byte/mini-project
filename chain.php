<?php
require_once __DIR__ . '/config.php';
$pdo = db();
$chain = new Blockchain($pdo, DIFFICULTY);

// DEMO ONLY: edit a row directly in MySQL, the way an attacker (or careless admin) would.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tamper') {
    $idx = (int)($_POST['block_index'] ?? -1);
    $st = $pdo->prepare("UPDATE blocks SET location = 'TAMPERED LOCATION' WHERE block_index = ?");
    $st->execute([$idx]);
    flash($st->rowCount() ? 'warn' : 'bad', $st->rowCount() ? "Block #$idx was edited directly in the database. Now verify the chain." : 'Block not found.');
    header('Location: chain.php');
    exit;
}

$check = $chain->validate();
$blocks = $pdo->query('SELECT * FROM blocks ORDER BY block_index DESC')->fetchAll();
$title = 'Blockchain ledger';
require __DIR__ . '/header.php';
?>
<section class="panel">
  <div class="head-row">
    <div>
      <h2>Blockchain ledger</h2>
      <p class="muted"><?= $check['checked'] ?> block(s) re-verified just now: hash of every block recomputed, links and proof of work checked.</p>
    </div>
    <span class="badge <?= $check['valid'] ? 'good' : 'bad' ?>">
      <?= $check['valid'] ? 'Chain is valid' : count($check['errors']) . ' invalid block(s)' ?>
    </span>
  </div>

  <form method="post" class="inline demo" onsubmit="return confirm('This edits data directly in MySQL to demonstrate tampering. Continue?');">
    <input type="hidden" name="action" value="tamper">
    <label>Demo: simulate an attacker editing block #
      <input type="number" name="block_index" min="0" required style="width:6rem">
    </label>
    <button class="ghost">Tamper with block</button>
  </form>
  <p class="small muted">To undo the demo, re-import <span class="mono">database.sql</span> (the chain cannot be repaired &ndash; that is the point).</p>
</section>

<?php if (!$blocks): ?>
  <p class="empty">The chain is empty. Register a product on the dashboard to mine the genesis block.</p>
<?php endif; ?>

<?php foreach ($blocks as $b): $err = $check['errors'][(int)$b['block_index']] ?? null; ?>
  <article class="block <?= $err ? 'fail' : '' ?>">
    <header>
      <b>Block #<?= (int)$b['block_index'] ?><?= (int)$b['block_index'] === 0 ? ' (genesis)' : '' ?></b>
      <span class="tag s-<?= e(strtolower($b['event_type'])) ?>"><?= e(EVENT_TYPES[$b['event_type']] ?? $b['event_type']) ?></span>
      <span class="muted"><?= e($b['timestamp']) ?></span>
    </header>
    <dl>
      <dt>Product</dt><dd><?= e($b['product_code']) ?></dd>
      <dt>Location</dt><dd><?= e($b['location']) ?></dd>
      <dt>Handler</dt><dd><?= e($b['handler']) ?></dd>
      <?php if ($b['details'] !== ''): ?><dt>Notes</dt><dd><?= e($b['details']) ?></dd><?php endif; ?>
      <dt>Nonce</dt><dd class="mono"><?= (int)$b['nonce'] ?></dd>
      <dt>Previous hash</dt><dd class="mono hash"><?= e($b['previous_hash']) ?></dd>
      <dt>Hash</dt><dd class="mono hash"><?= e($b['hash']) ?></dd>
    </dl>
    <?php if ($err): ?><p class="bad-text"><b>Invalid:</b> <?= e($err) ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>
<?php require __DIR__ . '/footer.php'; ?>
