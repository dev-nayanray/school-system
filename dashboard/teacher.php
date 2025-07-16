<?php
require_once '../includes/auth.php';
require_role('teacher');
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch subjects assigned to teacher
$stmt = $pdo->prepare('
    SELECT s.subject_name, c.class_name
    FROM subjects s
    JOIN teacher_profiles tp ON tp.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE tp.user_id = ?
');
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

// Fetch recent marks entered by teacher (last 5)
$stmt = $pdo->prepare('
    SELECT m.marks_obtained, m.exam_type, u.name as student_name, s.subject_name
    FROM marks m
    JOIN student_profiles sp ON m.student_id = sp.id
    JOIN users u ON sp.user_id = u.id
    JOIN subjects s ON m.subject_id = s.id
    JOIN teacher_profiles tp ON tp.subject_id = s.id
    WHERE tp.user_id = ?
    ORDER BY m.id DESC
    LIMIT 5
');
$stmt->execute([$user_id]);
$recent_marks = $stmt->fetchAll();

// Fetch announcements for teacher
$stmt = $pdo->prepare("SELECT a.title, a.content, a.created_at FROM announcements a WHERE a.role_target IN ('all', 'teacher') ORDER BY a.created_at DESC LIMIT 5");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-5xl">
        <h2 class="text-3xl font-bold mb-8 text-gray-800">Teacher Dashboard</h2>
        <p class="mb-8 text-lg text-gray-700">Welcome, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['name']); ?></span>! You are logged in as a Teacher.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-blue-100 rounded-lg p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4">My Subjects</h3>
                <?php if (empty($subjects)): ?>
                    <p>No subjects assigned.</p>
                <?php else: ?>
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($subjects as $subject): ?>
                            <li class="text-gray-800 font-medium"><?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['class_name'] . ')'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="bg-green-100 rounded-lg p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4">Recent Marks Entered</h3>
                <?php if (empty($recent_marks)): ?>
                    <p>No marks entered yet.</p>
                <?php else: ?>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        <?php foreach ($recent_marks as $mark): ?>
                            <li class="text-gray-700">
                                <?php echo htmlspecialchars($mark['student_name']); ?> - <?php echo htmlspecialchars($mark['subject_name']); ?> - <?php echo htmlspecialchars($mark['exam_type']); ?>: <span class="font-semibold"><?php echo htmlspecialchars($mark['marks_obtained']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="bg-yellow-100 rounded-lg p-6 shadow-lg">
                <h3 class="text-xl font-semibold mb-4">Announcements</h3>
                <?php if (empty($announcements)): ?>
                    <p>No announcements.</p>
                <?php else: ?>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        <?php foreach ($announcements as $announcement): ?>
                            <li class="text-gray-700">
                                <strong><?php echo htmlspecialchars($announcement['title']); ?></strong><br />
                                <small class="text-gray-600"><?php echo htmlspecialchars(date('M d, Y', strtotime($announcement['created_at']))); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
