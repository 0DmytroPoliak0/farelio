<?php
// public/recheck_price.php
session_start();
require __DIR__ . '/../app/db.php';

$flight_id     = (int)($_GET['flight_id'] ?? 0);
$hotel_id      = (int)($_GET['hotel_id'] ?? 0);
$nights        = (int)($_GET['nights'] ?? 0);
$current_total = isset($_GET['current_total']) ? (float)$_GET['current_total'] : null;

$error = null;
$flight = null;
$hotel  = null;
$new_total = null;
$change_label = null;

if ($flight_id <= 0 || $hotel_id <= 0 || $nights <= 0) {
    $error = 'Missing or invalid trip data.';
} else {
    try {
        // Load latest flight + hotel from DB
        $stmtF = $pdo->prepare('SELECT * FROM flights WHERE id = :id');
        $stmtF->execute(['id' => $flight_id]);
        $flight = $stmtF->fetch();

        $stmtH = $pdo->prepare('SELECT * FROM hotels WHERE id = :id');
        $stmtH->execute(['id' => $hotel_id]);
        $hotel = $stmtH->fetch();

        if (!$flight || !$hotel) {
            $error = 'We could not find this flight or hotel anymore.';
        } else {
            // Base price from current DB values
            $base_total = $flight['price'] + $hotel['nightly_price'] * max(1, $nights);

            // Simulate volatility: -8% to +12%
            $delta_percent = random_int(-8, 12);
            $new_total = round($base_total * (1 + $delta_percent / 100), 2);

            if ($current_total !== null) {
                $diff = $new_total - $current_total;
                if (abs($diff) < 0.01) {
                    $change_label = 'No change since you searched.';
                } elseif ($diff > 0) {
                    $change_label = 'Price increased by $' . number_format($diff, 2);
                } else {
                    $change_label = 'Price decreased by $' . number_format(abs($diff), 2);
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Something went wrong while rechecking the price.';
        // For debugging during development:
        // $error .= ' ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recheck price – Farelio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center">
<div class="w-full max-w-xl px-4 py-10">
    <div class="bg-slate-800/80 backdrop-blur rounded-2xl shadow-xl border border-slate-700 px-8 py-6">

        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-xl font-semibold">Recheck price</h1>
                <p class="text-sm text-slate-300">
                    Quick re-check based on sample data. Prices are for demo only.
                </p>
            </div>
            <a href="index.php"
               class="text-xs text-slate-300 hover:text-emerald-300 underline">
                Back to search
            </a>
        </div>

        <?php if ($error): ?>
            <p class="text-sm text-red-300"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($flight && $hotel && $new_total !== null): ?>
            <div class="space-y-3 text-sm">
                <p>
                    <span class="font-semibold">Flight:</span>
                    <?= htmlspecialchars($flight['origin_code']) ?> →
                    <?= htmlspecialchars($flight['destination_code']) ?>
                    (<?= htmlspecialchars($flight['airline']) ?>,
                    <?= (int)$flight['stops'] ?> stop<?= $flight['stops'] == 1 ? '' : 's' ?>)
                </p>

                <p class="text-xs text-slate-400">
                    <?= htmlspecialchars($flight['depart_date']) ?> to
                    <?= htmlspecialchars($flight['return_date']) ?> ·
                    <?= (int)$nights ?> night<?= $nights == 1 ? '' : 's' ?> in hotel
                </p>

                <p>
                    <span class="font-semibold">Hotel:</span>
                    <?= htmlspecialchars($hotel['name']) ?> –
                    <span class="text-xs text-slate-400">
                        rating <?= (float)$hotel['rating'] ?> ·
                        <?= (float)$hotel['distance_to_center_km'] ?> km to center
                    </span>
                </p>

                <?php if ($current_total !== null): ?>
                    <div class="mt-3 border border-slate-700 rounded-lg px-4 py-3 space-y-1">
                        <p class="text-xs text-slate-400">
                            Price at search time
                        </p>
                        <p class="text-base font-semibold">
                            $<?= number_format($current_total, 2) ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-2">
                            Rechecked price now
                        </p>
                        <p class="text-lg font-bold text-emerald-400">
                            $<?= number_format($new_total, 2) ?>
                        </p>
                        <?php if ($change_label): ?>
                            <p class="text-xs text-slate-300 mt-1">
                                <?= htmlspecialchars($change_label) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-3 text-sm">
                        Current sample bundle price:
                        <span class="font-semibold">
                            $<?= number_format($new_total, 2) ?>
                        </span>
                    </p>
                <?php endif; ?>

                <p class="mt-4 text-[11px] text-slate-500">
                    This is a simulated recheck on seeded sample data, to demonstrate
                    how prices might change between search and checkout.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>