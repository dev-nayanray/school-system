<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_fees'])) {
    try {
        // Fetch all fees with class_id
        $fees_with_class = $pdo->query('SELECT id, class_id, amount FROM fees WHERE class_id IS NOT NULL')->fetchAll();

        $assigned_count = 0;
        foreach ($fees_with_class as $fee) {
            // Find students in the class
            $stmt = $pdo->prepare('SELECT id FROM student_profiles WHERE class_id = ?');
            $stmt->execute([$fee['class_id']]);
            $students = $stmt->fetchAll();

            foreach ($students as $student) {
                // Check if fee already assigned
                $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM student_fees WHERE student_id = ? AND fee_id = ?');
                $stmt_check->execute([$student['id'], $fee['id']]);
                $exists = $stmt_check->fetchColumn();

                if (!$exists) {
                    // Assign fee to student
                    $stmt_insert = $pdo->prepare('INSERT INTO student_fees (student_id, fee_id, amount_paid, payment_status) VALUES (?, ?, 0, "pending")');
                    $stmt_insert->execute([$student['id'], $fee['id']]);
                    $assigned_count++;
                }
            }
        }
        $success = "Assigned fees to students: $assigned_count new assignments created.";
    } catch (PDOException $e) {
        $errors[] = 'Error assigning fees: ' . $e->getMessage();
    }
}

// Handle add/edit fee type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_fee_type'])) {
    $fee_type_id = $_POST['fee_type_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$name) {
        $errors[] = 'Fee type name is required.';
    }

    if (empty($errors)) {
        try {
            if ($fee_type_id) {
                $stmt = $pdo->prepare('UPDATE fee_types SET name = ?, description = ? WHERE id = ?');
                $stmt->execute([$name, $description, $fee_type_id]);
                $success = 'Fee type updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO fee_types (name, description) VALUES (?, ?)');
                $stmt->execute([$name, $description]);
                $success = 'Fee type added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle add/edit fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_fee'])) {
    $fee_id = $_POST['fee_id'] ?? null;
    $fee_type_id = $_POST['fee_type_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $due_date = $_POST['due_date'] ?? null;

    if (!$fee_type_id || !$amount) {
        $errors[] = 'Fee type and amount are required.';
    }

    if (empty($errors)) {
        try {
            if ($fee_id) {
                $stmt = $pdo->prepare('UPDATE fees SET fee_type_id = ?, class_id = ?, amount = ?, due_date = ? WHERE id = ?');
                $stmt->execute([$fee_type_id, $class_id ?: null, $amount, $due_date ?: null, $fee_id]);
                $success = 'Fee updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO fees (fee_type_id, class_id, amount, due_date) VALUES (?, ?, ?, ?)');
                $stmt->execute([$fee_type_id, $class_id ?: null, $amount, $due_date ?: null]);
                $success = 'Fee added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch fee types and fees
$fee_types = $pdo->query('SELECT * FROM fee_types ORDER BY name ASC')->fetchAll();
$classes = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC')->fetchAll();
$fees = $pdo->query('SELECT fees.*, fee_types.name as fee_type_name, classes.class_name FROM fees LEFT JOIN fee_types ON fees.fee_type_id = fee_types.id LEFT JOIN classes ON fees.class_id = classes.id ORDER BY fee_type_name ASC')->fetchAll();

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Fee Management</h1>

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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Fee Types Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Add / Edit Fee Type</h2>
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="fee_type_id" value="<?php echo htmlspecialchars($_POST['fee_type_id'] ?? ''); ?>">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" class="w-full border border-gray-300 rounded-lg px-4 py-2"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="submit_fee_type" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Fee Type</button>
                    </form>
                </div>

                <!-- Fees Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Add / Edit Fee</h2>
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="fee_id" value="<?php echo htmlspecialchars($_POST['fee_id'] ?? ''); ?>">
                        <div>
                            <label for="fee_type_id" class="block text-sm font-medium text-gray-700 mb-1">Fee Type</label>
                            <select id="fee_type_id" name="fee_type_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Fee Type</option>
                                <?php foreach ($fee_types as $ft): ?>
                                    <option value="<?php echo $ft['id']; ?>" <?php echo (($_POST['fee_type_id'] ?? '') == $ft['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ft['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class (optional)</label>
                            <select id="class_id" name="class_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo (($_POST['class_id'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input type="number" step="0.01" id="amount" name="amount" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date (optional)</label>
                            <input type="date" id="due_date" name="due_date" class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="submit_fee" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Fee</button>
            </form>
                </div>
            </div>

            <form method="post" class="mt-6">
                <button type="submit" name="assign_fees" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Assign Fees to Students by Class</button>
            </form>

            <!-- Fees List -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">All Fees</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fees as $fee): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($fee['fee_type_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($fee['class_name'] ?? 'All Classes'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($fee['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($fee['due_date'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
