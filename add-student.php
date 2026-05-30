<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

requirePermission('manage_students');

$tenantId = getCurrentTenantId();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentIdNumber = trim($_POST['student_id_number'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $enrollmentDate = trim($_POST['enrollment_date'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if (empty($studentIdNumber)) $errors[] = 'Student ID number is required';
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($enrollmentDate)) $errors[] = 'Enrollment date is required';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO students (tenant_id, student_id_number, first_name, last_name, email, phone, date_of_birth, gender, enrollment_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $tenantId,
                $studentIdNumber,
                $firstName,
                $lastName,
                $email ?: null,
                $phone ?: null,
                $dateOfBirth ?: null,
                $gender ?: null,
                $enrollmentDate,
                $status
            ]);
            $success = true;
            header('Location: students.php?added=1');
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = 'Student ID number already exists for this school';
            } else {
                $errors[] = 'Failed to add student. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Dashboard</title>
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
                        <h1 class="text-2xl font-bold">Add New Student</h1>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Register a new student in the system</p>
                    </div>
                    <a href="students.php" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                        ← Back to Students
                    </a>
                </div>
            </div>

            <!-- Form Section -->
            <div class="p-8">
                <div class="max-w-3xl">
                    <?php if (!empty($errors)): ?>
                        <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">Please fix the following errors:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="bg-white dark:bg-slate-900 rounded-xl p-8 border border-slate-200 dark:border-slate-800 space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Basic Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="student_id_number" class="block text-sm font-medium mb-2">Student ID Number *</label>
                                    <input
                                        type="text"
                                        id="student_id_number"
                                        name="student_id_number"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['student_id_number'] ?? ''); ?>"
                                        placeholder="e.g., STM-2024-001"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                                <div>
                                    <label for="enrollment_date" class="block text-sm font-medium mb-2">Enrollment Date *</label>
                                    <input
                                        type="date"
                                        id="enrollment_date"
                                        name="enrollment_date"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['enrollment_date'] ?? date('Y-m-d')); ?>"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Personal Details -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Personal Details</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium mb-2">First Name *</label>
                                    <input
                                        type="text"
                                        id="first_name"
                                        name="first_name"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                        placeholder="John"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium mb-2">Last Name *</label>
                                    <input
                                        type="text"
                                        id="last_name"
                                        name="last_name"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                        placeholder="Doe"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium mb-2">Date of Birth</label>
                                    <input
                                        type="date"
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                                <div>
                                    <label for="gender" class="block text-sm font-medium mb-2">Gender</label>
                                    <select
                                        id="gender"
                                        name="gender"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                        <option value="">Select gender</option>
                                        <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Contact Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="email" class="block text-sm font-medium mb-2">Email</label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        placeholder="student@example.com"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium mb-2">Phone</label>
                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                        placeholder="+256123456789"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Status</h2>
                            <div>
                                <select
                                    id="status"
                                    name="status"
                                    class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                >
                                    <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="graduated" <?php echo ($_POST['status'] ?? '') === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                    <option value="withdrawn" <?php echo ($_POST['status'] ?? '') === 'withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                                </select>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-4 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="submit"
                                class="px-6 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition"
                            >
                                Add Student
                            </button>
                            <a
                                href="students.php"
                                class="px-6 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-center hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
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
