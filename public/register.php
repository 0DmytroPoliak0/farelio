<?php
session_start();
require __DIR__ . '/../app/db.php';

$errors = [];
$email = '';
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }
    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, name)
                 VALUES (:email, :password_hash, :name)'
            );
            $stmt->execute([
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name'          => $name ?: null,
            ]);

            // log in right away
            $userId = (int)$pdo->lastInsertId();
            $_SESSION['user'] = [
                'id'    => $userId,
                'email' => $email,
                'name'  => $name,
            ];

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate email
                $errors[] = 'This email is already registered.';
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – Farelio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center">
<div class="w-full max-w-md px-4 py-10">
    <div class="bg-slate-800/80 backdrop-blur rounded-2xl shadow-xl border border-slate-700 px-8 py-6">
        <h1 class="text-xl font-semibold mb-1">Create your account</h1>
        <p class="text-sm text-slate-300 mb-4">
            Save trips and come back to them later.
        </p>

        <?php if ($errors): ?>
            <div class="mb-4 rounded-lg border border-red-500/60 bg-red-500/10 px-3 py-2 text-xs text-red-200">
                <ul class="list-disc pl-4">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-3">
            <div>
                <label class="block text-sm mb-1" for="name">Name (optional)</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    class="w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </div>

            <div>
                <label class="block text-sm mb-1" for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
                    class="w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1" for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                </div>
                <div>
                    <label class="block text-sm mb-1" for="password_confirm">Confirm</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        required
                        class="w-full rounded-lg border border-slate-600 bg-slate-900/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                </div>
            </div>

            <button
                type="submit"
                class="mt-2 w-full rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900"
            >
                Sign up
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-400">
            Already have an account?
            <a href="login.php" class="text-emerald-300 hover:text-emerald-200 underline">
                Sign in
            </a>
        </p>
    </div>
</div>
</body>
</html>