<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle delete class request
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
    $stmt->execute([$delete_id]);
    header('Location: admin_classes.php');
    exit();
}

// Handle add/edit class form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = trim($_POST['class_name'] ?? '');
    $class_id = $_POST['class_id'] ?? null;

    if (!$class_name) {
        $errors[] = 'Class name is required.';
    }

    if (empty($errors)) {
        if ($class_id) {
            // Update class
            $stmt = $pdo->prepare('UPDATE classes SET class_name = ? WHERE id = ?');
            $stmt->execute([$class_name, $class_id]);
            $success = 'Class updated successfully.';
        } else {
            // Insert new class
            $stmt = $pdo->prepare('INSERT INTO classes (class_name) VALUES (?)');
            $stmt->execute([$class_name]);
            $success = 'Class added successfully.';
        }
    }
}

// Fetch all classes
$stmt = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC');
$classes = $stmt->fetchAll();

// If editing, fetch class data
$edit_class = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_class = $stmt->fetch();
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-3xl">
        <h2 class="text-2xl font-semibold mb-4">Manage Classes</h2>

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

        <form action="admin_classes.php" method="post" class="mb-6 space-y-4">
            <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($edit_class['id'] ?? ''); ?>" />
            <div>
                <label for="class_name" class="block mb-1 font-semibold">Class Name</label>
                <input type="text" id="class_name" name="class_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($edit_class['class_name'] ?? ''); ?>" />
            </div>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                <?php echo $edit_class ? 'Update Class' : 'Add Class'; ?>
            </button>
            <?php if ($edit_class): ?>
                <a href="admin_classes.php" class="ml-4 text-gray-600 hover:underline">Cancel</a>
            <?php endif; ?>
        </form>

        <table class="w-full border border-gray-300 rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">Class Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td class="border border-gray-300 px-4 py-2">
                            <a href="admin_classes.php?edit=<?php echo $class['id']; ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
                            <a href="admin_classes.php?delete=<?php echo $class['id']; ?>" onclick="return confirm('Are you sure you want to delete this class?');" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
