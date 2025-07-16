<?php
require_once '../includes/auth.php';
require_role('user');
require_once '../includes/db.php';

// Fetch announcements for user
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.content, a.created_at 
    FROM announcements a 
    WHERE a.role_target IN ('all', 'user') 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$announcements = $stmt->fetchAll();

// Simulated user stats
$user_stats = [
    'completed_tasks' => 12,
    'pending_actions' => 3,
    'notifications' => 5,
    'account_status' => 'Active'
];

// Recent activity (simulated)
$recent_activity = [
    ['action' => 'Logged in', 'time' => '2 hours ago'],
    ['action' => 'Updated profile', 'time' => 'Yesterday'],
    ['action' => 'Viewed announcements', 'time' => '2 days ago'],
    ['action' => 'Changed password', 'time' => '1 week ago'],
];
?>

<?php include '../includes/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">User Dashboard</h1>
                    <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! Here's your personalized overview.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="flex items-center bg-white rounded-lg shadow-sm p-3">
                        <div class="bg-gray-200 border-2 border-dashed rounded-xl w-12 h-12"></div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                            <p class="text-sm text-gray-500">Standard User Account</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium">Completed Tasks</p>
                            <p class="mt-1 text-3xl font-bold"><?php echo $user_stats['completed_tasks']; ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Actions</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $user_stats['pending_actions']; ?></p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Notifications</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $user_stats['notifications']; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Account Status</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $user_stats['account_status']; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Announcements Card -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Announcements</h2>
                            <a href="#" class="text-blue-600 font-medium hover:text-blue-800">View All</a>
                        </div>
                        
                        <?php if (empty($announcements)): ?>
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-4 text-xl font-medium text-gray-900">No announcements</h3>
                                <p class="mt-2 text-gray-500">Check back later for updates</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($announcements as $announcement): 
                                    $date = date('M d, Y', strtotime($announcement['created_at']));
                                ?>
                                <div class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                    <div class="flex justify-between">
                                        <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <span class="text-sm text-gray-500"><?php echo $date; ?></span>
                                    </div>
                                    <p class="mt-2 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($announcement['content'], 0, 120)); ?>...</p>
                                    <div class="mt-3 flex justify-end">
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">Read More</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-md p-6 text-white">
                        <h2 class="text-xl font-bold mb-4">Quick Access</h2>
                        <div class="grid grid-cols-4 gap-4">
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Profile</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Settings</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Reports</span>
                            </a>
                            <a href="#" class="bg-white bg-opacity-20 hover:bg-opacity-30 transition rounded-lg p-4 flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <span>Support</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Recent Activity -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Recent Activity</h2>
                            <a href="#" class="text-blue-600 font-medium hover:text-blue-800">View All</a>
                        </div>
                        
                        <div class="space-y-4">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="flex items-start">
                                    <div class="bg-blue-100 p-2 rounded-full mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo $activity['action']; ?></p>
                                        <p class="text-sm text-gray-500"><?php echo $activity['time']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Account Overview -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Account Overview</h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Account Type</span>
                                <span class="font-medium">Standard User</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Member Since</span>
                                <span class="font-medium"><?php echo date('M d, Y', strtotime('-6 months')); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Last Login</span>
                                <span class="font-medium">Today, 09:24 AM</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Storage Used</span>
                                <span class="font-medium">125 MB / 1 GB</span>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: 12.5%"></div>
                            </div>
                            <div class="flex justify-between mt-2 text-sm text-gray-500">
                                <span>12.5% used</span>
                                <span>875 MB available</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-md p-6 text-white">
                        <div class="flex items-start">
                            <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">System Status</h3>
                                <p class="mt-1 text-sm opacity-90">All systems operational</p>
                                <p class="mt-2 text-xs opacity-75">Last updated: Today at 09:30 AM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>