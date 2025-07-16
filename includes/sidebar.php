<?php
if (!isset($_SESSION)) {
    session_start();
}

$role = $_SESSION['role'] ?? 'user';

$menu_items = [];

switch ($role) {
    case 'admin':
        $menu_items = [
            'Dashboard' => '../dashboard/admin.php',
            'Manage Users' => '../dashboard/admin_manage_users.php',
            'Classes' => '../dashboard/admin_classes.php',
            'Subjects' => '../dashboard/admin_subjects.php',
            'Announcements' => '../dashboard/admin_announcements.php',
        ];
        break;
    case 'teacher':
        $menu_items = [
            'Dashboard' => '../dashboard/teacher.php',
            'Marks Entry' => '../dashboard/teacher_marks.php',
            'Profile' => '../dashboard/teacher_profile.php',
            'Announcements' => '../dashboard/announcements.php',
        ];
        break;
    case 'student':
        $menu_items = [
            'Dashboard' => '../dashboard/student.php',
            'Marks' => '../dashboard/student_marks.php',
            'Profile' => '../dashboard/student_profile.php',
            'Announcements' => '../dashboard/announcements.php',
        ];
        break;
    default:
        $menu_items = [
            'Dashboard' => '../dashboard/user.php',
            'Announcements' => '../dashboard/announcements.php',
        ];
        break;
}
?>

<!-- Sidebar Component -->
<aside class="w-full md:w-64 h-screen md:h-auto bg-gradient-to-b from-blue-600 to-blue-800 text-white shadow-xl rounded-lg p-4 transition-all">
    <nav>
        <!-- Search Field -->
        <div class="mb-4">
            <input 
                type="text" 
                id="sidebar-search" 
                placeholder="Search menu..." 
                class="w-full px-4 py-2 text-sm text-gray-700 rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
            />
        </div>

        <!-- Menu Items -->
        <ul id="sidebar-menu" class="space-y-2">
            <?php foreach ($menu_items as $name => $link): ?>
                <li>
                    <a href="<?= htmlspecialchars($link) ?>" 
                       class="flex items-center gap-3 px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition duration-200">
                        <svg class="w-4 h-4 text-white opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-sm"><?= htmlspecialchars($name) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
