<?php
require_once '../includes/auth.php';
require_role('teacher');
require_once '../includes/db.php';

// For now, this is a placeholder page for teacher schedule.
// Later, this can be extended to fetch and display actual schedule data.

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Teacher Schedule</h1>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <p class="text-gray-600">This is the teacher schedule page. Schedule management functionality will be implemented here.</p>
                <!-- Placeholder schedule table -->
                <table class="min-w-full mt-6 border border-gray-200 rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 border-b border-gray-300 text-left text-sm font-semibold text-gray-700">Day</th>
                            <th class="px-4 py-2 border-b border-gray-300 text-left text-sm font-semibold text-gray-700">Time</th>
                            <th class="px-4 py-2 border-b border-gray-300 text-left text-sm font-semibold text-gray-700">Class</th>
                            <th class="px-4 py-2 border-b border-gray-300 text-left text-sm font-semibold text-gray-700">Subject</th>
                            <th class="px-4 py-2 border-b border-gray-300 text-left text-sm font-semibold text-gray-700">Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-4 py-2 border-b border-gray-200">Monday</td>
                            <td class="px-4 py-2 border-b border-gray-200">9:00 AM - 10:00 AM</td>
                            <td class="px-4 py-2 border-b border-gray-200">Mathematics 101</td>
                            <td class="px-4 py-2 border-b border-gray-200">Algebra</td>
                            <td class="px-4 py-2 border-b border-gray-200">Room 201</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 border-b border-gray-200">Tuesday</td>
                            <td class="px-4 py-2 border-b border-gray-200">10:15 AM - 11:15 AM</td>
                            <td class="px-4 py-2 border-b border-gray-200">Science 102</td>
                            <td class="px-4 py-2 border-b border-gray-200">Physics</td>
                            <td class="px-4 py-2 border-b border-gray-200">Room 105</td>
                        </tr>
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
