<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$tenantId = getCurrentTenantId();
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student = null;
$enrolledCourses = [];
$requirements = [];

try {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL');
    $stmt->execute([$studentId, $tenantId]);
    $student = $stmt->fetch();

    if (!$student) {
        header('Location: students.php');
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT DISTINCT c.id, c.name, c.code, c.credits, c.status
        FROM courses c
        INNER JOIN requirements r ON c.id = r.course_id
        WHERE r.student_id = ? AND r.tenant_id = ? AND c.deleted_at IS NULL AND r.deleted_at IS NULL
        ORDER BY c.name ASC
    ');
    $stmt->execute([$studentId, $tenantId]);
    $enrolledCourses = $stmt->fetchAll();

    $stmt = $pdo->prepare('
        SELECT r.id, r.requirement_type, r.title, r.due_date, r.status, r.grade, r.max_grade, c.name as course_name, c.code as course_code
        FROM requirements r
        INNER JOIN courses c ON r.course_id = c.id
        WHERE r.student_id = ? AND r.tenant_id = ? AND r.deleted_at IS NULL
        ORDER BY r.due_date DESC
        LIMIT 10
    ');
    $stmt->execute([$studentId, $tenantId]);
    $requirements = $stmt->fetchAll();
} catch (PDOException $e) {
    header('Location: students.php');
    exit;
}

$age = $student['date_of_birth'] ? date_diff(date_create($student['date_of_birth']), date_create('today'))->y : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> - Dashboard</title>
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
                <a href="#" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span>⚙️</span>
                    <span>Settings</span>
                </a>
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
                        <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">ID: <?php echo htmlspecialchars($student['student_id_number']); ?></p>
                    </div>
                    <div class="flex gap-3">
                        <a href="edit-student.php?id=<?php echo $studentId; ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition">
                            ✏️ Edit
                        </a>
                        <a href="students.php" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                            ← Back to Students
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                <?php if (isset($_GET['updated'])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <p class="text-sm text-emerald-800 dark:text-emerald-200">✓ Student information updated successfully</p>
                    </div>
                <?php endif; ?>

                <!-- Student Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Enrollment Date</p>
                        <p class="text-2xl font-bold"><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Age</p>
                        <p class="text-2xl font-bold"><?php echo $age !== null ? $age : 'N/A'; ?></p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Gender</p>
                        <p class="text-2xl font-bold"><?php echo $student['gender'] ? ucfirst($student['gender']) : 'N/A'; ?></p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <p class="text-slate-600 dark:text-slate-400 text-sm font-medium mb-2">Status</p>
                        <?php
                        $statusColors = [
                            'active' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                            'inactive' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-200',
                            'graduated' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                            'withdrawn' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200',
                        ];
                        $colorClass = $statusColors[$student['status']] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200';
                        ?>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-semibold <?php echo $colorClass; ?>">
                            <?php echo ucfirst($student['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 mb-8">
                    <h2 class="text-lg font-semibold mb-4">Contact Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 text-sm mb-1">Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 text-sm mb-1">Phone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <p class="text-slate-600 dark:text-slate-400 text-sm mb-1">Date of Birth</p>
                            <p class="font-medium"><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Courses -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 mb-8">
                    <h2 class="text-lg font-semibold mb-4">Enrolled Courses</h2>
                    <?php if (count($enrolledCourses) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800">
                                        <th class="px-4 py-3 text-left font-semibold">Course Code</th>
                                        <th class="px-4 py-3 text-left font-semibold">Course Name</th>
                                        <th class="px-4 py-3 text-center font-semibold">Credits</th>
                                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <?php foreach ($enrolledCourses as $course): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td class="px-4 py-3 font-mono font-semibold"><?php echo htmlspecialchars($course['code']); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($course['name']); ?></td>
                                            <td class="px-4 py-3 text-center"><?php echo $course['credits']; ?></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?php echo $course['status'] === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200'; ?>">
                                                    <?php echo ucfirst($course['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-600 dark:text-slate-400">No enrolled courses yet</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Requirements -->
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                    <h2 class="text-lg font-semibold mb-4">Recent Requirements</h2>
                    <?php if (count($requirements) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($requirements as $req): ?>
                                <div class="p-4 border border-slate-200 dark:border-slate-800 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <p class="font-semibold"><?php echo htmlspecialchars($req['title']); ?></p>
                                            <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($req['course_name']); ?> (<?php echo htmlspecialchars($req['course_code']); ?>)</p>
                                        </div>
                                        <?php
                                        $typeEmojis = [
                                            'assignment' => '📝',
                                            'project' => '🎯',
                                            'exam' => '📝',
                                            'participation' => '💬',
                                            'lab' => '🧪',
                                            'other' => '📌'
                                        ];
                                        ?>
                                        <span class="text-lg"><?php echo $typeEmojis[$req['requirement_type']] ?? '📌'; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex gap-4 text-sm">
                                            <span class="text-slate-600 dark:text-slate-400">Due: <?php echo date('M d, Y', strtotime($req['due_date'])); ?></span>
                                            <?php if ($req['status'] !== 'pending'): ?>
                                                <span class="text-slate-600 dark:text-slate-400">Grade: <?php echo $req['grade'] ? number_format($req['grade'], 1) . '/' . $req['max_grade'] : 'N/A'; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php
                                            $statusClass = [
                                                'pending' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200',
                                                'submitted' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                                                'graded' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                                                'overdue' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200',
                                            ];
                                            echo $statusClass[$req['status']] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200';
                                        ?>">
                                            <?php echo ucfirst($req['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-600 dark:text-slate-400">No requirements assigned yet</p>
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
