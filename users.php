<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

requirePermission('manage_users');

$tenantId = getCurrentTenantId();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$limit = 25;
$offset = ($page - 1) * $limit;

try {
    $countQuery = 'SELECT COUNT(*) as total FROM users WHERE tenant_id = ? AND deleted_at IS NULL';
    $queryParams = [$tenantId];

    if ($search) {
        $countQuery .= ' AND (name LIKE ? OR email LIKE ?)';
        $searchTerm = "%{$search}%";
        $queryParams = [$tenantId, $searchTerm, $searchTerm];
    }

    if ($role && in_array($role, ['admin', 'staff', 'teacher', 'student'])) {
        $countQuery .= ' AND role = ?';
        $queryParams[] = $role;
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($queryParams);
    $totalCount = $countStmt->fetch()['total'];
    $totalPages = ceil($totalCount / $limit);
    $page = min($page, max(1, $totalPages));

    $listQuery = 'SELECT id, name, email, role, status, created_at FROM users WHERE tenant_id = ? AND deleted_at IS NULL';
    $queryParams = [$tenantId];

    if ($search) {
        $listQuery .= ' AND (name LIKE ? OR email LIKE ?)';
        $searchTerm = "%{$search}%";
        $queryParams = [$tenantId, $searchTerm, $searchTerm];
    }

    if ($role && in_array($role, ['admin', 'staff', 'teacher', 'student'])) {
        $listQuery .= ' AND role = ?';
        $queryParams[] = $role;
    }

    $listQuery .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $queryParams[] = $limit;
    $queryParams[] = $offset;

    $stmt = $pdo->prepare($listQuery);
    $stmt->execute($queryParams);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $totalCount = 0;
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Roles - Dashboard</title>
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
            <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                <a href="dashboard.php" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">EB</span>
                    </div>
                    <p class="text-sm font-semibold">EduBridge</p>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
                <a href="dashboard.php" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="students.php" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
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
                <?php if (hasPermission('manage_users')): ?>
                    <a href="users.php" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-medium transition">
                        <span>🔑</span>
                        <span>Users & Roles</span>
                    </a>
                <?php endif; ?>
                <?php if (hasPermission('manage_settings')): ?>
                    <a href="#" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <span>⚙️</span>
                        <span>Settings</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                <button onclick="toggleDarkMode()" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm">
                    <span id="theme-toggle-icon">🌙</span>
                    <span id="theme-toggle-text">Dark Mode</span>
                </button>
                <form method="GET" action="logout.php" class="w-full">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium">
                        <span>🚪</span>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-8 py-4 sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Users & Roles</h1>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Manage system users and their access levels</p>
                    </div>
                    <a href="add-user.php" class="px-6 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
                        + Add User
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="p-8">
                <div class="mb-6 bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="search" class="block text-sm font-medium mb-2">Search</label>
                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Name or email..."
                                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                >
                            </div>
                            <div>
                                <label for="role" class="block text-sm font-medium mb-2">Role</label>
                                <select
                                    id="role"
                                    name="role"
                                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                >
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="staff" <?php echo $role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="teacher" <?php echo $role === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition">
                                    Filter
                                </button>
                                <a href="users.php" class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-center hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white dark:bg-slate-900 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm">
                    <?php if (count($users) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Created</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo getRoleColor($user['role']); ?>">
                                                    <?php echo getRoleLabel($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $user['status'] === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td class="px-6 py-4 text-sm text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium" title="Edit">
                                                        ✏️
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div class="text-sm text-slate-600 dark:text-slate-400">
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalCount); ?> of <?php echo $totalCount; ?> users
                                </div>
                                <div class="flex gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="users.php?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role ? '&role=' . urlencode($role) : ''; ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <?php if ($i === $page): ?>
                                            <span class="px-3 py-2 rounded-lg bg-indigo-600 text-white font-medium">
                                                <?php echo $i; ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="users.php?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role ? '&role=' . urlencode($role) : ''; ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="users.php?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role ? '&role=' . urlencode($role) : ''; ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                            Next →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <p class="text-slate-600 dark:text-slate-400 mb-4">No users found</p>
                            <a href="add-user.php" class="inline-block px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition">
                                Add First User
                            </a>
                        </div>
                    <?php endif; ?>
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
