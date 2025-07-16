<?php
require_once '../includes/auth.php';
require_role('user');
require_once '../includes/db.php';

// Fetch announcements for user
$stmt = $pdo->prepare("SELECT a.title, a.content, a.created_at FROM announcements a WHERE a.role_target IN ('all', 'user') ORDER BY a.created_at DESC LIMIT 5");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="flex">
    <?php include '../includes/sidebar.php'; ?>

    <section class="flex-grow p-6 bg-white rounded shadow ml-6 max-w-4xl">
        <h2 class="text-3xl font-bold mb-8 text-gray-800">User Dashboard</h2>
        <p class="mb-8 text-lg text-gray-700">Welcome, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['name']); ?></span>! You are logged in as a User.</p>

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
    </section>
</div>

<?php include '../includes/footer.php'; ?>
