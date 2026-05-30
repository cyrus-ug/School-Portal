<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare('SELECT id, tenant_id, password_hash, name FROM users WHERE email = ? AND deleted_at IS NULL');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['tenant_id'] = $user['tenant_id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduBridge Uganda - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-50 transition-colors duration-300">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 mb-4">
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">EB</span>
                </div>
                <h1 class="text-3xl font-bold">EduBridge Uganda</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">School Management Platform</p>
            </div>

            <?php if ($error): ?>
            <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <p class="text-sm text-red-800 dark:text-red-200"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6 bg-white dark:bg-slate-900 p-8 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800">
                <div>
                    <label for="email" class="block text-sm font-medium mb-2">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                        placeholder="admin@school.edu"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                        placeholder="••••••••"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full px-4 py-2 rounded-lg font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950 transition"
                >
                    Sign In
                </button>
            </form>

            <div class="text-center">
                <button
                    onclick="toggleDarkMode()"
                    class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition"
                >
                    <span id="theme-toggle">🌙 Dark Mode</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            if (localStorage.theme === 'dark') {
                localStorage.theme = 'light';
                document.documentElement.classList.remove('dark');
                document.getElementById('theme-toggle').textContent = '🌙 Dark Mode';
            } else {
                localStorage.theme = 'dark';
                document.documentElement.classList.add('dark');
                document.getElementById('theme-toggle').textContent = '☀️ Light Mode';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            document.getElementById('theme-toggle').textContent = isDark ? '☀️ Light Mode' : '🌙 Dark Mode';
        });
    </script>
</body>
</html>
