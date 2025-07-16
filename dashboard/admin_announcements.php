<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $role_target = $_POST['role_target'] ?? 'all';

    if (!$title) {
        $errors[] = 'Title is required.';
    }
    if (!$content) {
        $errors[] = 'Content is required.';
    }
    $valid_roles = ['all', 'admin', 'teacher', 'student', 'user'];
    if (!in_array($role_target, $valid_roles)) {
        $errors[] = 'Invalid role target selected.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO announcements (title, content, posted_by, role_target) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $content, $_SESSION['user_id'], $role_target]);
        $success = 'Announcement posted successfully.';
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-3xl">
        <h2 class="text-2xl font-semibold mb-4">Post New Announcement</h2>

        <?php if ($errors): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="admin_announcements.php" method="post" class="space-y-4">
            <div>
                <label for="title" class="block mb-1 font-semibold">Title</label>
                <input type="text" id="title" name="title" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" />
            </div>
            <div>
                <label for="content" class="block mb-1 font-semibold">Content</label>
                <textarea id="content" name="content" rows="5" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="role_target" class="block mb-1 font-semibold">Target Role</label>
                <select id="role_target" name="role_target" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all" <?php if (($_POST['role_target'] ?? '') === 'all') echo 'selected'; ?>>All</option>
                    <option value="admin" <?php if (($_POST['role_target'] ?? '') === 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="teacher" <?php if (($_POST['role_target'] ?? '') === 'teacher') echo 'selected'; ?>>Teacher</option>
                    <option value="student" <?php if (($_POST['role_target'] ?? '') === 'student') echo 'selected'; ?>>Student</option>
                    <option value="user" <?php if (($_POST['role_target'] ?? '') === 'user') echo 'selected'; ?>>User</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Post Announcement</button>
        </form>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
