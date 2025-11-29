<?php
// public/delete_trip.php
session_start();
require __DIR__ . '/../app/db.php';

// Must be logged in
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id = (int)($_POST['trip_id'] ?? 0);

    if ($trip_id > 0 && $user_id > 0) {
        // Only delete trips that belong to this user
        $stmt = $pdo->prepare('DELETE FROM trips WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id'      => $trip_id,
            'user_id' => $user_id,
        ]);

        // If at least 1 row was deleted, success
        if ($stmt->rowCount() > 0) {
            header('Location: my_trips.php?deleted=1');
            exit;
        }
    }
}

// If something went wrong
header('Location: my_trips.php?delete_error=1');
exit;