<?php
session_start();

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../app/db.php';

$user_id = (int)($_SESSION['user']['id'] ?? 0);

$sql = '
    SELECT
        t.*,
        f.airline,
        f.depart_date,
        f.return_date,
        f.stops,
        f.price AS flight_price,
        h.name AS hotel_name,
        h.rating,
        h.distance_to_center_km,
        h.nightly_price,
        d_from.city  AS origin_city,
        d_from.country AS origin_country,
        d_to.city    AS dest_city,
        d_to.country AS dest_country
    FROM trips t
    JOIN flights f       ON t.flight_id = f.id
    JOIN hotels h        ON t.hotel_id = h.id
    JOIN destinations d_from ON t.origin_code = d_from.code
    JOIN destinations d_to   ON t.destination_code = d_to.code
    WHERE t.user_id = :user_id
    ORDER BY t.created_at DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My trips – Farelio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center">
    <div class="w-full max-w-3xl px-4 py-10">
        <div class="bg-slate-800/80 backdrop-blur rounded-2xl shadow-xl border border-slate-700">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-slate-700 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">My trips</h1>
                    <p class="text-sm text-slate-300 mt-1">
                        Saved trips on your Farelio account.
                    </p>
                </div>
                <div class="text-sm flex items-center gap-4">
                    <a href="index.php"
                       class="text-slate-300 hover:text-emerald-300 text-xs underline">
                        Back to search
                    </a>

                    <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                        <a href="admin.php"
                        class="text-slate-300 hover:text-amber-300 text-xs underline">
                            Admin
                        </a>
                    <?php endif; ?>

                    <span class="text-slate-300 text-xs">
                        <?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['email']) ?>
                    </span>
                    <a href="logout.php"
                       class="text-slate-300 hover:text-red-300 text-xs underline">
                        Log out
                    </a>
                </div>
            </div>

            <div class="px-8 pb-6 pt-4">
                <?php if (empty($trips)): ?>
                    <p class="mt-3 text-sm text-slate-300">
                        You do not have any saved trips yet. Go back to the search and save a bundle.
                    </p>
                <?php else: ?>
                    <div class="mt-3 space-y-3">
                        <?php foreach ($trips as $t): ?>
                            <div class="rounded-xl border border-slate-700 bg-slate-900/50 px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold">
                                        <?= htmlspecialchars($t['origin_city']) ?>, <?= htmlspecialchars($t['origin_country']) ?>
                                        →
                                        <?= htmlspecialchars($t['dest_city']) ?>, <?= htmlspecialchars($t['dest_country']) ?>
                                        <span class="text-xs text-slate-400">
                                            (<?= htmlspecialchars($t['airline']) ?>, <?= (int)$t['stops'] ?> stop<?= $t['stops'] == 1 ? '' : 's' ?>)
                                        </span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <?= htmlspecialchars($t['depart_date']) ?> to <?= htmlspecialchars($t['return_date']) ?> ·
                                        <?= (int)$t['nights'] ?> night<?= $t['nights'] == 1 ? '' : 's' ?> in hotel
                                    </p>
                                    <p class="text-sm mt-2">
                                        Hotel: <span class="font-medium"><?= htmlspecialchars($t['hotel_name']) ?></span>
                                        <span class="text-xs text-slate-400">
                                            · <?= (float)$t['rating'] ?> rating · <?= (float)$t['distance_to_center_km'] ?> km to center
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-emerald-400">
                                        $<?= number_format($t['total_price'], 2) ?>
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        Flight: $<?= number_format($t['flight_price'], 2) ?> + hotel
                                    </p>
                                    <p class="text-[10px] text-slate-500 mt-1">
                                        Saved on <?= htmlspecialchars($t['created_at']) ?>
                                    </p>
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