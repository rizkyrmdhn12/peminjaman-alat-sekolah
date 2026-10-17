<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="print-page">
<?= $content ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
