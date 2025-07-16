<?php
require_once '../includes/auth.php';
require_role('teacher');
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user and teacher profile data
$stmt = $pdo->prepare('
    SELECT u.name, u.email, s.subject_id, sub.subject_name
    FROM users u
    LEFT JOIN teacher_profiles s ON s.user_id = u.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    WHERE u.id = ?
');
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Fetch all subjects for dropdown
$stmt = $pdo->query('SELECT * FROM subjects ORDER BY subject_name ASC');
$subjects = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject_id = $_POST['subject_id'] ?? null;

    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (!$subject_id) {
        $errors[] = 'Subject is required.';
    }

    if (empty($errors)) {
        // Update users table
        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->execute([$name, $email, $user_id]);

        // Check if teacher profile exists
        $stmt = $pdo->prepare('SELECT id FROM teacher_profiles WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $teacher_profile = $stmt->fetch();

        if ($teacher_profile) {
            // Update teacher profile
            $stmt = $pdo->prepare('UPDATE teacher_profiles SET subject_id = ? WHERE user_id = ?');
            $stmt->execute([$subject_id, $user_id]);
        } else {
            // Insert teacher profile
            $stmt = $pdo->prepare('INSERT INTO teacher_profiles (user_id, subject_id) VALUES (?, ?)');
            $stmt->execute([$user_id, $subject_id]);
        }

        $success = 'Profile updated successfully.';
        // Refresh profile data
        $stmt = $pdo->prepare('
            SELECT u.name, u.email, s.subject_id, sub.subject_name
            FROM users u
            LEFT JOIN teacher_profiles s ON s.user_id = u.id
            LEFT JOIN subjects sub ON s.subject_id = sub.id
            WHERE u.id = ?
        ');
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-md">
        <h2 class="text-2xl font-semibold mb-4">My Profile</h2>

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

        <form action="teacher_profile.php" method="post" class="space-y-4">
            <div>
                <label for="name" class="block mb-1 font-semibold">Name</label>
                <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" />
            </div>
            <div>
                <label for="email" class="block mb-1 font-semibold">Email</label>
                <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" />
            </div>
            <div>
                <label for="subject_id" class="block mb-1 font-semibold">Subject</label>
                <select id="subject_id" name="subject_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Subject</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php if (($profile['subject_id'] ?? '') == $subject['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Update Profile</button>
        </form>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
