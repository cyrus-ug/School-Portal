<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$tenantId = getCurrentTenantId();

try {
    $stmt = $pdo->prepare('SELECT id, name, logo_url FROM schools WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$tenantId]);
    $school = $stmt->fetch();

    if (!$school) {
        logout();
    }

    $schoolName = $school['name'];
    $schoolLogo = $school['logo_url'];
    $schoolInitials = implode('', array_map(fn($w) => $w[0], explode(' ', $schoolName)));
} catch (PDOException $e) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($schoolName); ?></title>
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
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col">
            <!-- School Header -->
            <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3 mb-4">
                    <?php if ($schoolLogo): ?>
                        <img src="<?php echo htmlspecialchars($schoolLogo); ?>" alt="<?php echo htmlspecialchars($schoolName); ?>" class="w-10 h-10 rounded-lg object-cover">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400"><?php echo htmlspecialchars($schoolInitials); ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($schoolName); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">ID: <?php echo htmlspecialchars($tenantId); ?></p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
                <a href="dashboard.php" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-medium transition">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span>👥</span>
                    <span>Students</span>
                </a>
                <a href="#" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span>📚</span>
                    <span>Courses</span>
                </a>
                <a href="#" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span>📋</span>
                    <span>Requirements</span>
                </a>
                <a href="#" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span>⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                <button
                    onclick="toggleDarkMode()"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm"
                >
                    <span id="theme-toggle-icon">🌙</span>
                    <span id="theme-toggle-text">Dark Mode</span>
                </button>
                <form method="GET" action="logout.php" class="w-full">
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium"
                    >
                        <span>🚪</span>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Dashboard</h1>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Last login: <?php echo date('M d, Y H:i'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Stats Cards -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Total Students</p>
                        <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">0</p>
                        <p class="text-xs text-slate-500 dark:text-slate-500 mt-2">No data available</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Active Courses</p>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">0</p>
                        <p class="text-xs text-slate-500 dark:text-slate-500 mt-2">No data available</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Pending Requirements</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">0</p>
                        <p class="text-xs text-slate-500 dark:text-slate-500 mt-2">No data available</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">System Health</p>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">100%</p>
                        <p class="text-xs text-slate-500 dark:text-slate-500 mt-2">All systems operational</p>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-900/10 rounded-xl p-8 border border-indigo-200 dark:border-indigo-900/30">
                    <h2 class="text-xl font-bold mb-2">Welcome to EduBridge Uganda</h2>
                    <p class="text-slate-700 dark:text-slate-300 mb-4">Your school management platform is ready. Start by adding students, creating courses, and managing requirements from the sidebar menu.</p>
                    <button class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition">Get Started</button>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleDarkMode() {
            if (localStorage.theme === 'dark') {
                localStorage.theme = 'light';
                document.documentElement.classList.remove('dark');
                document.getElementById('theme-toggle-icon').textContent = '🌙';
                document.getElementById('theme-toggle-text').textContent = 'Dark Mode';
            } else {
                localStorage.theme = 'dark';
                document.documentElement.classList.add('dark');
                document.getElementById('theme-toggle-icon').textContent = '☀️';
                document.getElementById('theme-toggle-text').textContent = 'Light Mode';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            document.getElementById('theme-toggle-icon').textContent = isDark ? '☀️' : '🌙';
            document.getElementById('theme-toggle-text').textContent = isDark ? 'Light Mode' : 'Dark Mode';
        });
    </script>
</body>
</html>
