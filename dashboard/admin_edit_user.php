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
$id = $_GET['id'] ?? null;
$user = null;

if ($id) {
    // Fetch user data for editing
    $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['error'] = "User not found";
        header('Location: admin_manage_users.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$name) {
            $errors[] = 'Name is required.';
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        $valid_roles = ['admin', 'teacher', 'student', 'user'];
        if (!in_array($role, $valid_roles)) {
            $errors[] = 'Invalid role selected.';
        }
        if ($id === null && !$password) {
            $errors[] = 'Password is required for new users.';
        }
        if ($password && $password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            // Check if email is unique
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $id ?? 0]);
            if ($stmt->fetch()) {
                $errors[] = 'Email is already registered to another user.';
            } else {
                try {
                    if ($id) {
                        // Update user
                        if ($password) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?');
                            $stmt->execute([$name, $email, $role, $hashed_password, $id]);
                        } else {
                            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
                            $stmt->execute([$name, $email, $role, $id]);
                        }
                        $success = 'User updated successfully.';
                    } else {
                        // Insert new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$name, $email, $hashed_password, $role]);
                        $success = 'User added successfully.';
                        
                        // Redirect to prevent form resubmission
                        $_SESSION['success'] = $success;
                        header('Location: admin_edit_user.php?id=' . $pdo->lastInsertId());
                        exit();
                    }
                } catch (PDOException $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        }
    }
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
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <?php echo $id ? 'Edit User' : 'Create New User'; ?>
                </h1>
                <a href="admin_manage_users.php" class="flex items-center space-x-1 text-blue-600 hover:text-blue-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    <span>Back to Users</span>
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
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

                    <form action="<?php echo $id ? 'admin_edit_user.php?id=' . $id : 'admin_edit_user.php'; ?>" method="post" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <div class="relative">
                                    <input type="text" id="name" name="name" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name'] ?? ''); ?>"
                                           placeholder="Enter full name">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <div class="relative">
                                    <input type="email" id="email" name="email" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email'] ?? ''); ?>"
                                           placeholder="user@example.com">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">User Role</label>
                                <div class="relative">
                                    <select id="role" name="role" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm appearance-none bg-white bg-select-arrow bg-no-repeat bg-right pr-10">
                                        <?php
                                        $roles = [
                                            'admin' => 'Administrator',
                                            'teacher' => 'Teacher',
                                            'student' => 'Student',
                                            'user' => 'Standard User'
                                        ];
                                        $selected_role = $_POST['role'] ?? $user['role'] ?? 'user';
                                        foreach ($roles as $value => $label) {
                                            $selected = ($value === $selected_role) ? 'selected' : '';
                                            echo "<option value=\"$value\" $selected>$label</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($id && isset($user['created_at'])): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Created</label>
                                    <div class="px-4 py-3 bg-gray-50 rounded-lg">
                                        <p class="text-gray-900"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Password Settings</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                <?php echo $id ? 'Leave these fields blank to keep the current password' : 'Set a strong password for this user'; ?>
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                               placeholder="••••••••">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        Must be at least 8 characters
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                    <div class="relative">
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition"
                                               placeholder="••••••••">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end pt-8 border-t border-gray-200 mt-8">
                            <a href="admin_manage_users.php" class="px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Cancel
                            </a>
                            <button type="submit" class="ml-4 px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium transition transform hover:-translate-y-0.5">
                                <?php echo $id ? 'Update User' : 'Create User'; ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if ($id): ?>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Danger Zone</h3>
                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Admin Only</span>
                        </div>
                        <div class="mt-4 flex items-center">
                            <div class="flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-700">
                                    Deleting a user account is permanent and cannot be undone.
                                    <?php if ($id == $_SESSION['user_id']): ?>
                                        <span class="font-semibold">You cannot delete your own account.</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="ml-auto">
                                <?php if ($id != $_SESSION['user_id']): ?>
                                    <form method="post" action="admin_manage_users.php" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                                        <button type="submit" name="delete" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                            Delete User Account
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-300 cursor-not-allowed">
                                        Delete Disabled
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>