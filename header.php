<?php require_once __DIR__ . '/config.php'; $current = basename($_SERVER['PHP_SELF']); $flash = take_flash(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'ChainTrack') ?> | ChainTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
  <div class="wrap bar">
    <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"></span>ChainTrack</a>
    <nav>
      <a href="index.php"  class="<?= $current === 'index.php' ? 'on' : '' ?>">Dashboard</a>
      <a href="track.php"  class="<?= $current === 'track.php' ? 'on' : '' ?>">Track a product</a>
      <a href="chain.php"  class="<?= $current === 'chain.php' ? 'on' : '' ?>">Blockchain ledger</a>
    </nav>
  </div>
</header>
<main class="wrap">
<?php if ($flash): ?>
  <div class="note <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>
