<?php
require_once '../includes/auth.php';
require_role('student');
require_once '../includes/db.php';

$student_user_id = $_SESSION['user_id'];

// Get student profile id
$stmt = $pdo->prepare('SELECT id, class_id FROM student_profiles WHERE user_id = ?');
$stmt->execute([$student_user_id]);
$student_profile = $stmt->fetch();

$marks = [];
$class_name = "N/A";
$student_name = $_SESSION['name'];
$performance_summary = [
    'total_marks' => 0,
    'count' => 0,
    'highest' => 0,
    'lowest' => 100,
    'distinctions' => 0,
    'passes' => 0,
    'needs_improvement' => 0
];

if ($student_profile) {
    $student_id = $student_profile['id'];
    // Get class name
    $stmt = $pdo->prepare('SELECT class_name FROM classes WHERE id = ?');
    $stmt->execute([$student_profile['class_id']]);
    $class_row = $stmt->fetch();
    $class_name = $class_row ? $class_row['class_name'] : "N/A";
    
    // Fetch marks with subject and exam details
    $stmt = $pdo->prepare('
        SELECT m.id, m.marks_obtained, m.exam_type, m.exam_date, s.subject_name, c.class_name
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        JOIN classes c ON s.class_id = c.id
        WHERE m.student_id = ?
        ORDER BY m.exam_date DESC, c.class_name, s.subject_name, m.exam_type
    ');
    $stmt->execute([$student_id]);
    $marks = $stmt->fetchAll();
    
    // Calculate performance summary
    foreach ($marks as $mark) {
        $score = $mark['marks_obtained'];
        $performance_summary['total_marks'] += $score;
        $performance_summary['count']++;
        
        if ($score > $performance_summary['highest']) {
            $performance_summary['highest'] = $score;
        }
        
        if ($score < $performance_summary['lowest']) {
            $performance_summary['lowest'] = $score;
        }
        
        if ($score >= 85) {
            $performance_summary['distinctions']++;
        } elseif ($score >= 50) {
            $performance_summary['passes']++;
        } else {
            $performance_summary['needs_improvement']++;
        }
    }
    
    if ($performance_summary['count'] > 0) {
        $performance_summary['average'] = round($performance_summary['total_marks'] / $performance_summary['count'], 1);
    } else {
        $performance_summary['average'] = 0;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Academic Performance</h1>
                    <p class="mt-2 text-gray-600">Review your marks and track your academic progress</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="flex items-center bg-white rounded-lg shadow-sm p-3">
                        <div class="bg-gray-200 border-2 border-dashed rounded-xl w-12 h-12"></div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($student_name); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($class_name); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium">Overall Average</p>
                            <p class="mt-1 text-3xl font-bold"><?php echo $performance_summary['average']; ?>%</p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-white bg-opacity-20 rounded-full h-2">
                            <div class="bg-white h-2 rounded-full" style="width: <?php echo $performance_summary['average']; ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Highest Score</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $performance_summary['highest']; ?>%</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Lowest Score</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $performance_summary['lowest']; ?>%</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Exams Taken</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo count($marks); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Distribution Chart -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Performance Distribution</h2>
                    <div class="relative">
                        <select class="appearance-none bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option>All Subjects</option>
                            <option>Mathematics</option>
                            <option>Science</option>
                            <option>Literature</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-sm font-medium">Distinction (85%+)</span>
                            </div>
                            <span class="text-sm font-semibold"><?php echo $performance_summary['distinctions']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: <?php echo count($marks) ? round(($performance_summary['distinctions'] / count($marks)) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-sm font-medium">Pass (50-84%)</span>
                            </div>
                            <span class="text-sm font-semibold"><?php echo $performance_summary['passes']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo count($marks) ? round(($performance_summary['passes'] / count($marks)) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                                <span class="text-sm font-medium">Needs Improvement (<50%)</span>
                            </div>
                            <span class="text-sm font-semibold"><?php echo $performance_summary['needs_improvement']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-red-500 h-2.5 rounded-full" style="width: <?php echo count($marks) ? round(($performance_summary['needs_improvement'] / count($marks)) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Marks Table with Filters -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Exam Results</h2>
                    
                    <div class="flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
                        <div class="relative">
                            <input type="text" id="searchMarks" placeholder="Search subjects..." class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="relative">
                            <select id="filterExamType" class="appearance-none w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="all">All Exam Types</option>
                                <option value="Midterm">Midterm</option>
                                <option value="Final">Final</option>
                                <option value="Quiz">Quiz</option>
                                <option value="Assignment">Assignment</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($marks)): ?>
                    <div class="bg-gray-50 rounded-lg p-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-4 text-xl font-medium text-gray-900">No exam results available</h3>
                        <p class="mt-2 text-gray-500">Your marks will appear here once they are recorded</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Subject</th>
                                    <th scope="col" class="px-6 py-3">Class</th>
                                    <th scope="col" class="px-6 py-3">Exam Type</th>
                                    <th scope="col" class="px-6 py-3">Date</th>
                                    <th scope="col" class="px-6 py-3">Marks</th>
                                    <th scope="col" class="px-6 py-3">Performance</th>
                                </tr>
                            </thead>
                            <tbody id="marksTableBody">
                                <?php foreach ($marks as $mark): 
                                    $score = $mark['marks_obtained'];
                                    $status = $score >= 85 ? 'Distinction' : 
                                             ($score >= 50 ? 'Pass' : 'Needs Improvement');
                                    $statusColor = $score >= 85 ? 'bg-green-100 text-green-800' : 
                                                ($score >= 50 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800');
                                    $date = $mark['exam_date'] ? date('M d, Y', strtotime($mark['exam_date'])) : 'N/A';
                                ?>
                                <tr class="bg-white border-b hover:bg-gray-50 mark-row">
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($mark['class_name']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <?php echo htmlspecialchars($mark['exam_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo $date; ?></td>
                                    <td class="px-6 py-4 font-semibold"><?php echo $score; ?>%</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Performance Trends -->
                    <div class="mt-10">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Performance Trends</h3>
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex justify-between mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Average score over time</p>
                                    <p class="text-xl font-bold text-gray-900">Improving by 8% this term</p>
                                </div>
                                <div>
                                    <select class="bg-white border border-gray-300 rounded-lg px-3 py-1 text-sm">
                                        <option>Last 30 days</option>
                                        <option>Last 90 days</option>
                                        <option>All Time</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="h-64 flex items-end space-x-2">
                                <!-- Graph bars would be generated dynamically in a real app -->
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="bg-gradient-to-t from-blue-500 to-blue-300 w-10 rounded-t-lg" style="height: 70%"></div>
                                    <span class="mt-2 text-xs text-gray-500">Math</span>
                                </div>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="bg-gradient-to-t from-green-500 to-green-300 w-10 rounded-t-lg" style="height: 85%"></div>
                                    <span class="mt-2 text-xs text-gray-500">Science</span>
                                </div>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="bg-gradient-to-t from-purple-500 to-purple-300 w-10 rounded-t-lg" style="height: 65%"></div>
                                    <span class="mt-2 text-xs text-gray-500">History</span>
                                </div>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="bg-gradient-to-t from-yellow-500 to-yellow-300 w-10 rounded-t-lg" style="height: 78%"></div>
                                    <span class="mt-2 text-xs text-gray-500">English</span>
                                </div>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="bg-gradient-to-t from-red-500 to-red-300 w-10 rounded-t-lg" style="height: 60%"></div>
                                    <span class="mt-2 text-xs text-gray-500">Art</span>
                                </div>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="bg-gradient-to-t from-indigo-500 to-indigo-300 w-10 rounded-t-lg" style="height: 82%"></div>
                                    <span class="mt-2 text-xs text-gray-500">PE</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
    .mark-row {
        transition: all 0.2s ease;
    }
    .mark-row:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchMarks');
    const markRows = document.querySelectorAll('.mark-row');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        markRows.forEach(row => {
            const subject = row.querySelector('td:first-child').textContent.toLowerCase();
            const examType = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (subject.includes(searchTerm) || examType.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Filter by exam type
    const examTypeFilter = document.getElementById('filterExamType');
    
    examTypeFilter.addEventListener('change', function() {
        const filterValue = this.value.toLowerCase();
        
        markRows.forEach(row => {
            const examType = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (filterValue === 'all' || examType.includes(filterValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Performance chart hover effect
    const graphBars = document.querySelectorAll('[class*="bg-gradient-to-t"]');
    graphBars.forEach(bar => {
        bar.addEventListener('mouseenter', function() {
            this.classList.add('opacity-90');
        });
        
        bar.addEventListener('mouseleave', function() {
            this.classList.remove('opacity-90');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>