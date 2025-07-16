<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

// Handle delete user request
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($delete_id !== $_SESSION['user_id']) { // Prevent deleting self
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$delete_id]);
        header('Location: admin_manage_users.php');
        exit();
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Fetch all users
$stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-5xl">
        <h2 class="text-2xl font-semibold mb-4">Manage Users</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <a href="admin_edit_user.php" class="inline-block mb-4 bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Add New User</a>

        <table class="w-full border border-gray-300 rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Email</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Role</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Created At</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td class="border border-gray-300 px-4 py-2">
                            <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="admin_manage_users.php?delete=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');" class="text-red-600 hover:underline">Delete</a>
                            <?php else: ?>
                                <span class="text-gray-400">Delete</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
