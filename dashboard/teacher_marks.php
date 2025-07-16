<?php
require_once '../includes/auth.php';
require_role('teacher');
require_once '../includes/db.php';

$teacher_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch subjects assigned to this teacher
$stmt = $pdo->prepare('
    SELECT s.id, s.subject_name, c.class_name
    FROM subjects s
    JOIN teacher_profiles tp ON tp.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE tp.user_id = ?
');
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll();

// Handle form submission to add/update marks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $marks_obtained = $_POST['marks_obtained'] ?? '';
    $exam_type = trim($_POST['exam_type'] ?? '');

    if (!$student_id || !$subject_id || $marks_obtained === '' || !$exam_type) {
        $errors[] = 'All fields are required.';
    } elseif (!is_numeric($marks_obtained) || $marks_obtained < 0) {
        $errors[] = 'Marks obtained must be a non-negative number.';
    } else {
        // Check if marks record exists for this student, subject, and exam_type
        $stmt = $pdo->prepare('SELECT id FROM marks WHERE student_id = ? AND subject_id = ? AND exam_type = ?');
        $stmt->execute([$student_id, $subject_id, $exam_type]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing marks
            $stmt = $pdo->prepare('UPDATE marks SET marks_obtained = ? WHERE id = ?');
            $stmt->execute([$marks_obtained, $existing['id']]);
            $success = 'Marks updated successfully.';
        } else {
            // Insert new marks
            $stmt = $pdo->prepare('INSERT INTO marks (student_id, subject_id, marks_obtained, exam_type) VALUES (?, ?, ?, ?)');
            $stmt->execute([$student_id, $subject_id, $marks_obtained, $exam_type]);
            $success = 'Marks added successfully.';
        }
    }
}

// Fetch students for selected subject (if subject_id is selected)
$students = [];
$selected_subject_id = $_POST['subject_id'] ?? '';
if ($selected_subject_id) {
    // Get class_id for the subject
    $stmt = $pdo->prepare('SELECT class_id FROM subjects WHERE id = ?');
    $stmt->execute([$selected_subject_id]);
    $class = $stmt->fetch();
    if ($class) {
        $class_id = $class['class_id'];
        // Get students in that class
        $stmt = $pdo->prepare('
            SELECT sp.id, u.name, sp.roll_number
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.id
            WHERE sp.class_id = ?
        ');
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-4xl">
        <h2 class="text-2xl font-semibold mb-4">Enter Marks</h2>

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

        <form action="teacher_marks.php" method="post" class="space-y-4">
            <div>
                <label for="subject_id" class="block mb-1 font-semibold">Subject</label>
                <select id="subject_id" name="subject_id" required onchange="this.form.submit()" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Subject</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php if ($selected_subject_id == $subject['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['class_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_subject_id): ?>
                <div>
                    <label for="student_id" class="block mb-1 font-semibold">Student</label>
                    <select id="student_id" name="student_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['name'] . ' (Roll: ' . $student['roll_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="marks_obtained" class="block mb-1 font-semibold">Marks Obtained</label>
                    <input type="number" id="marks_obtained" name="marks_obtained" min="0" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div>
                    <label for="exam_type" class="block mb-1 font-semibold">Exam Type</label>
                    <input type="text" id="exam_type" name="exam_type" placeholder="e.g. Midterm, Final" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Submit Marks</button>
            <?php endif; ?>
        </form>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
