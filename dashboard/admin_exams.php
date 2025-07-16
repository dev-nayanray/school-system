<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle add/edit exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $exam_id = $_POST['exam_id'] ?? null;
    $exam_name = trim($_POST['exam_name'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (!$exam_name || !$start_date || !$end_date) {
        $errors[] = 'Exam name, start date, and end date are required.';
    }

    if (empty($errors)) {
        try {
            if ($exam_id) {
                $stmt = $pdo->prepare('UPDATE exams SET exam_name = ?, start_date = ?, end_date = ? WHERE id = ?');
                $stmt->execute([$exam_name, $start_date, $end_date, $exam_id]);
                $success = 'Exam updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO exams (exam_name, start_date, end_date) VALUES (?, ?, ?)');
                $stmt->execute([$exam_name, $start_date, $end_date]);
                $success = 'Exam added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle add/edit exam routine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_routine'])) {
    $routine_id = $_POST['routine_id'] ?? null;
    $exam_id = $_POST['exam_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;
    $exam_date = $_POST['exam_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (!$exam_id || !$class_id || !$subject_id || !$exam_date || !$start_time || !$end_time) {
        $errors[] = 'All routine fields are required.';
    }

    if (empty($errors)) {
        try {
            if ($routine_id) {
                $stmt = $pdo->prepare('UPDATE exam_routines SET exam_id = ?, class_id = ?, subject_id = ?, exam_date = ?, start_time = ?, end_time = ? WHERE id = ?');
                $stmt->execute([$exam_id, $class_id, $subject_id, $exam_date, $start_time, $end_time, $routine_id]);
                $success = 'Exam routine updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO exam_routines (exam_id, class_id, subject_id, exam_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$exam_id, $class_id, $subject_id, $exam_date, $start_time, $end_time]);
                $success = 'Exam routine added successfully.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch exams, classes, subjects, and routines
$exams = $pdo->query('SELECT * FROM exams ORDER BY start_date DESC')->fetchAll();
$classes = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC')->fetchAll();
$subjects = $pdo->query('SELECT * FROM subjects ORDER BY subject_name ASC')->fetchAll();
$routines = $pdo->query('SELECT er.*, e.exam_name, c.class_name, s.subject_name FROM exam_routines er JOIN exams e ON er.exam_id = e.id JOIN classes c ON er.class_id = c.id JOIN subjects s ON er.subject_id = s.id ORDER BY er.exam_date ASC')->fetchAll();

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Exam Management</h1>

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
                <!-- Exams Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Add / Edit Exam</h2>
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($_POST['exam_id'] ?? ''); ?>">
                        <div>
                            <label for="exam_name" class="block text-sm font-medium text-gray-700 mb-1">Exam Name</label>
                            <input type="text" id="exam_name" name="exam_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['exam_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" id="start_date" name="start_date" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" id="end_date" name="end_date" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="submit_exam" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Exam</button>
                    </form>
                </div>

                <!-- Exam Routines Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Add / Edit Exam Routine</h2>
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="routine_id" value="<?php echo htmlspecialchars($_POST['routine_id'] ?? ''); ?>">
                        <div>
                            <label for="exam_id" class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                            <select id="exam_id" name="exam_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Exam</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>" <?php echo (($_POST['exam_id'] ?? '') == $exam['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam['exam_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                            <select id="class_id" name="class_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo (($_POST['class_id'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                            <select id="subject_id" name="subject_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo (($_POST['subject_id'] ?? '') == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="exam_date" class="block text-sm font-medium text-gray-700 mb-1">Exam Date</label>
                            <input type="date" id="exam_date" name="exam_date" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['exam_date'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                            <input type="time" id="end_time" name="end_time" required class="w-full border border-gray-300 rounded-lg px-4 py-2" value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="submit_routine" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Exam Routine</button>
                    </form>
                </div>
            </div>

            <!-- Exam Routines List -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Exam Routines</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routines as $routine): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($routine['exam_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($routine['class_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($routine['subject_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($routine['exam_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($routine['start_time']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($routine['end_time']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
