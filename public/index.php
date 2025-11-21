<?php
// /public/index.php
require __DIR__ . '/../app/db.php';

// Test query: list all destinations
$stmt = $pdo->query('SELECT code, city, country FROM destinations ORDER BY city');
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trip planner test</title>
</head>
<body>
    <h1>Destinations (test)</h1>

    <?php if (empty($destinations)): ?>
        <p>No destinations found.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($destinations as $d): ?>
                <li>
                    <?= htmlspecialchars($d['code']) ?> –
                    <?= htmlspecialchars($d['city']) ?>,
                    <?= htmlspecialchars($d['country']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>