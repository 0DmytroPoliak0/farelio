<?php
session_start();
// /public/index.php
require __DIR__ . '/../app/db.php';

// get all destinations for dropdowns
$stmt = $pdo->query('SELECT code, city, country FROM destinations ORDER BY city');
$destinations = $stmt->fetchAll();

// read form input (GET for now so it is simple)
$origin_code = $_GET['from'] ?? '';
$destination_code = $_GET['to'] ?? '';

function dest_label(array $d): string {
    return $d['city'] . ', ' . $d['country'] . ' (' . $d['code'] . ')';
}

$bundles = [];
$error = null;

if ($origin_code && $destination_code) {
    try {
        // flights for selected route
        $sqlFlights = '
            SELECT *
            FROM flights
            WHERE origin_code = :origin
              AND destination_code = :dest
        ';
        $stmtFlights = $pdo->prepare($sqlFlights);
        $stmtFlights->execute([
            'origin' => $origin_code,
            'dest'   => $destination_code,
        ]);
        $flights = $stmtFlights->fetchAll();

        // hotels at destination
        $sqlHotels = '
            SELECT *
            FROM hotels
            WHERE destination_code = :dest
        ';
        $stmtHotels = $pdo->prepare($sqlHotels);
        $stmtHotels->execute(['dest' => $destination_code]);
        $hotels = $stmtHotels->fetchAll();

        foreach ($flights as $flight) {
            $depart = new DateTime($flight['depart_date']);
            $return = new DateTime($flight['return_date']);
            $nights = max(1, $depart->diff($return)->days);

            foreach ($hotels as $hotel) {
                $total_price = $flight['price'] + $hotel['nightly_price'] * $nights;

                $bundles[] = [
                    'flight'      => $flight,
                    'hotel'       => $hotel,
                    'nights'      => $nights,
                    'total_price' => $total_price,
                ];
            }
        }

        // sort by total_price and keep top 3
        usort($bundles, function ($a, $b) {
            return $a['total_price'] <=> $b['total_price'];
        });
        $bundles = array_slice($bundles, 0, 3);

    } catch (Exception $e) {
        $error = 'Something went wrong while building results.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Farelio – Trip planner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center">
    <div class="w-full max-w-3xl px-4 py-10">
        <!-- Card -->
        <div class="bg-slate-800/80 backdrop-blur rounded-2xl shadow-xl border border-slate-700">

            <!-- HEADER with login / register -->
            <div class="px-8 py-6 border-b border-slate-700 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Farelio</h1>
                    <p class="text-sm text-slate-300 mt-1">
                        Quick check: how much does it cost to go from here to there.
                    </p>
                </div>

                <div class="text-sm flex items-center gap-4">
                    <?php if (!empty($_SESSION['user'])): ?>
                        <span class="text-slate-300">
                            Hello,
                            <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['email']) ?></strong>
                        </span>

                        <a href="my_trips.php"
                           class="text-slate-300 hover:text-emerald-300 text-xs underline">
                            My trips
                        </a>

                        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                            <a href="admin.php"
                            class="text-slate-300 hover:text-amber-300 text-xs underline">
                                Admin
                            </a>
                        <?php endif; ?>

                        <a href="logout.php"
                           class="text-slate-300 hover:text-red-300 text-xs underline">
                            Log out
                        </a>
                    <?php else: ?>
                        <a href="login.php"
                           class="text-slate-300 hover:text-emerald-300 text-xs underline">
                            Sign in
                        </a>
                        <span class="text-slate-500 text-xs mx-1">·</span>
                        <a href="register.php"
                           class="text-slate-300 hover:text-emerald-300 text-xs underline">
                            Create account
                        </a>
                    <?php endif; ?>
                </div>
            </div> <!-- END HEADER -->

            <!-- Search form -->
            <form method="get" class="px-8 pt-6 pb-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- From -->
                    <div>
                        <label for="from" class="block text-sm font-medium mb-1">From</label>
                        <select
                            name="from"
                            id="from"
                            required
                            class="block w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        >
                            <option value="">Select origin</option>
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?= htmlspecialchars($d['code']) ?>"
                                    <?= $origin_code === $d['code'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(dest_label($d)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- To -->
                    <div>
                        <label for="to" class="block text-sm font-medium mb-1">To</label>
                        <select
                            name="to"
                            id="to"
                            required
                            class="block w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        >
                            <option value="">Select destination</option>
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?= htmlspecialchars($d['code']) ?>"
                                    <?= $destination_code === $d['code'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(dest_label($d)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition"
                    >
                        Find cheapest trips
                    </button>
                </div>
            </form>

            <!-- Results -->
            <div class="px-8 pb-6 border-t border-slate-700">
                <?php if (isset($_GET['saved'])): ?>
                    <p class="mt-3 mb-1 text-sm text-emerald-300">
                        Trip saved to your account.
                    </p>
                <?php elseif (isset($_GET['save_error'])): ?>
                    <p class="mt-3 mb-1 text-sm text-red-300">
                        Could not save this trip. Please try again.
                    </p>
                <?php endif; ?>

                <?php if ($error): ?>
                    <p class="mt-4 text-sm text-red-300"><?= htmlspecialchars($error) ?></p>

                <?php elseif (!$origin_code || !$destination_code): ?>
                    <p class="mt-3 text-sm text-slate-300">
                        Pick From and To and click <span class="font-semibold">Find cheapest trips</span>.
                    </p>

                <?php elseif (empty($bundles)): ?>
                    <h2 class="mt-4 text-lg font-semibold">No trips found</h2>
                    <p class="mt-1 text-sm text-slate-300">
                        There are no sample flights or hotels yet for this route. Try another combination.
                    </p>

                <?php else: ?>
                    <h2 class="mt-4 text-lg font-semibold">Cheapest sample bundles</h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Based on seeded data. Prices are for demo only.
                    </p>

                    <div class="mt-4 space-y-3">
                        <?php foreach ($bundles as $bundle): ?>
                            <?php
                                $f = $bundle['flight'];
                                $h = $bundle['hotel'];
                            ?>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/50 px-4 py-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-semibold">
                                        <?= htmlspecialchars($origin_code) ?>
                                        →
                                        <?= htmlspecialchars($destination_code) ?>
                                        <span class="text-xs text-slate-400">
                                            (<?= htmlspecialchars($f['airline']) ?>, <?= (int)$f['stops'] ?> stop<?= $f['stops'] == 1 ? '' : 's' ?>)
                                        </span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <?= htmlspecialchars($f['depart_date']) ?> to <?= htmlspecialchars($f['return_date']) ?> ·
                                        <?= (int)$bundle['nights'] ?> night<?= $bundle['nights'] == 1 ? '' : 's' ?> in hotel
                                    </p>
                                    <p class="text-sm mt-2">
                                        Hotel: <span class="font-medium"><?= htmlspecialchars($h['name']) ?></span>
                                        <span class="text-xs text-slate-400">
                                            · <?= (float)$h['rating'] ?> rating · <?= (float)$h['distance_to_center_km'] ?> km to center
                                        </span>
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-2 md:items-end">
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-emerald-400">
                                            $<?= number_format($bundle['total_price'], 2) ?>
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            Flight: $<?= number_format($f['price'], 2) ?> + hotel
                                        </p>
                                    </div>

                                    <?php if (!empty($_SESSION['user'])): ?>
                                        <form method="post" action="save_trip.php" class="mt-1">
                                            <input type="hidden" name="flight_id" value="<?= (int)$f['id'] ?>">
                                            <input type="hidden" name="hotel_id" value="<?= (int)$h['id'] ?>">
                                            <input type="hidden" name="origin_code" value="<?= htmlspecialchars($origin_code) ?>">
                                            <input type="hidden" name="destination_code" value="<?= htmlspecialchars($destination_code) ?>">
                                            <input type="hidden" name="nights" value="<?= (int)$bundle['nights'] ?>">
                                            <input type="hidden" name="total_price" value="<?= (float)$bundle['total_price'] ?>">

                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg border border-emerald-500 px-3 py-1.5 text-xs font-semibold text-emerald-300 hover:bg-emerald-500/10 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition"
                                            >
                                                Save this trip
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>