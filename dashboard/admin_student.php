<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle add/edit student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $student_id = $_POST['student_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $class_id = $_POST['class_id'] ?? null;
    $roll_number = trim($_POST['roll_number'] ?? '');

    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email) {
        $errors[] = 'Email is required.';
    }
    if (!$student_id && !$password) {
        $errors[] = 'Password is required for new student.';
    }
    if (!$class_id) {
        $errors[] = 'Class is required.';
    }

    if (empty($errors)) {
        try {
            if ($student_id) {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = "student"');
                    $stmt->execute([$name, $email, $hashed_password, $student_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = "student"');
                    $stmt->execute([$name, $email, $student_id]);
                }
                // Update student_profiles
                $stmt = $pdo->prepare('UPDATE student_profiles SET class_id = ?, roll_number = ? WHERE user_id = ?');
                $stmt->execute([$class_id, $roll_number, $student_id]);
                $success = 'Student updated successfully.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "student")');
                $stmt->execute([$name, $email, $hashed_password]);
                $new_user_id = $pdo->lastInsertId();
                // Insert into student_profiles
                $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, class_id, roll_number) VALUES (?, ?, ?)');
                $stmt->execute([$new_user_id, $class_id, $roll_number]);
                $success = 'Student added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle delete student
if (isset($_POST['delete'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "student"');
        $stmt->execute([$delete_id]);
        $success = 'Student deleted successfully.';
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Fetch all students with profile info
$stmt = $pdo->query('SELECT u.id, u.name, u.email, sp.class_id, sp.roll_number, c.class_name FROM users u LEFT JOIN student_profiles sp ON u.id = sp.user_id LEFT JOIN classes c ON sp.class_id = c.id WHERE u.role = "student" ORDER BY u.name ASC');
$students = $stmt->fetchAll();

// Fetch classes for dropdown
$classes = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC')->fetchAll();

// If editing, fetch student data
$edit_student = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, sp.class_id, sp.roll_number FROM users u LEFT JOIN student_profiles sp ON u.id = sp.user_id WHERE u.id = ? AND u.role = "student"');
    $stmt->execute([$edit_id]);
    $edit_student = $stmt->fetch();
}

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Student Management</h1>

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
                <h2 class="text-xl font-semibold mb-4"><?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?></h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($edit_student['id'] ?? ''); ?>">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($edit_student['name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($edit_student['email'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <?php echo $edit_student ? '(Leave blank to keep current password)' : ''; ?></label>
                        <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select id="class_id" name="class_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo (($_POST['class_id'] ?? $edit_student['class_id'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="roll_number" class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                        <input type="text" id="roll_number" name="roll_number" class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($edit_student['roll_number'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><?php echo $edit_student ? 'Update Student' : 'Add Student'; ?></button>
                    <?php if ($edit_student): ?>
                        <a href="admin_student.php" class="ml-4 text-gray-600 hover:text-gray-900">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">All Students</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll Number</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($student['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($student['class_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="admin_student.php?edit=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                    <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $student['id']; ?>">
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
