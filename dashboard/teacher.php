<?php
require_once '../includes/auth.php';
require_role('teacher');
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'];

// Fetch subjects assigned to teacher
$stmt = $pdo->prepare('
    SELECT s.id, s.subject_name, c.class_name, COUNT(sp.id) as student_count
    FROM subjects s
    JOIN teacher_profiles tp ON tp.subject_id = s.id
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN student_profiles sp ON sp.class_id = c.id
    WHERE tp.user_id = ?
    GROUP BY s.id, c.id
');
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

// Fetch recent marks entered by teacher (last 5)
$stmt = $pdo->prepare('
    SELECT m.marks_obtained, m.exam_type, m.exam_date, u.name as student_name, s.subject_name
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
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.content, a.created_at 
    FROM announcements a 
    WHERE a.role_target IN ('all', 'teacher') 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$announcements = $stmt->fetchAll();

// Calculate stats
$total_students = 0;
$total_subjects = count($subjects);
foreach ($subjects as $subject) {
    $total_students += $subject['student_count'];
}

// Get upcoming classes (simulated data)
$upcoming_classes = [
    ['subject' => 'Mathematics', 'class' => 'Grade 10A', 'time' => '10:00 AM - 11:30 AM', 'room' => 'Room 302'],
    ['subject' => 'Physics', 'class' => 'Grade 11B', 'time' => '1:00 PM - 2:30 PM', 'room' => 'Room 215'],
    ['subject' => 'Computer Science', 'class' => 'Grade 12C', 'time' => '3:00 PM - 4:30 PM', 'room' => 'Lab 3'],
];
?>

<?php include '../includes/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Teacher Dashboard</h1>
                    <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($teacher_name); ?>! Here's your teaching overview.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="flex items-center bg-white rounded-lg shadow-sm p-3">
                        <div class="bg-gray-200 border-2 border-dashed rounded-xl w-12 h-12"></div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($teacher_name); ?></p>
                            <p class="text-sm text-gray-500">Educator</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium">Assigned Subjects</p>
                            <p class="mt-1 text-3xl font-bold"><?php echo $total_subjects; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Students</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $total_students; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.284-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.284.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Evaluations</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">12</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Upcoming Classes</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo count($upcoming_classes); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- My Subjects -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">My Subjects</h2>
                            <a href="#" class="text-blue-600 font-medium hover:text-blue-800">View All</a>
                        </div>
                        
                        <?php if (empty($subjects)): ?>
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-4 text-xl font-medium text-gray-900">No subjects assigned</h3>
                                <p class="mt-2 text-gray-500">Contact administrator to get assigned to subjects</p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="border border-gray-200 rounded-lg p-5 hover:border-blue-300 transition-colors">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                                                <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($subject['class_name']); ?></p>
                                            </div>
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                <?php echo $subject['student_count']; ?> students
                                            </span>
                                        </div>
                                        <div class="mt-4 flex justify-between">
                                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Class</a>
                                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Enter Marks</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Marks Entered -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Recent Marks Entered</h2>
                            <a href="#" class="text-blue-600 font-medium hover:text-blue-800">View All</a>
                        </div>
                        
                        <?php if (empty($recent_marks)): ?>
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <h3 class="mt-4 text-xl font-medium text-gray-900">No marks entered yet</h3>
                                <p class="mt-2 text-gray-500">Your entered marks will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Student</th>
                                            <th scope="col" class="px-6 py-3">Subject</th>
                                            <th scope="col" class="px-6 py-3">Exam Type</th>
                                            <th scope="col" class="px-6 py-3">Date</th>
                                            <th scope="col" class="px-6 py-3">Marks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_marks as $mark): 
                                            $date = $mark['exam_date'] ? date('M d, Y', strtotime($mark['exam_date'])) : 'N/A';
                                        ?>
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap"><?php echo htmlspecialchars($mark['student_name']); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    <?php echo htmlspecialchars($mark['exam_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4"><?php echo $date; ?></td>
                                            <td class="px-6 py-4 font-semibold"><?php echo $mark['marks_obtained']; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Upcoming Classes -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Upcoming Classes</h2>
                            <a href="#" class="text-blue-600 font-medium hover:text-blue-800">View Schedule</a>
                        </div>
                        
                        <div class="space-y-4">
                            <?php foreach ($upcoming_classes as $class): ?>
                                <div class="flex items-start p-3 border border-gray-200 rounded-lg">
                                    <div class="flex-shrink-0 w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="font-medium text-gray-900"><?php echo $class['subject']; ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo $class['class']; ?></p>
                                        <div class="flex items-center mt-1 text-sm text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.284-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.284.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <?php echo $class['time']; ?>
                                        </div>
                                        <div class="flex items-center mt-1 text-sm text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            <?php echo $class['room']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Announcements -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Announcements</h2>
                            <a href="#" class="text-blue-600 font-medium hover:text-blue-800">View All</a>
                        </div>
                        
                        <div class="space-y-4">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-4">
                                    <p class="text-gray-500">No announcements available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): 
                                    $date = date('M d, Y', strtotime($announcement['created_at']));
                                ?>
                                <div class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                    <div class="flex justify-between">
                                        <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <span class="text-sm text-gray-500"><?php echo $date; ?></span>
                                    </div>
                                    <p class="mt-2 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</p>
                                    <div class="mt-3 flex justify-end">
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">Read More</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
                        <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Enter Marks</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Schedule</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span>Announce</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>Resources</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>