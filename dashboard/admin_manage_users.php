<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

// Initialize messages
$error = '';
$success = '';

// Handle delete user request
if (isset($_POST['delete'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
    } elseif ($delete_id === $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$delete_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "User deleted successfully.";
                header('Location: admin_manage_users.php');
                exit();
            } else {
                $error = "User not found or already deleted.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle status toggle
if (isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = (int)$_POST['new_status'];
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request";
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmt->execute([$new_status, $user_id]);
            $success = "User status updated successfully.";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all users with status
$stmt = $pdo->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

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
                <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                <a href="admin_edit_user.php" class="flex items-center space-x-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-medium py-2 px-4 rounded-lg shadow-lg transition-all duration-300 transform hover:-translate-y-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    <span>Add New User</span>
                </a>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <?php
                $statusCounts = [
                    'total' => count($users),
                    'active' => 0,
                    'inactive' => 0,
                    'admins' => 0
                ];
                
                foreach ($users as $user) {
                    if ($user['status'] == 1) $statusCounts['active']++;
                    if ($user['status'] == 0) $statusCounts['inactive']++;
                    if ($user['role'] == 'admin') $statusCounts['admins']++;
                }
                ?>
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500">
                    <div class="text-gray-500 text-sm font-medium">Total Users</div>
                    <div class="text-3xl font-bold mt-1"><?= $statusCounts['total'] ?></div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500">
                    <div class="text-gray-500 text-sm font-medium">Active</div>
                    <div class="text-3xl font-bold mt-1"><?= $statusCounts['active'] ?></div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-amber-500">
                    <div class="text-gray-500 text-sm font-medium">Inactive</div>
                    <div class="text-3xl font-bold mt-1"><?= $statusCounts['inactive'] ?></div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-purple-500">
                    <div class="text-gray-500 text-sm font-medium">Administrators</div>
                    <div class="text-3xl font-bold mt-1"><?= $statusCounts['admins'] ?></div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">All Users</h2>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search users..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 flex items-center justify-center text-gray-500 font-medium mr-3">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                                <div class="text-sm text-gray-500">ID: <?= $user['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                            <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="post" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $user['status'] ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_status" class="relative inline-flex items-center cursor-pointer">
                                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                                <span class="ml-3 text-sm font-medium text-gray-700">
                                                    <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="admin_edit_user.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </a>
                                            
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="delete" class="text-red-600 hover:text-red-900 transition-colors p-2 rounded-lg hover:bg-red-50" title="Delete">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-gray-300 cursor-not-allowed p-2" title="Cannot delete your own account">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($users)): ?>
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No users found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding a new user</p>
                        <div class="mt-6">
                            <a href="admin_edit_user.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Add New User
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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
            const name = row.querySelector('td:first-child .text-gray-900').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(2) .text-gray-900').textContent.toLowerCase();
            const shouldShow = name.includes(searchValue) || email.includes(searchValue);
            row.style.display = shouldShow ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>