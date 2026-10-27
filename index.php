<?php
require_once __DIR__ . '/config.php';
$chain = new Blockchain(db(), DIFFICULTY);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'register') {
            $code = strtoupper(trim($_POST['product_code'] ?? ''));
            $name = trim($_POST['name'] ?? '');
            $maker = trim($_POST['manufacturer'] ?? '');
            $loc = trim($_POST['location'] ?? '');
            if (!preg_match('/^[A-Z0-9\-]{3,30}$/', $code)) throw new RuntimeException('Product code must be 3-30 letters, numbers or hyphens.');
            if ($name === '' || $maker === '' || $loc === '') throw new RuntimeException('Fill in all required fields.');
            $b = $chain->registerProduct($code, $name, $maker, $loc, trim($_POST['details'] ?? ''));
            flash('ok', "Product $code registered in block #{$b['block_index']}.");
        } elseif ($action === 'event') {
            $code = strtoupper(trim($_POST['product_code'] ?? ''));
            $event = $_POST['event_type'] ?? '';
            $loc = trim($_POST['location'] ?? '');
            $handler = trim($_POST['handler'] ?? '');
            if (!isset(EVENT_TYPES[$event]) || $event === 'MANUFACTURED') throw new RuntimeException('Choose a valid event.');
            if ($loc === '' || $handler === '') throw new RuntimeException('Location and handler are required.');
            $b = $chain->addEvent($code, $event, $loc, $handler, trim($_POST['details'] ?? ''));
            flash('ok', EVENT_TYPES[$event] . " recorded in block #{$b['block_index']}.");
        }
    } catch (PDOException $ex) {
        flash('bad', $ex->getCode() === '23000' ? 'That product code already exists.' : 'Database error: ' . $ex->getMessage());
    } catch (Throwable $t) {
        flash('bad', $t->getMessage());
    }
    header('Location: index.php');
    exit;
}

$pdo = db();
$stats = [
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'blocks'   => (int)$pdo->query('SELECT COUNT(*) FROM blocks')->fetchColumn(),
    'delivered'=> (int)$pdo->query("SELECT COUNT(DISTINCT product_code) FROM blocks WHERE event_type='DELIVERED'")->fetchColumn(),
];
$check = $chain->validate();
$products = $pdo->query(
    "SELECT p.*, (SELECT event_type FROM blocks b WHERE b.product_code = p.product_code ORDER BY block_index DESC LIMIT 1) AS status
     FROM products p ORDER BY p.id DESC LIMIT 15"
)->fetchAll();
$codes = $pdo->query('SELECT product_code, name FROM products ORDER BY product_code')->fetchAll();

$title = 'Dashboard';
require __DIR__ . '/header.php';
?>
<section class="stats">
  <div><b><?= $stats['products'] ?></b><span>Products tracked</span></div>
  <div><b><?= $stats['blocks'] ?></b><span>Blocks in chain</span></div>
  <div><b><?= $stats['delivered'] ?></b><span>Delivered</span></div>
  <div class="<?= $check['valid'] ? 'good' : 'bad' ?>">
    <b><?= $check['valid'] ? 'Intact' : 'Broken' ?></b>
    <span><a href="chain.php">Ledger status</a></span>
  </div>
</section>

<section class="grid2">
  <form method="post" class="panel">
    <h2>Register a new product</h2>
    <input type="hidden" name="action" value="register">
    <label>Product code <input name="product_code" placeholder="e.g. MED-1001" required maxlength="30"></label>
    <label>Product name <input name="name" required maxlength="120"></label>
    <label>Manufacturer <input name="manufacturer" required maxlength="120"></label>
    <label>Factory location <input name="location" required maxlength="150"></label>
    <label>Notes (batch, expiry&hellip;) <textarea name="details" rows="2"></textarea></label>
    <button>Register product</button>
  </form>

  <form method="post" class="panel">
    <h2>Record a tracking event</h2>
    <input type="hidden" name="action" value="event">
    <label>Product
      <select name="product_code" required>
        <option value="">Select a product</option>
        <?php foreach ($codes as $c): ?>
          <option value="<?= e($c['product_code']) ?>"><?= e($c['product_code']) ?> &ndash; <?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Event
      <select name="event_type" required>
        <?php foreach (EVENT_TYPES as $k => $v): if ($k === 'MANUFACTURED') continue; ?>
          <option value="<?= e($k) ?>"><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Current location <input name="location" required maxlength="150"></label>
    <label>Handled by <input name="handler" required maxlength="120"></label>
    <label>Notes <textarea name="details" rows="2"></textarea></label>
    <button>Add to blockchain</button>
  </form>
</section>

<section class="panel">
  <h2>Latest products</h2>
  <?php if (!$products): ?>
    <p class="empty">No products yet. Register your first product above to create the genesis block.</p>
  <?php else: ?>
  <div class="scroll"><table>
    <tr><th>Code</th><th>Name</th><th>Manufacturer</th><th>Status</th><th></th></tr>
    <?php foreach ($products as $p): ?>
      <tr>
        <td class="mono"><?= e($p['product_code']) ?></td>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['manufacturer']) ?></td>
        <td><span class="tag s-<?= e(strtolower($p['status'])) ?>"><?= e(EVENT_TYPES[$p['status']] ?? $p['status']) ?></span></td>
        <td><a href="track.php?code=<?= urlencode($p['product_code']) ?>">View journey</a></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
