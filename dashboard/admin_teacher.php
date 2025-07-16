<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle add/edit teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $teacher_id = $_POST['teacher_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email) {
        $errors[] = 'Email is required.';
    }
    if (!$teacher_id && !$password) {
        $errors[] = 'Password is required for new teacher.';
    }

    if (empty($errors)) {
        try {
            if ($teacher_id) {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = "teacher"');
                    $stmt->execute([$name, $email, $hashed_password, $teacher_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = "teacher"');
                    $stmt->execute([$name, $email, $teacher_id]);
                }
                $success = 'Teacher updated successfully.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "teacher")');
                $stmt->execute([$name, $email, $hashed_password]);
                $success = 'Teacher added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle delete teacher
if (isset($_POST['delete'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "teacher"');
        $stmt->execute([$delete_id]);
        $success = 'Teacher deleted successfully.';
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Fetch all teachers
$stmt = $pdo->query('SELECT * FROM users WHERE role = "teacher" ORDER BY name ASC');
$teachers = $stmt->fetchAll();

// If editing, fetch teacher data
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "teacher"');
    $stmt->execute([$edit_id]);
    $edit_teacher = $stmt->fetch();
}

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Teacher Management</h1>

            <?php if ($errors): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                    <ul class="list-disc pl-5 space-y-1 text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4"><?php echo $edit_teacher ? 'Edit Teacher' : 'Add New Teacher'; ?></h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($edit_teacher['id'] ?? ''); ?>">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($edit_teacher['name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($edit_teacher['email'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <?php echo $edit_teacher ? '(Leave blank to keep current password)' : ''; ?></label>
                        <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <button type="submit" name="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><?php echo $edit_teacher ? 'Update Teacher' : 'Add Teacher'; ?></button>
                    <?php if ($edit_teacher): ?>
                        <a href="admin_teacher.php" class="ml-4 text-gray-600 hover:text-gray-900">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">All Teachers</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($teacher['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($teacher['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="admin_teacher.php?edit=<?php echo $teacher['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                    <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $teacher['id']; ?>">
                                        <button type="submit" name="delete" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
