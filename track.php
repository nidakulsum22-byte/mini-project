<?php
require_once __DIR__ . '/config.php';
$chain = new Blockchain(db(), DIFFICULTY);
$code = strtoupper(trim($_GET['code'] ?? ''));
$product = null; $history = []; $check = null;

if ($code !== '') {
    $s = db()->prepare('SELECT * FROM products WHERE product_code = ?');
    $s->execute([$code]);
    $product = $s->fetch();
    if ($product) {
        $history = $chain->getProductHistory($code);
        $check = $chain->validate();
    }
}
$title = 'Track a product';
require __DIR__ . '/header.php';
?>
<section class="panel">
  <h2>Track a product</h2>
  <form method="get" class="inline">
    <input name="code" value="<?= e($code) ?>" placeholder="Enter product code, e.g. MED-1001" required>
    <button>Track</button>
  </form>
</section>

<?php if ($code !== '' && !$product): ?>
  <div class="note bad">No product found with code <b><?= e($code) ?></b>. Check the code and try again.</div>
<?php elseif ($product): ?>
  <?php
    // A product is only trustworthy if none of ITS blocks failed verification.
    $bad = array_intersect_key($check['errors'], array_flip(array_column($history, 'block_index')));
  ?>
  <section class="panel">
    <div class="head-row">
      <div>
        <h2><?= e($product['name']) ?></h2>
        <p class="muted"><span class="mono"><?= e($product['product_code']) ?></span> &middot; made by <?= e($product['manufacturer']) ?></p>
      </div>
      <?php if (!$bad && $check['valid']): ?>
        <span class="badge good">Verified &ndash; history untampered</span>
      <?php elseif ($bad): ?>
        <span class="badge bad">Tampered &ndash; <?= count($bad) ?> block(s) failed</span>
      <?php else: ?>
        <span class="badge warn">Chain broken elsewhere &ndash; see ledger</span>
      <?php endif; ?>
    </div>

    <ol class="timeline">
      <?php foreach ($history as $b): $err = $bad[(int)$b['block_index']] ?? null; ?>
        <li class="<?= $err ? 'fail' : '' ?>">
          <div class="when"><?= e($b['timestamp']) ?></div>
          <div class="what">
            <span class="tag s-<?= e(strtolower($b['event_type'])) ?>"><?= e(EVENT_TYPES[$b['event_type']] ?? $b['event_type']) ?></span>
            <b><?= e($b['location']) ?></b> &mdash; <?= e($b['handler']) ?>
            <?php if ($b['details'] !== ''): ?><p class="muted"><?= e($b['details']) ?></p><?php endif; ?>
            <p class="mono small">Block #<?= (int)$b['block_index'] ?> &middot; hash <?= e(short_hash($b['hash'])) ?></p>
            <?php if ($err): ?><p class="small bad-text"><?= e($err) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
