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
    $max_marks = $_POST['max_marks'] ?? 100;

    if (!$student_id || !$subject_id || $marks_obtained === '' || !$exam_type) {
        $errors[] = 'All fields are required.';
    } elseif (!is_numeric($marks_obtained) || $marks_obtained < 0) {
        $errors[] = 'Marks obtained must be a non-negative number.';
    } elseif (!is_numeric($max_marks) || $max_marks <= 0) {
        $errors[] = 'Max marks must be a positive number.';
    } else {
        // Check if marks record exists for this student, subject, and exam_type
        $stmt = $pdo->prepare('SELECT id FROM marks WHERE student_id = ? AND subject_id = ? AND exam_type = ?');
        $stmt->execute([$student_id, $subject_id, $exam_type]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing marks
            $stmt = $pdo->prepare('UPDATE marks SET marks_obtained = ?, max_marks = ? WHERE id = ?');
            $stmt->execute([$marks_obtained, $max_marks, $existing['id']]);
            $success = 'Marks updated successfully.';
        } else {
            // Insert new marks
            $stmt = $pdo->prepare('INSERT INTO marks (student_id, subject_id, marks_obtained, max_marks, exam_type) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$student_id, $subject_id, $marks_obtained, $max_marks, $exam_type]);
            $success = 'Marks added successfully.';
        }
    }
}

// Fetch students for selected subject (if subject_id is selected)
$students = [];
$selected_subject_id = $_POST['subject_id'] ?? '';
$class_id = null;
$class_name = '';
$subject_name = '';

if ($selected_subject_id) {
    // Get class_id and subject name for the subject
    $stmt = $pdo->prepare('SELECT s.subject_name, c.class_name, c.id AS class_id 
                            FROM subjects s 
                            JOIN classes c ON s.class_id = c.id 
                            WHERE s.id = ?');
    $stmt->execute([$selected_subject_id]);
    $subject_info = $stmt->fetch();
    
    if ($subject_info) {
        $class_id = $subject_info['class_id'];
        $class_name = $subject_info['class_name'];
        $subject_name = $subject_info['subject_name'];
        
        // Get students in that class
        $stmt = $pdo->prepare('
            SELECT sp.id, u.name, sp.roll_number
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.id
            WHERE sp.class_id = ?
            ORDER BY sp.roll_number ASC
        ');
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll();
    }
}

// Fetch recent marks entries for this teacher
$stmt = $pdo->prepare('
    SELECT m.*, u.name AS student_name, s.subject_name, c.class_name
    FROM marks m
    JOIN student_profiles sp ON m.student_id = sp.id
    JOIN users u ON sp.user_id = u.id
    JOIN subjects s ON m.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE s.id IN (SELECT subject_id FROM teacher_profiles WHERE user_id = ?)
    ORDER BY m.id DESC
    LIMIT 5
');
$stmt->execute([$teacher_id]);
$recent_entries = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Student Marks Management</h1>
                <div class="flex items-center space-x-1 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Enter Marks</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Marks Entry Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600">
                            <h2 class="text-xl font-semibold text-white">Enter Student Marks</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($errors): ?>
                                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                                            <div class="mt-2 text-sm text-red-700">
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <?php foreach ($errors as $error): ?>
                                                        <li><?php echo htmlspecialchars($error); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($success); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form action="teacher_marks.php" method="post" class="space-y-6">
                                <div>
                                    <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                    <div class="relative">
                                        <select id="subject_id" name="subject_id" required onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white bg-select-arrow bg-no-repeat bg-right pr-10">
                                            <option value="">Select Subject</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo $subject['id']; ?>" <?php if ($selected_subject_id == $subject['id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($subject['subject_name'] . ' - ' . $subject['class_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($selected_subject_id): ?>
                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                                            </svg>
                                            <span class="text-sm font-medium text-blue-800">
                                                <?= htmlspecialchars($subject_name) ?> - <?= htmlspecialchars($class_name) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                                        <div class="relative">
                                            <select id="student_id" name="student_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white bg-select-arrow bg-no-repeat bg-right pr-10">
                                                <option value="">Select Student</option>
                                                <?php foreach ($students as $student): ?>
                                                    <option value="<?php echo $student['id']; ?>">
                                                        <?php echo htmlspecialchars($student['name'] . ' (Roll: ' . $student['roll_number'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="marks_obtained" class="block text-sm font-medium text-gray-700 mb-1">Marks Obtained</label>
                                            <div class="relative">
                                                <input type="number" id="marks_obtained" name="marks_obtained" min="0" step="0.01" required 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" 
                                                       placeholder="Enter marks" />
                                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M9.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="max_marks" class="block text-sm font-medium text-gray-700 mb-1">Max Marks</label>
                                            <div class="relative">
                                                <input type="number" id="max_marks" name="max_marks" min="1" value="100" required 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" 
                                                       placeholder="Max marks" />
                                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="exam_type" class="block text-sm font-medium text-gray-700 mb-1">Exam Type</label>
                                        <div class="relative">
                                            <input type="text" id="exam_type" name="exam_type" required 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" 
                                                   placeholder="e.g., Midterm, Final, Quiz 1" />
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Examples: Midterm Exam, Final Exam, Quiz 1, Assignment 2
                                        </p>
                                    </div>

                                    <div class="pt-4">
                                        <button type="submit" class="w-full px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium transition transform hover:-translate-y-0.5">
                                            <div class="flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                Submit Marks
                                            </div>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Entries & Subjects -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Recent Entries -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-gray-700 to-gray-900">
                            <h2 class="text-xl font-semibold text-white">Recent Entries</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($recent_entries)): ?>
                                <div class="text-center py-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900">No recent entries</h3>
                                    <p class="mt-1 text-sm text-gray-500">Your recent marks entries will appear here</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
                                    <?php foreach ($recent_entries as $entry): 
                                        $percentage = ($entry['marks_obtained'] / $entry['max_marks']) * 100;
                                        $percentage = round($percentage, 1);
                                    ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex justify-between">
                                                <div>
                                                    <h3 class="font-medium text-gray-900"><?= htmlspecialchars($entry['student_name']) ?></h3>
                                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($entry['subject_name']) ?> - <?= htmlspecialchars($entry['class_name']) ?></p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-lg font-bold text-indigo-600"><?= $entry['marks_obtained'] ?></span>
                                                    <span class="text-gray-500">/<?= $entry['max_marks'] ?></span>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <div class="flex justify-between text-xs text-gray-500">
                                                    <span><?= $percentage ?>%</span>
                                                    <span><?= htmlspecialchars($entry['exam_type']) ?></span>
                                                </div>
                                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percentage, 100) ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-xs text-gray-500">
                                                <?= isset($entry['updated_at']) ? date('M d, Y', strtotime($entry['updated_at'])) : '' ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Assigned Subjects -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                            <h2 class="text-xl font-semibold text-white">Your Subjects</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($subjects)): ?>
                                <div class="text-center py-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900">No subjects assigned</h3>
                                    <p class="mt-1 text-sm text-gray-500">Contact administrator to get assigned to subjects</p>
                                </div>
                            <?php else: ?>
                                <ul class="space-y-3">
                                    <?php foreach ($subjects as $subject): ?>
                                        <li class="flex items-start">
                                            <span class="flex-shrink-0 h-5 w-5 text-indigo-500 mt-0.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="ml-3 text-gray-700">
                                                <span class="font-medium"><?= htmlspecialchars($subject['subject_name']) ?></span>
                                                <span class="text-sm block text-gray-500"><?= htmlspecialchars($subject['class_name']) ?></span>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Auto-submit form when subject changes with loading indicator
    document.getElementById('subject_id').addEventListener('change', function() {
        if (this.value) {
            // Create and show loading indicator
            const form = this.form;
            const loader = document.createElement('div');
            loader.className = 'fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50';
            loader.innerHTML = `
                <div class="bg-white rounded-lg p-6 flex flex-col items-center">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
                    <p>Loading students...</p>
                </div>
            `;
            document.body.appendChild(loader);
            
            // Submit form
            form.submit();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>