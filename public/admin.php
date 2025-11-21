<?php
// public/admin.php
session_start();
require __DIR__ . '/../app/db.php';

// ---------------------------------------------------------------------
// Admin access control: only users with role = 'admin'
// ---------------------------------------------------------------------
if (empty($_SESSION['user'])) {
    // Not logged in → send to login
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];

if (($currentUser['role'] ?? 'user') !== 'admin') {
    // Logged in but not admin → deny access
    http_response_code(403);
    echo 'Access denied. This page is for admins only.';
    exit;
}

$messages = [];
$errors   = [];

// ---------------------------------------------------------------------
// Handle POST actions
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $mode = $_POST['mode'] ?? '';

    try {
        if ($type === 'destination') {
            if ($mode === 'create') {
                $code    = trim($_POST['code'] ?? '');
                $city    = trim($_POST['city'] ?? '');
                $country = trim($_POST['country'] ?? '');

                if ($code && $city && $country) {
                    $stmt = $pdo->prepare('
                        INSERT INTO destinations (code, city, country)
                        VALUES (:code, :city, :country)
                    ');
                    $stmt->execute([
                        'code'    => $code,
                        'city'    => $city,
                        'country' => $country,
                    ]);
                    $messages[] = "Destination {$code} added.";
                } else {
                    $errors[] = 'All destination fields are required.';
                }
            } elseif ($mode === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM destinations WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                    $messages[] = "Destination #{$id} deleted.";
                }
            }

        } elseif ($type === 'hotel') {
            if ($mode === 'create') {
                $destination_code      = trim($_POST['destination_code'] ?? '');
                $name                  = trim($_POST['name'] ?? '');
                $rating                = (float)($_POST['rating'] ?? 0);
                $distance_to_center_km = (float)($_POST['distance_to_center_km'] ?? 0);
                $nightly_price         = (float)($_POST['nightly_price'] ?? 0);

                if ($destination_code && $name) {
                    $stmt = $pdo->prepare('
                        INSERT INTO hotels (destination_code, name, rating, distance_to_center_km, nightly_price)
                        VALUES (:destination_code, :name, :rating, :distance, :price)
                    ');
                    $stmt->execute([
                        'destination_code'      => $destination_code,
                        'name'                  => $name,
                        'rating'                => $rating,
                        'distance'              => $distance_to_center_km,
                        'price'                 => $nightly_price,
                    ]);
                    $messages[] = "Hotel \"{$name}\" added.";
                } else {
                    $errors[] = 'Destination + hotel name are required.';
                }
            } elseif ($mode === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM hotels WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                    $messages[] = "Hotel #{$id} deleted.";
                }
            }

        } elseif ($type === 'flight') {
            if ($mode === 'create') {
                $origin_code        = trim($_POST['origin_code'] ?? '');
                $destination_code   = trim($_POST['destination_code'] ?? '');
                $depart_date        = trim($_POST['depart_date'] ?? '');
                $return_date        = trim($_POST['return_date'] ?? '');
                $airline            = trim($_POST['airline'] ?? '');
                $stops              = (int)($_POST['stops'] ?? 0);
                $total_duration_min = (int)($_POST['total_duration_min'] ?? 0);
                $price              = (float)($_POST['price'] ?? 0);
                $bag_included       = isset($_POST['bag_included']) ? 1 : 0;

                if ($origin_code && $destination_code && $depart_date && $return_date && $airline) {
                    $stmt = $pdo->prepare('
                        INSERT INTO flights (
                            origin_code, destination_code, depart_date, return_date,
                            airline, stops, total_duration_min, price, bag_included
                        )
                        VALUES (
                            :origin_code, :destination_code, :depart_date, :return_date,
                            :airline, :stops, :duration, :price, :bag
                        )
                    ');
                    $stmt->execute([
                        'origin_code'      => $origin_code,
                        'destination_code' => $destination_code,
                        'depart_date'      => $depart_date,
                        'return_date'      => $return_date,
                        'airline'          => $airline,
                        'stops'            => $stops,
                        'duration'         => $total_duration_min,
                        'price'            => $price,
                        'bag'              => $bag_included,
                    ]);
                    $messages[] = "Flight {$origin_code} → {$destination_code} added.";
                } else {
                    $errors[] = 'Origin, destination, dates, and airline are required for flights.';
                }
            } elseif ($mode === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM flights WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                    $messages[] = "Flight #{$id} deleted.";
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Something went wrong while saving changes.';
        // For debugging during development you can uncomment:
        // $errors[] = $e->getMessage();
    }
}

// ---------------------------------------------------------------------
// Load data for tables
// ---------------------------------------------------------------------
$destinations = $pdo->query('SELECT * FROM destinations ORDER BY city')->fetchAll();

$hotelsStmt = $pdo->query('
    SELECT h.*, d.city, d.country
    FROM hotels h
    JOIN destinations d ON d.code = h.destination_code
    ORDER BY d.city, h.name
');
$hotels = $hotelsStmt->fetchAll();

$flightsStmt = $pdo->query('
    SELECT f.*,
           o.city  AS origin_city,
           o.country AS origin_country,
           d.city  AS dest_city,
           d.country AS dest_country
    FROM flights f
    JOIN destinations o ON o.code = f.origin_code
    JOIN destinations d ON d.code = f.destination_code
    ORDER BY o.city, d.city, f.depart_date
');
$flights = $flightsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Farelio – Admin console</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center">
<div class="w-full max-w-5xl px-4 py-10">
    <div class="bg-slate-800/80 backdrop-blur rounded-2xl shadow-xl border border-slate-700">
        <!-- HEADER -->
        <div class="px-8 py-6 border-b border-slate-700 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Admin console</h1>
                <p class="text-sm text-slate-300 mt-1">
                    Manage sample destinations, hotels, and flights for Farelio.
                </p>
            </div>

            <div class="text-sm text-right space-x-3">
                <a href="index.php" class="text-slate-300 hover:text-emerald-300 text-xs underline">
                    Back to search
                </a>
                <span class="text-slate-400 text-xs">
                    <?= htmlspecialchars($currentUser['email']) ?>
                </span>
                <a href="logout.php" class="text-slate-300 hover:text-red-300 text-xs underline">
                    Log out
                </a>
            </div>
        </div>

        <div class="px-8 pt-4 pb-8 space-y-6">
            <!-- messages -->
            <?php if ($messages): ?>
                <div class="rounded-lg border border-emerald-500/60 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">
                    <?php foreach ($messages as $m): ?>
                        <p><?= htmlspecialchars($m) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="rounded-lg border border-red-500/60 bg-red-500/10 px-4 py-2 text-sm text-red-200">
                    <?php foreach ($errors as $e): ?>
                        <p><?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- DESTINATIONS -->
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Destinations</h2>

                <form method="post" class="flex flex-wrap gap-3 items-end text-xs">
                    <input type="hidden" name="type" value="destination">
                    <input type="hidden" name="mode" value="create">

                    <div>
                        <label class="block mb-1">Code</label>
                        <input name="code" class="rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="YVR">
                    </div>
                    <div>
                        <label class="block mb-1">City</label>
                        <input name="city" class="rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="Vancouver">
                    </div>
                    <div>
                        <label class="block mb-1">Country</label>
                        <input name="country" class="rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="Canada">
                    </div>

                    <button
                        type="submit"
                        class="ml-auto inline-flex items-center rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-emerald-400">
                        Add destination
                    </button>
                </form>

                <div class="overflow-x-auto text-xs">
                    <table class="min-w-full border border-slate-700 rounded-lg overflow-hidden">
                        <thead class="bg-slate-900/70">
                        <tr>
                            <th class="px-3 py-2 text-left">ID</th>
                            <th class="px-3 py-2 text-left">Code</th>
                            <th class="px-3 py-2 text-left">City</th>
                            <th class="px-3 py-2 text-left">Country</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($destinations as $d): ?>
                            <tr class="border-t border-slate-700">
                                <td class="px-3 py-2"><?= (int)$d['id'] ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars($d['code']) ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars($d['city']) ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars($d['country']) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <form method="post" onsubmit="return confirm('Delete this destination?');">
                                        <input type="hidden" name="type" value="destination">
                                        <input type="hidden" name="mode" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                        <button
                                            type="submit"
                                            class="text-xs text-red-300 hover:text-red-200 underline">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <hr class="border-slate-700/80">

            <!-- HOTELS -->
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">Hotels</h2>

                <form method="post" class="grid grid-cols-1 md:grid-cols-5 gap-3 text-xs items-end">
                    <input type="hidden" name="type" value="hotel">
                    <input type="hidden" name="mode" value="create">

                    <div>
                        <label class="block mb-1">Destination code</label>
                        <input name="destination_code" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="FCO">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1">Name</label>
                        <input name="name" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="Hotel Roma">
                    </div>
                    <div>
                        <label class="block mb-1">Rating</label>
                        <input name="rating" type="number" step="0.1" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="8.2">
                    </div>
                    <div>
                        <label class="block mb-1">Nightly price</label>
                        <input name="nightly_price" type="number" step="0.01" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="150">
                    </div>
                    <div>
                        <label class="block mb-1">Distance to center (km)</label>
                        <input name="distance_to_center_km" type="number" step="0.1" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="2.5">
                    </div>

                    <div class="md:col-span-5 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-emerald-400">
                            Add hotel
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto text-xs">
                    <table class="min-w-full border border-slate-700 rounded-lg overflow-hidden">
                        <thead class="bg-slate-900/70">
                        <tr>
                            <th class="px-3 py-2 text-left">ID</th>
                            <th class="px-3 py-2 text-left">Destination</th>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Rating</th>
                            <th class="px-3 py-2 text-left">Nightly</th>
                            <th class="px-3 py-2 text-left">Dist. to center</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($hotels as $h): ?>
                            <tr class="border-t border-slate-700">
                                <td class="px-3 py-2"><?= (int)$h['id'] ?></td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($h['destination_code']) ?>
                                    – <?= htmlspecialchars($h['city']) ?>, <?= htmlspecialchars($h['country']) ?>
                                </td>
                                <td class="px-3 py-2"><?= htmlspecialchars($h['name']) ?></td>
                                <td class="px-3 py-2"><?= (float)$h['rating'] ?></td>
                                <td class="px-3 py-2">$<?= number_format($h['nightly_price'], 2) ?></td>
                                <td class="px-3 py-2"><?= (float)$h['distance_to_center_km'] ?> km</td>
                                <td class="px-3 py-2 text-right">
                                    <form method="post" onsubmit="return confirm('Delete this hotel?');">
                                        <input type="hidden" name="type" value="hotel">
                                        <input type="hidden" name="mode" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                        <button
                                            type="submit"
                                            class="text-xs text-red-300 hover:text-red-200 underline">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <hr class="border-slate-700/80">

            <!-- FLIGHTS -->
            <section class="space-y-3 pb-2">
                <h2 class="text-lg font-semibold">Flights</h2>

                <form method="post" class="grid grid-cols-1 md:grid-cols-6 gap-3 text-xs items-end">
                    <input type="hidden" name="type" value="flight">
                    <input type="hidden" name="mode" value="create">

                    <div>
                        <label class="block mb-1">Origin code</label>
                        <input name="origin_code" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="YVR">
                    </div>
                    <div>
                        <label class="block mb-1">Destination code</label>
                        <input name="destination_code" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="FCO">
                    </div>
                    <div>
                        <label class="block mb-1">Depart date</label>
                        <input name="depart_date" type="date" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1">
                    </div>
                    <div>
                        <label class="block mb-1">Return date</label>
                        <input name="return_date" type="date" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1">
                    </div>
                    <div>
                        <label class="block mb-1">Airline</label>
                        <input name="airline" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" placeholder="BudgetWings">
                    </div>
                    <div>
                        <label class="block mb-1">Stops</label>
                        <input name="stops" type="number" min="0" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" value="0">
                    </div>
                    <div>
                        <label class="block mb-1">Duration (min)</label>
                        <input name="total_duration_min" type="number" min="0" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" value="600">
                    </div>
                    <div>
                        <label class="block mb-1">Price</label>
                        <input name="price" type="number" step="0.01" class="w-full rounded border border-slate-600 bg-slate-900/60 px-2 py-1" value="900">
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="bag_included" name="bag_included" type="checkbox" class="rounded border-slate-600 bg-slate-900/60">
                        <label for="bag_included" class="text-xs">Bag included</label>
                    </div>

                    <div class="md:col-span-6 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-emerald-400">
                            Add flight
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto text-xs">
                    <table class="min-w-full border border-slate-700 rounded-lg overflow-hidden">
                        <thead class="bg-slate-900/70">
                        <tr>
                            <th class="px-3 py-2 text-left">ID</th>
                            <th class="px-3 py-2 text-left">Route</th>
                            <th class="px-3 py-2 text-left">Dates</th>
                            <th class="px-3 py-2 text-left">Airline / stops</th>
                            <th class="px-3 py-2 text-left">Duration</th>
                            <th class="px-3 py-2 text-left">Price</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($flights as $f): ?>
                            <tr class="border-t border-slate-700">
                                <td class="px-3 py-2"><?= (int)$f['id'] ?></td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($f['origin_code']) ?>
                                    (<?= htmlspecialchars($f['origin_city']) ?>)
                                    →
                                    <?= htmlspecialchars($f['destination_code']) ?>
                                    (<?= htmlspecialchars($f['dest_city']) ?>)
                                </td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($f['depart_date']) ?> – <?= htmlspecialchars($f['return_date']) ?>
                                </td>
                                <td class="px-3 py-2">
                                    <?= htmlspecialchars($f['airline']) ?>,
                                    <?= (int)$f['stops'] ?> stop<?= $f['stops'] == 1 ? '' : 's' ?>
                                    <?= $f['bag_included'] ? ' · bag incl.' : '' ?>
                                </td>
                                <td class="px-3 py-2">
                                    <?= (int)$f['total_duration_min'] ?> min
                                </td>
                                <td class="px-3 py-2">
                                    $<?= number_format($f['price'], 2) ?>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <form method="post" onsubmit="return confirm('Delete this flight?');">
                                        <input type="hidden" name="type" value="flight">
                                        <input type="hidden" name="mode" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                        <button
                                            type="submit"
                                            class="text-xs text-red-300 hover:text-red-200 underline">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
</body>
</html>