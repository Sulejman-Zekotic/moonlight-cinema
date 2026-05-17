<?php

$styles = $styles ?? [];
$scripts = $scripts ?? [];
$user = $user ?? null;
$auth = $auth ?? $app['auth'];
$repo = $repo ?? $app['repo'];
$title = $title ?? 'Moonlight Cinema';
?>
<!DOCTYPE html>
<html lang="bs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= e($title) ?> | <?= e($repo->appName()) ?></title>

  <link rel="icon" type="image/svg+xml" href="<?= e(asset_url('icons/favicon.svg')) ?>">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= e(asset_url('icons/favicon-96x96.png')) ?>">
  <link rel="icon" type="image/x-icon" href="<?= e(asset_url('icons/favicon.ico')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset_url('icons/apple-touch-icon.png')) ?>">
  <link rel="manifest" href="<?= e(asset_url('icons/site.webmanifest')) ?>">
  <meta name="theme-color" content="#0D2B3E">

  <?php foreach ($styles as $style): ?>
    <?php $stylePath = ltrim($style, '/'); ?>
    <?php $styleVersion = is_file(__DIR__ . '/../../../assets/' . $stylePath) ? filemtime(__DIR__ . '/../../../assets/' . $stylePath) : time(); ?>
    <link rel="stylesheet" href="<?= e(asset_url($stylePath) . '?v=' . $styleVersion) ?>">
  <?php endforeach; ?>

  <script>
    window.MC_BASE_URL = <?= json_encode(rtrim(url_for('/'), '/'), JSON_UNESCAPED_SLASHES) ?>;
    window.mcAjax = { ajaxurl: <?= json_encode(url_for('api'), JSON_UNESCAPED_SLASHES) ?> };
    window.mcMovieFilter = { ajaxurl: <?= json_encode(url_for('api'), JSON_UNESCAPED_SLASHES) ?> };
    window.mcAuth = { ajaxurl: <?= json_encode(url_for('api'), JSON_UNESCAPED_SLASHES) ?> };
    window.mcReviews = { ajaxurl: <?= json_encode(url_for('api'), JSON_UNESCAPED_SLASHES) ?> };
    window.mcAdmin = { ajaxurl: <?= json_encode(url_for('api'), JSON_UNESCAPED_SLASHES) ?> };
    window.mcAuto = { ajaxurl: <?= json_encode(url_for('api'), JSON_UNESCAPED_SLASHES) ?>, nonce: 'local' };
    window.mcAsset = function(path) { return window.MC_BASE_URL + '/assets/media/' + path.replace(/^\/+/, ''); };
  </script>
</head>
<body class="mc-app-shell">
  <?php render_partial('partials/header', ['auth' => $auth, 'user' => $user]); ?>

  <main>
    <?= $content ?>
  </main>

  <?php render_partial('partials/footer', ['auth' => $auth, 'user' => $user]); ?>

  <?php $siteScriptVersion = is_file(__DIR__ . '/../../../assets/js/site.js') ? filemtime(__DIR__ . '/../../../assets/js/site.js') : time(); ?>
  <script src="<?= e(asset_url('js/site.js') . '?v=' . $siteScriptVersion) ?>"></script>

  <?php foreach ($scripts as $script): ?>
    <?php $scriptPath = ltrim($script, '/'); ?>
    <?php $scriptVersion = is_file(__DIR__ . '/../../../assets/' . $scriptPath) ? filemtime(__DIR__ . '/../../../assets/' . $scriptPath) : time(); ?>
    <script src="<?= e(asset_url($scriptPath) . '?v=' . $scriptVersion) ?>"></script>
  <?php endforeach; ?>
</body>
</html>