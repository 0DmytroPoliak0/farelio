<?php
// public/save_trip.php
session_start();

if (empty($_SESSION['user'])) {
    // must be logged in
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../app/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id          = (int)($_SESSION['user']['id'] ?? 0);
$flight_id        = (int)($_POST['flight_id'] ?? 0);
$hotel_id         = (int)($_POST['hotel_id'] ?? 0);
$origin_code      = $_POST['origin_code']      ?? '';
$destination_code = $_POST['destination_code'] ?? '';
$nights           = (int)($_POST['nights'] ?? 0);
$total_price      = (float)($_POST['total_price'] ?? 0);

// basic validation – if anything important is missing, bail
if (
    !$user_id || !$flight_id || !$hotel_id ||
    !$origin_code || !$destination_code || !$nights || !$total_price
) {
    header('Location: index.php?save_error=1');
    exit;
}

try {
    // Load the flight row to get the dates (and to validate ID)
    $stmtFlight = $pdo->prepare('SELECT * FROM flights WHERE id = :id');
    $stmtFlight->execute(['id' => $flight_id]);
    $flight = $stmtFlight->fetch();

    if (!$flight) {
        header('Location: index.php?save_error=1');
        exit;
    }

    // Optional: make sure hotel exists too
    $stmtHotel = $pdo->prepare('SELECT * FROM hotels WHERE id = :id');
    $stmtHotel->execute(['id' => $hotel_id]);
    $hotel = $stmtHotel->fetch();

    if (!$hotel) {
        header('Location: index.php?save_error=1');
        exit;
    }

    // Insert into trips
    $stmtInsert = $pdo->prepare(
        'INSERT INTO trips (
            user_id,
            flight_id,
            hotel_id,
            origin_code,
            destination_code,
            depart_date,
            return_date,
            total_price,
            created_at
        ) VALUES (
            :user_id,
            :flight_id,
            :hotel_id,
            :origin_code,
            :destination_code,
            :depart_date,
            :return_date,
            :total_price,
            NOW()
        )'
    );

    $ok = $stmtInsert->execute([
        'user_id'          => $user_id,
        'flight_id'        => $flight_id,
        'hotel_id'         => $hotel_id,
        'origin_code'      => $origin_code,
        'destination_code' => $destination_code,
        'depart_date'      => $flight['depart_date'],
        'return_date'      => $flight['return_date'],
        'total_price'      => $total_price,
    ]);

    if (!$ok) {
        header('Location: index.php?save_error=1');
        exit;
    }

    header('Location: index.php?saved=1');
    exit;

} catch (Exception $e) {
    // If something blows up, just show generic error on main page
    header('Location: index.php?save_error=1');
    exit;
}