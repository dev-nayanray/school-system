<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';
$id = $_GET['id'] ?? null;
$user = null;

if ($id) {
    // Fetch user data for editing
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: admin_manage_users.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    $valid_roles = ['admin', 'teacher', 'student', 'user'];
    if (!in_array($role, $valid_roles)) {
        $errors[] = 'Invalid role selected.';
    }
    if ($id === null && !$password) {
        $errors[] = 'Password is required for new users.';
    }
    if ($password && $password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check if email is unique
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $id ?? 0]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already registered to another user.';
        } else {
            if ($id) {
                // Update user
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?');
                    $stmt->execute([$name, $email, $role, $hashed_password, $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
                    $stmt->execute([$name, $email, $role, $id]);
                }
                $success = 'User updated successfully.';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashed_password, $role]);
                $success = 'User added successfully.';
                // Clear form after adding
                $name = $email = $role = '';
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-md">
        <h2 class="text-2xl font-semibold mb-4"><?php echo $id ? 'Edit User' : 'Add New User'; ?></h2>

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

        <form action="<?php echo $id ? 'admin_edit_user.php?id=' . $id : 'admin_edit_user.php'; ?>" method="post" class="space-y-4">
            <div>
                <label for="name" class="block mb-1 font-semibold">Name</label>
                <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name'] ?? ''); ?>" />
            </div>
            <div>
                <label for="email" class="block mb-1 font-semibold">Email</label>
                <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email'] ?? ''); ?>" />
            </div>
            <div>
                <label for="role" class="block mb-1 font-semibold">Role</label>
                <select id="role" name="role" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php
                    $roles = ['admin', 'teacher', 'student', 'user'];
                    $selected_role = $_POST['role'] ?? $user['role'] ?? 'user';
                    foreach ($roles as $r) {
                        $selected = ($r === $selected_role) ? 'selected' : '';
                        echo "<option value=\"$r\" $selected>" . ucfirst($r) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="password" class="block mb-1 font-semibold">Password <?php echo $id ? '(leave blank to keep current password)' : ''; ?></label>
                <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label for="confirm_password" class="block mb-1 font-semibold">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition"><?php echo $id ? 'Update User' : 'Add User'; ?></button>
            <a href="admin_manage_users.php" class="ml-4 text-gray-600 hover:underline">Cancel</a>
        </form>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
