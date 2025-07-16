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

// Handle delete subject request
if (isset($_POST['delete'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request";
    } else {
        $delete_id = (int)$_POST['delete_id'];
        try {
            $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = ?');
            $stmt->execute([$delete_id]);
            $_SESSION['success'] = 'Subject deleted successfully.';
            header('Location: admin_subjects.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle add/edit subject form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request";
    } else {
        $subject_name = trim($_POST['subject_name'] ?? '');
        $class_id = $_POST['class_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;

        if (!$subject_name) {
            $errors[] = 'Subject name is required.';
        }
        if (!$class_id) {
            $errors[] = 'Class is required.';
        }

        if (empty($errors)) {
            try {
                if ($subject_id) {
                    // Update subject
                    $stmt = $pdo->prepare('UPDATE subjects SET subject_name = ?, class_id = ? WHERE id = ?');
                    $stmt->execute([$subject_name, $class_id, $subject_id]);
                    $success = 'Subject updated successfully.';
                } else {
                    // Insert new subject
                    $stmt = $pdo->prepare('INSERT INTO subjects (subject_name, class_id) VALUES (?, ?)');
                    $stmt->execute([$subject_name, $class_id]);
                    $success = 'Subject added successfully.';
                    
                    // Clear form after adding
                    $_POST['subject_name'] = '';
                    $_POST['class_id'] = '';
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all subjects with class names
$stmt = $pdo->query('
    SELECT s.id, s.subject_name, c.class_name, c.id AS class_id
    FROM subjects s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.subject_name
');
$subjects = $stmt->fetchAll();

// Fetch all classes for dropdown
$stmt = $pdo->query('SELECT * FROM classes ORDER BY class_name ASC');
$classes = $stmt->fetchAll();

// If editing, fetch subject data
$edit_subject = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_subject = $stmt->fetch();
}

// Check for session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Manage Subjects</h1>
                <a href="#" class="flex items-center space-x-1 text-blue-600 hover:text-blue-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    <span>Export</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Section -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600">
                            <h2 class="text-xl font-semibold text-white">
                                <?php echo $edit_subject ? 'Edit Subject' : 'Add New Subject'; ?>
                            </h2>
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

                            <form action="admin_subjects.php<?php echo $edit_subject ? '?edit=' . $edit_subject['id'] : ''; ?>" method="post" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id'] ?? ''; ?>" />
                                
                                <div>
                                    <label for="subject_name" class="block text-sm font-medium text-gray-700 mb-1">Subject Name</label>
                                    <div class="relative">
                                        <input type="text" id="subject_name" name="subject_name" required 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                               value="<?php echo htmlspecialchars($edit_subject['subject_name'] ?? $_POST['subject_name'] ?? ''); ?>"
                                               placeholder="Mathematics, Science, etc.">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                    <div class="relative">
                                        <select id="class_id" name="class_id" required 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white bg-select-arrow bg-no-repeat bg-right pr-10">
                                            <option value="">Select Class</option>
                                            <?php foreach ($classes as $class): ?>
                                                <?php 
                                                $selected = false;
                                                if ($edit_subject && $edit_subject['class_id'] == $class['id']) $selected = true;
                                                if (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) $selected = true;
                                                ?>
                                                <option value="<?php echo $class['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="pt-4">
                                    <button type="submit" name="submit" class="w-full px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium transition transform hover:-translate-y-0.5">
                                        <?php echo $edit_subject ? 'Update Subject' : 'Add Subject'; ?>
                                    </button>
                                    
                                    <?php if ($edit_subject): ?>
                                        <a href="admin_subjects.php" class="mt-3 w-full block text-center px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                            Cancel Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Subjects Table -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800">All Subjects</h2>
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search subjects..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($subject['class_name']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="admin_subjects.php?edit=<?php echo $subject['id']; ?>" class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                    </a>
                                                    
                                                    <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this subject? All related data will be permanently removed.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="delete_id" value="<?php echo $subject['id']; ?>">
                                                        <button type="submit" name="delete" class="text-red-600 hover:text-red-900 transition-colors p-2 rounded-lg hover:bg-red-50" title="Delete">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (empty($subjects)): ?>
                                <div class="text-center py-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900">No subjects found</h3>
                                    <p class="mt-1 text-sm text-gray-500">Add your first subject using the form</p>
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
    // Simple client-side search
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const subject = row.querySelector('td:first-child .text-gray-900').textContent.toLowerCase();
            const className = row.querySelector('td:nth-child(2) .text-blue-800').textContent.toLowerCase();
            const shouldShow = subject.includes(searchValue) || className.includes(searchValue);
            row.style.display = shouldShow ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>