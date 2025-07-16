<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle add/edit exam result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $result_id = $_POST['result_id'] ?? null;
    $exam_id = $_POST['exam_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;
    $marks_obtained = $_POST['marks_obtained'] ?? null;

    if (!$exam_id || !$student_id || !$subject_id || $marks_obtained === null) {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        try {
            if ($result_id) {
                $stmt = $pdo->prepare('UPDATE exam_results SET exam_id = ?, student_id = ?, subject_id = ?, marks_obtained = ? WHERE id = ?');
                $stmt->execute([$exam_id, $student_id, $subject_id, $marks_obtained, $result_id]);
                $success = 'Exam result updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO exam_results (exam_id, student_id, subject_id, marks_obtained) VALUES (?, ?, ?, ?)');
                $stmt->execute([$exam_id, $student_id, $subject_id, $marks_obtained]);
                $success = 'Exam result added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle delete exam result
if (isset($_POST['delete'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM exam_results WHERE id = ?');
        $stmt->execute([$delete_id]);
        $success = 'Exam result deleted successfully.';
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Fetch exams, students, subjects, and results
$exams = $pdo->query('SELECT * FROM exams ORDER BY start_date DESC')->fetchAll();
$students = $pdo->query('SELECT u.id, u.name FROM users u WHERE u.role = "student" ORDER BY u.name ASC')->fetchAll();
$subjects = $pdo->query('SELECT * FROM subjects ORDER BY subject_name ASC')->fetchAll();
$results = $pdo->query('SELECT er.*, e.exam_name, u.name as student_name, s.subject_name FROM exam_results er JOIN exams e ON er.exam_id = e.id JOIN users u ON er.student_id = u.id JOIN subjects s ON er.subject_id = s.id ORDER BY e.start_date DESC')->fetchAll();

// If editing, fetch result data
$edit_result = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM exam_results WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_result = $stmt->fetch();
}

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Result Management</h1>

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
                <h2 class="text-xl font-semibold mb-4"><?php echo $edit_result ? 'Edit Exam Result' : 'Add New Exam Result'; ?></h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="result_id" value="<?php echo htmlspecialchars($edit_result['id'] ?? ''); ?>">
                    <div>
                        <label for="exam_id" class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                        <select id="exam_id" name="exam_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Select Exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?php echo $exam['id']; ?>" <?php echo (($_POST['exam_id'] ?? $edit_result['exam_id'] ?? '') == $exam['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                        <select id="student_id" name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo (($_POST['student_id'] ?? $edit_result['student_id'] ?? '') == $student['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <select id="subject_id" name="subject_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo (($_POST['subject_id'] ?? $edit_result['subject_id'] ?? '') == $subject['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="marks_obtained" class="block text-sm font-medium text-gray-700 mb-1">Marks Obtained</label>
                        <input type="number" id="marks_obtained" name="marks_obtained" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['marks_obtained'] ?? $edit_result['marks_obtained'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><?php echo $edit_result ? 'Update Result' : 'Add Result'; ?></button>
                    <?php if ($edit_result): ?>
                        <a href="admin_results.php" class="ml-4 text-gray-600 hover:text-gray-900">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">All Exam Results</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks Obtained</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($result['student_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($result['marks_obtained']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="admin_results.php?edit=<?php echo $result['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                    <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this result?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $result['id']; ?>">
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
