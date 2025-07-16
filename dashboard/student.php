<?php
require_once '../includes/auth.php';
require_role('student');
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Get student profile id and class
$stmt = $pdo->prepare('
    SELECT sp.id as student_profile_id, c.class_name, sp.class_id, u.name, u.email
    FROM student_profiles sp
    JOIN classes c ON sp.class_id = c.id
    JOIN users u ON sp.user_id = u.id
    WHERE sp.user_id = ?
');
$stmt->execute([$user_id]);
$student_profile = $stmt->fetch();

$student_profile_id = $student_profile['student_profile_id'] ?? null;
$class_name = $student_profile['class_name'] ?? 'N/A';
$student_name = $student_profile['name'] ?? '';
$student_email = $student_profile['email'] ?? '';

// Fetch subjects for the student's class
$subjects = [];
if ($student_profile['class_id'] ?? null) {
    $stmt = $pdo->prepare('
        SELECT id, subject_name
        FROM subjects
        WHERE class_id = ?
    ');
    $stmt->execute([$student_profile['class_id']]);
    $subjects = $stmt->fetchAll();
}

// Fetch all marks for student
$all_marks = [];
if ($student_profile_id) {
    $stmt = $pdo->prepare('
        SELECT m.marks_obtained, m.exam_type, m.exam_date, s.subject_name, s.id as subject_id
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        WHERE m.student_id = ?
        ORDER BY m.exam_date DESC
    ');
    $stmt->execute([$student_profile_id]);
    $all_marks = $stmt->fetchAll();
}

// Fetch announcements for student
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.content, a.created_at 
    FROM announcements a 
    WHERE a.role_target IN ('all', 'student') 
    ORDER BY a.created_at DESC
");
$stmt->execute();
$announcements = $stmt->fetchAll();

// Calculate statistics
$overall_performance = 0;
$subject_performance = [];
$recent_marks = array_slice($all_marks, 0, 5);

if (!empty($all_marks)) {
    $total_marks = 0;
    $count = 0;
    foreach ($all_marks as $mark) {
        $total_marks += $mark['marks_obtained'];
        $count++;
        
        if (!isset($subject_performance[$mark['subject_id']])) {
            $subject_performance[$mark['subject_id']] = [
                'name' => $mark['subject_name'],
                'total' => 0,
                'count' => 0,
                'max' => 0,
                'min' => 100
            ];
        }
        
        $subject_performance[$mark['subject_id']]['total'] += $mark['marks_obtained'];
        $subject_performance[$mark['subject_id']]['count']++;
        
        if ($mark['marks_obtained'] > $subject_performance[$mark['subject_id']]['max']) {
            $subject_performance[$mark['subject_id']]['max'] = $mark['marks_obtained'];
        }
        
        if ($mark['marks_obtained'] < $subject_performance[$mark['subject_id']]['min']) {
            $subject_performance[$mark['subject_id']]['min'] = $mark['marks_obtained'];
        }
    }
    
    $overall_performance = $count > 0 ? round($total_marks / $count, 1) : 0;
    
    // Calculate averages for each subject
    foreach ($subject_performance as $subject_id => $data) {
        $subject_performance[$subject_id]['average'] = round($data['total'] / $data['count'], 1);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header with student info -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Student Dashboard</h1>
                    <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($student_name); ?>! Here's your academic overview.</p>
                </div>
                <div class="mt-4 md:mt-0 bg-white rounded-lg shadow-sm p-4 flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-16 h-16" ></div>
                    <div class="ml-4">
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($student_name); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($class_name); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($student_email); ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium">Overall Performance</p>
                            <p class="mt-1 text-3xl font-bold"><?php echo $overall_performance; ?>%</p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-white bg-opacity-20 rounded-full h-2">
                            <div class="bg-white h-2 rounded-full" style="width: <?php echo $overall_performance; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Enrolled Class</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($class_name); ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Subjects</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo count($subjects); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Exams Taken</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo count($all_marks); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Subject Performance Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Subject Performance</h2>
                            <div class="relative">
                                <select class="appearance-none bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    <option>Last 30 Days</option>
                                    <option>Last 60 Days</option>
                                    <option>All Time</option>
                                </select>
                            </div>
                        </div>
                        
                        <?php if (!empty($subject_performance)): ?>
                            <div class="space-y-4">
                                <?php foreach ($subject_performance as $subject): ?>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-base font-medium text-gray-900"><?php echo htmlspecialchars($subject['name']); ?></span>
                                            <span class="text-sm font-medium text-gray-900"><?php echo $subject['average']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $subject['average']; ?>%"></div>
                                        </div>
                                        <div class="flex justify-between mt-1">
                                            <span class="text-xs text-gray-500">Min: <?php echo $subject['min']; ?>%</span>
                                            <span class="text-xs text-gray-500">Max: <?php echo $subject['max']; ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No performance data available yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Marks with Search -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Recent Exam Results</h2>
                            <div class="relative">
                                <input type="text" id="marksSearch" placeholder="Search by subject or exam..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Subject</th>
                                        <th scope="col" class="px-6 py-3">Exam Type</th>
                                        <th scope="col" class="px-6 py-3">Date</th>
                                        <th scope="col" class="px-6 py-3">Marks</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="marksTableBody">
                                    <?php if (!empty($recent_marks)): ?>
                                        <?php foreach ($recent_marks as $mark): ?>
                                            <?php 
                                                $status = $mark['marks_obtained'] >= 70 ? 'Excellent' : 
                                                         ($mark['marks_obtained'] >= 50 ? 'Good' : 'Needs Improvement');
                                                $statusColor = $mark['marks_obtained'] >= 70 ? 'bg-green-100 text-green-800' : 
                                                            ($mark['marks_obtained'] >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                            ?>
                                            <tr class="bg-white border-b hover:bg-gray-50">
                                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars($mark['exam_type']); ?></td>
                                                <td class="px-6 py-4"><?php echo htmlspecialchars(date('M d, Y', strtotime($mark['exam_date']))); ?></td>
                                                <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($mark['marks_obtained']); ?></td>
                                                <td class="px-6 py-4"><span class="px-2.5 py-0.5 text-xs font-medium rounded-full <?php echo $statusColor; ?>"><?php echo $status; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="bg-white border-b">
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No marks available yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (count($all_marks) > 5): ?>
                            <div class="mt-4 text-right">
                                <a href="#" class="text-blue-600 hover:underline font-medium">View All Results</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right column -->
                <div class="space-y-6">
                    <!-- Announcements with Search -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Announcements</h2>
                            <div class="relative">
                                <input type="text" id="announcementSearch" placeholder="Search announcements..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4" id="announcementsList">
                            <?php if (!empty($announcements)): ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement-item p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                        <div class="flex justify-between">
                                            <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                            <span class="text-sm text-gray-500"><?php echo htmlspecialchars(date('M d', strtotime($announcement['created_at']))); ?></span>
                                        </div>
                                        <p class="mt-2 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</p>
                                        <div class="mt-3 flex justify-end">
                                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">Read More</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No announcements available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Upcoming Events -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Upcoming Events</h2>
                            <button class="text-blue-600 font-medium">View Calendar</button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-start p-3 border border-gray-200 rounded-lg">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                    <span class="text-blue-800 font-bold">15</span>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900">Mathematics Midterm</h3>
                                    <p class="text-sm text-gray-500">10:00 AM - 11:30 AM</p>
                                    <p class="mt-1 text-sm text-gray-500">Room 204</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start p-3 border border-gray-200 rounded-lg">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                                    <span class="text-green-800 font-bold">18</span>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900">Science Project Due</h3>
                                    <p class="text-sm text-gray-500">All Day</p>
                                    <p class="mt-1 text-sm text-gray-500">Submit online</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start p-3 border border-gray-200 rounded-lg">
                                <div class="flex-shrink-0 w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                                    <span class="text-purple-800 font-bold">22</span>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900">Parent-Teacher Meeting</h3>
                                    <p class="text-sm text-gray-500">2:00 PM - 4:00 PM</p>
                                    <p class="mt-1 text-sm text-gray-500">Conference Room</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
                        <h2 class="text-xl font-bold mb-4">Quick Access</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span>Study Materials</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Assignments</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Schedule</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>Classmates</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Marks search functionality
    const marksSearch = document.getElementById('marksSearch');
    const marksTableBody = document.getElementById('marksTableBody');
    const originalMarksRows = marksTableBody.innerHTML;
    
    marksSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm === '') {
            marksTableBody.innerHTML = originalMarksRows;
            return;
        }
        
        const rows = marksTableBody.querySelectorAll('tr');
        let hasResults = false;
        
        marksTableBody.innerHTML = '';
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let rowText = '';
            
            cells.forEach(cell => {
                rowText += cell.textContent.toLowerCase() + ' ';
            });
            
            if (rowText.includes(searchTerm)) {
                marksTableBody.appendChild(row);
                hasResults = true;
            }
        });
        
        if (!hasResults) {
            marksTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No matching results found</td></tr>';
        }
    });
    
    // Announcements search functionality
    const announcementSearch = document.getElementById('announcementSearch');
    const announcementsList = document.getElementById('announcementsList');
    const originalAnnouncements = announcementsList.innerHTML;
    
    announcementSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm === '') {
            announcementsList.innerHTML = originalAnnouncements;
            return;
        }
        
        const announcements = announcementsList.querySelectorAll('.announcement-item');
        let hasResults = false;
        
        announcementsList.innerHTML = '';
        
        announcements.forEach(announcement => {
            const title = announcement.querySelector('h3').textContent.toLowerCase();
            const content = announcement.querySelector('p').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || content.includes(searchTerm)) {
                announcementsList.appendChild(announcement);
                hasResults = true;
            }
        });
        
        if (!hasResults) {
            announcementsList.innerHTML = '<p class="text-gray-500 text-center py-4">No matching announcements found</p>';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>