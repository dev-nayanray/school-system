<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request";
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $role_target = $_POST['role_target'] ?? 'all';
        $priority = $_POST['priority'] ?? 'normal';

        if (!$title) {
            $errors[] = 'Title is required.';
        }
        if (!$content) {
            $errors[] = 'Content is required.';
        }
        $valid_roles = ['all', 'admin', 'teacher', 'student', 'user'];
        if (!in_array($role_target, $valid_roles)) {
            $errors[] = 'Invalid role target selected.';
        }
        $valid_priorities = ['low', 'normal', 'high', 'critical'];
        if (!in_array($priority, $valid_priorities)) {
            $errors[] = 'Invalid priority selected.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO announcements (title, content, posted_by, role_target, priority) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$title, $content, $_SESSION['user_id'], $role_target, $priority]);
                $success = 'Announcement posted successfully.';
                
                // Clear form after successful submission
                $_POST['title'] = '';
                $_POST['content'] = '';
                $_POST['role_target'] = 'all';
                $_POST['priority'] = 'normal';
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch recent announcements
$stmt = $pdo->query('
    SELECT a.*, u.name AS posted_by_name 
    FROM announcements a 
    JOIN users u ON a.posted_by = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
');
$announcements = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Announcements</h1>
                <div class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    <span class="text-lg font-medium">Create New Announcement</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Section -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600">
                            <h2 class="text-xl font-semibold text-white">New Announcement</h2>
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

                            <form action="admin_announcements.php" method="post" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <div class="relative">
                                        <input type="text" id="title" name="title" required 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                               placeholder="Enter announcement title">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H6z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                    <div class="relative">
                                        <textarea id="content" name="content" rows="6" required 
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                                  placeholder="Type your announcement details here..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-500 text-right">
                                        <span id="charCount">0</span>/1000 characters
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="role_target" class="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
                                        <div class="relative">
                                            <select id="role_target" name="role_target" 
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white bg-select-arrow bg-no-repeat bg-right pr-10">
                                                <option value="all" <?php if (($_POST['role_target'] ?? '') === 'all') echo 'selected'; ?>>All Users</option>
                                                <option value="admin" <?php if (($_POST['role_target'] ?? '') === 'admin') echo 'selected'; ?>>Administrators</option>
                                                <option value="teacher" <?php if (($_POST['role_target'] ?? '') === 'teacher') echo 'selected'; ?>>Teachers</option>
                                                <option value="student" <?php if (($_POST['role_target'] ?? '') === 'student') echo 'selected'; ?>>Students</option>
                                                <option value="user" <?php if (($_POST['role_target'] ?? '') === 'user') echo 'selected'; ?>>Standard Users</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                        <div class="relative">
                                            <select id="priority" name="priority" 
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white bg-select-arrow bg-no-repeat bg-right pr-10">
                                                <option value="low" <?php if (($_POST['priority'] ?? '') === 'low') echo 'selected'; ?>>Low Priority</option>
                                                <option value="normal" <?php if (($_POST['priority'] ?? '') === 'normal') echo 'selected'; ?>>Normal Priority</option>
                                                <option value="high" <?php if (($_POST['priority'] ?? '') === 'high') echo 'selected'; ?>>High Priority</option>
                                                <option value="critical" <?php if (($_POST['priority'] ?? '') === 'critical') echo 'selected'; ?>>Critical Priority</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="pt-4">
                                    <button type="submit" class="w-full px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium transition transform hover:-translate-y-0.5">
                                        <div class="flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd" />
                                            </svg>
                                            Post Announcement
                                        </div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Announcements -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-gray-700 to-gray-900">
                            <h2 class="text-xl font-semibold text-white">Recent Announcements</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900">No recent announcements</h3>
                                    <p class="mt-1 text-sm text-gray-500">Your announcements will appear here</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                                    <?php foreach ($announcements as $announcement): 
                                        $priorityColors = [
                                            'low' => 'bg-gray-100 text-gray-800',
                                            'normal' => 'bg-blue-100 text-blue-800',
                                            'high' => 'bg-amber-100 text-amber-800',
                                            'critical' => 'bg-red-100 text-red-800'
                                        ];
                                        $priorityText = [
                                            'low' => 'Low',
                                            'normal' => 'Normal',
                                            'high' => 'High',
                                            'critical' => 'Critical'
                                        ];
                                    ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex justify-between items-start">
                                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $priorityColors[$announcement['priority']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo $priorityText[$announcement['priority']] ?? 'Normal'; ?>
                                                </span>
                                            </div>
                                            <div class="mt-2 text-sm text-gray-500 line-clamp-2">
                                                <?php echo htmlspecialchars($announcement['content']); ?>
                                            </div>
                                            <div class="mt-3 flex items-center text-xs text-gray-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                </svg>
                                                <?php echo htmlspecialchars($announcement['posted_by_name']); ?>
                                                <span class="mx-2">•</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                                <?php 
                                                    $date = new DateTime($announcement['created_at']);
                                                    echo $date->format('M d, Y');
                                                ?>
                                            </div>
                                            <div class="mt-2 text-xs">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                                    For: <?php echo ucfirst($announcement['role_target']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-4 text-center">
                                    <a href="admin_view_announcements.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        View All Announcements
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Character count for content
    const contentField = document.getElementById('content');
    const charCount = document.getElementById('charCount');
    
    contentField.addEventListener('input', function() {
        charCount.textContent = this.value.length;
        
        if (this.value.length > 1000) {
            charCount.classList.add('text-red-600', 'font-medium');
        } else {
            charCount.classList.remove('text-red-600', 'font-medium');
        }
    });
    
    // Initialize character count
    charCount.textContent = contentField.value.length;
</script>

<?php include '../includes/footer.php'; ?>