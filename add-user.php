<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

requirePermission('manage_users');

$tenantId = getCurrentTenantId();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if (empty($name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    if (!in_array($role, getValidRoles())) $errors[] = 'Invalid role selected';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND tenant_id = ? AND deleted_at IS NULL');
            $stmt->execute([$email, $tenantId]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists for this school';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('
                    INSERT INTO users (tenant_id, name, email, password_hash, role, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $tenantId,
                    $name,
                    $email,
                    $passwordHash,
                    $role,
                    $status
                ]);
                $success = true;
                header('Location: users.php?added=1');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to add user. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Dashboard</title>
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
                        <h1 class="text-2xl font-bold">Add New User</h1>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Create a new system user with role assignment</p>
                    </div>
                    <a href="users.php" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                        ← Back to Users
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
                        <!-- Personal Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Personal Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium mb-2">Full Name *</label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                        placeholder="John Doe"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium mb-2">Email *</label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        required
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        placeholder="john@school.edu"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Security</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium mb-2">Password *</label>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        required
                                        placeholder="••••••••"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Minimum 8 characters</p>
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium mb-2">Confirm Password *</label>
                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        required
                                        placeholder="••••••••"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Role & Status -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Access Control</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="role" class="block text-sm font-medium mb-2">Role *</label>
                                    <select
                                        id="role"
                                        name="role"
                                        required
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                        <option value="">Select a role</option>
                                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                        <option value="staff" <?php echo ($_POST['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="teacher" <?php echo ($_POST['role'] ?? '') === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                        <option value="student" <?php echo ($_POST['role'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                                    </select>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Choose user role to define permissions</p>
                                </div>
                                <div>
                                    <label for="status" class="block text-sm font-medium mb-2">Status</label>
                                    <select
                                        id="status"
                                        name="status"
                                        class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none transition"
                                    >
                                        <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo ($_POST['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Role Description -->
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                            <p class="text-sm font-semibold mb-3">Role Permissions:</p>
                            <div id="roleDescription" class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                                <p>Select a role to view permissions</p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-4 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="submit"
                                class="px-6 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition"
                            >
                                Add User
                            </button>
                            <a
                                href="users.php"
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
        const roleDescriptions = {
            admin: ['Full system access', 'Manage users and roles', 'Configure school settings', 'View all analytics', 'Manage all students and courses'],
            staff: ['Manage student records', 'Create and manage courses', 'Manage course requirements', 'View analytics'],
            teacher: ['View assigned students', 'Create assignments and exams', 'Grade student work', 'View class analytics'],
            student: ['View own profile', 'View enrolled courses', 'Submit assignments', 'View grades']
        };

        const roleSelect = document.getElementById('role');
        const roleDescription = document.getElementById('roleDescription');

        roleSelect.addEventListener('change', function() {
            const selected = this.value;
            if (selected && roleDescriptions[selected]) {
                roleDescription.innerHTML = roleDescriptions[selected]
                    .map(perm => `<p>✓ ${perm}</p>`)
                    .join('');
            } else {
                roleDescription.innerHTML = '<p>Select a role to view permissions</p>';
            }
        });

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
