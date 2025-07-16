<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle delete subject request
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = ?');
    $stmt->execute([$delete_id]);
    header('Location: admin_subjects.php');
    exit();
}

// Handle add/edit subject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $class_id = $_POST['class_id'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;

    if (!$subject_name) {
        $errors[] = 'Subject name is required.';
    }
    if (!$class_id) {
        $errors[] = 'Class is required.';
    }

    if (empty($errors)) {
        if ($subject_id) {
            // Update subject
            $stmt = $pdo->prepare('UPDATE subjects SET subject_name = ?, class_id = ? WHERE id = ?');
            $stmt->execute([$subject_name, $class_id, $subject_id]);
            $success = 'Subject updated successfully.';
        } else {
            // Insert new subject
            $stmt = $pdo->prepare('INSERT INTO subjects (subject_name, class_id) VALUES (?, ?)');
            $stmt->execute([$subject_name, $class_id]);
            $success = 'Subject added successfully.';
        }
    }
}

// Fetch all subjects with class names
$stmt = $pdo->query('
    SELECT s.id, s.subject_name, c.class_name
    FROM subjects s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.subject_name
');
$subjects = $stmt->fetchAll();

// Fetch all classes for dropdown
$stmt = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC');
$classes = $stmt->fetchAll();

// If editing, fetch subject data
$edit_subject = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_subject = $stmt->fetch();
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-4xl">
        <h2 class="text-2xl font-semibold mb-4">Manage Subjects</h2>

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

        <form action="admin_subjects.php" method="post" class="mb-6 space-y-4">
            <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($edit_subject['id'] ?? ''); ?>" />
            <div>
                <label for="subject_name" class="block mb-1 font-semibold">Subject Name</label>
                <input type="text" id="subject_name" name="subject_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($edit_subject['subject_name'] ?? ''); ?>" />
            </div>
            <div>
                <label for="class_id" class="block mb-1 font-semibold">Class</label>
                <select id="class_id" name="class_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php if (($edit_subject['class_id'] ?? '') == $class['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                <?php echo $edit_subject ? 'Update Subject' : 'Add Subject'; ?>
            </button>
            <?php if ($edit_subject): ?>
                <a href="admin_subjects.php" class="ml-4 text-gray-600 hover:underline">Cancel</a>
            <?php endif; ?>
        </form>

        <table class="w-full border border-gray-300 rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">Subject Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Class</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($subject['class_name']); ?></td>
                        <td class="border border-gray-300 px-4 py-2">
                            <a href="admin_subjects.php?edit=<?php echo $subject['id']; ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
                            <a href="admin_subjects.php?delete=<?php echo $subject['id']; ?>" onclick="return confirm('Are you sure you want to delete this subject?');" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
