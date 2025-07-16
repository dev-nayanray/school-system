<?php
require_once '../includes/auth.php';
require_role('student');
require_once '../includes/db.php';

// Placeholder page for resources.

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Resources</h1>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <p class="text-gray-600 mb-4">This is the resources page. Useful study materials and links will be available here.</p>
                <ul class="list-disc list-inside text-blue-600">
                    <li><a href="#" class="hover:underline">Mathematics Study Guide (PDF)</a></li>
                    <li><a href="#" class="hover:underline">Physics Lecture Notes (PDF)</a></li>
                    <li><a href="#" class="hover:underline">Chemistry Lab Manual (PDF)</a></li>
                    <li><a href="#" class="hover:underline">History Timeline (PDF)</a></li>
                </ul>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
