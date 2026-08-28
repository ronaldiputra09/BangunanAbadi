<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Accurate</title>
</head>
<body>
    <h2>Daftar Database dari Accurate</h2>

    <?php if (isset($databases['error'])): ?>
        <p>Error: <?= esc($databases['error']) ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($databases as $db): ?>
                <li><?= esc($db['name']) ?> - ID: <?= esc($db['id']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</body>
</html>
