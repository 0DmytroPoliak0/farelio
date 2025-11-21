<?php
session_start();
require __DIR__ . '/../app/db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email or password is incorrect.';
        } else {
            $_SESSION['user'] = [
                'id'       => (int)$user['id'],
                'email'    => $user['email'],
                'name'     => $user['name'],
                'role'     => $user['role'],
                'is_admin' => (int)($user['is_admin'] ?? 0),   // <-- NEW
            ];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login – Farelio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center">
<div class="w-full max-w-md px-4 py-10">
    <div class="bg-slate-800/80 backdrop-blur rounded-2xl shadow-xl border border-slate-700 px-8 py-6">
        <h1 class="text-xl font-semibold mb-1">Sign in</h1>
        <p class="text-sm text-slate-300 mb-4">
            Continue planning your trips.
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

            <button
                type="submit"
                class="mt-2 w-full rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900"
            >
                Sign in
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-400">
            Need an account?
            <a href="register.php" class="text-emerald-300 hover:text-emerald-200 underline">
                Sign up
            </a>
        </p>
    </div>
</div>
</body>
</html>