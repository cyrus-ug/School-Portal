<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

requirePermission('manage_students');

$tenantId = getCurrentTenantId();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$limit = 25;
$offset = ($page - 1) * $limit;

try {
    $countQuery = 'SELECT COUNT(*) as total FROM students WHERE tenant_id = ? AND deleted_at IS NULL';
    $queryParams = [$tenantId];

    if ($search) {
        $countQuery .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id_number LIKE ?)';
        $searchTerm = "%{$search}%";
        $queryParams = [$tenantId, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    if ($status && in_array($status, ['active', 'inactive', 'graduated', 'withdrawn'])) {
        $countQuery .= ' AND status = ?';
        $queryParams[] = $status;
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($queryParams);
    $totalCount = $countStmt->fetch()['total'];
    $totalPages = ceil($totalCount / $limit);
    $page = min($page, max(1, $totalPages));

    $listQuery = 'SELECT id, student_id_number, first_name, last_name, email, phone, enrollment_date, status, created_at FROM students WHERE tenant_id = ? AND deleted_at IS NULL';
    $queryParams = [$tenantId];

    if ($search) {
        $listQuery .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id_number LIKE ?)';
        $searchTerm = "%{$search}%";
        $queryParams = [$tenantId, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    if ($status && in_array($status, ['active', 'inactive', 'graduated', 'withdrawn'])) {
        $listQuery .= ' AND status = ?';
        $queryParams[] = $status;
    }

    $listQuery .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $queryParams[] = $limit;
    $queryParams[] = $offset;

    $stmt = $pdo->prepare($listQuery);
    $stmt->execute($queryParams);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
    $totalCount = 0;
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Dashboard</title>
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
                <a href="students.php" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-medium transition">
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
                    <a href="users.php" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
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
                        <h1 class="text-2xl font-bold">Students</h1>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Manage student records and enrollment</p>
                    </div>
                    <a href="add-student.php" class="px-6 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
                        + Add Student
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
                                    placeholder="Name, email, or ID number..."
                                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                >
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium mb-2">Status</label>
                                <select
                                    id="status"
                                    name="status"
                                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="graduated" <?php echo $status === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                    <option value="withdrawn" <?php echo $status === 'withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition">
                                    Filter
                                </button>
                                <a href="students.php" class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-center hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Students Table -->
                <div class="bg-white dark:bg-slate-900 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm">
                    <?php if (count($students) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">ID Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Phone</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Enrollment Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <?php foreach ($students as $student): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($student['student_id_number']); ?></td>
                                            <td class="px-6 py-4 text-sm">
                                                <a href="view-student.php?id=<?php echo $student['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                                            <td class="px-6 py-4 text-sm"><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                                            <td class="px-6 py-4 text-sm">
                                                <?php
                                                $statusColors = [
                                                    'active' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                                                    'inactive' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-200',
                                                    'graduated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                                                    'withdrawn' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200',
                                                ];
                                                $colorClass = $statusColors[$student['status']] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200';
                                                ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $colorClass; ?>">
                                                    <?php echo ucfirst($student['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="view-student.php?id=<?php echo $student['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium" title="View">
                                                        👁️
                                                    </a>
                                                    <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium" title="Edit">
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
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalCount); ?> of <?php echo $totalCount; ?> students
                                </div>
                                <div class="flex gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="students.php?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <?php if ($i === $page): ?>
                                            <span class="px-3 py-2 rounded-lg bg-indigo-600 text-white font-medium">
                                                <?php echo $i; ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="students.php?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="students.php?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                            Next →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <p class="text-slate-600 dark:text-slate-400 mb-4">No students found</p>
                            <a href="add-student.php" class="inline-block px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition">
                                Add First Student
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
