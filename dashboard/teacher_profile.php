<?php
require_once '../includes/auth.php';
require_role('teacher');
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user and teacher profile data
$stmt = $pdo->prepare('
    SELECT u.name, u.email, u.created_at, s.subject_id, sub.subject_name, sub.class_id, c.class_name
    FROM users u
    LEFT JOIN teacher_profiles s ON s.user_id = u.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN classes c ON sub.class_id = c.id
    WHERE u.id = ?
');
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Fetch all subjects for dropdown
$stmt = $pdo->query('SELECT s.*, c.class_name FROM subjects s JOIN classes c ON s.class_id = c.id ORDER BY s.subject_name ASC');
$subjects = $stmt->fetchAll();

// Fetch recent activity
$activity = [];
$stmt = $pdo->prepare('
    SELECT "Login" as type, created_at FROM users WHERE id = ? 
    UNION 
    SELECT "Profile Update" as type, created_at FROM profile_updates WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
');
$stmt->execute([$user_id, $user_id]);
$activity = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject_id = $_POST['subject_id'] ?? null;
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (!$subject_id) {
        $errors[] = 'Subject is required.';
    }
    if ($phone && !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Phone number must be 10-15 digits.';
    }

    if (empty($errors)) {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update users table
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $stmt->execute([$name, $email, $user_id]);

            // Check if teacher profile exists
            $stmt = $pdo->prepare('SELECT id FROM teacher_profiles WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $teacher_profile = $stmt->fetch();

            if ($teacher_profile) {
                // Update teacher profile
                $stmt = $pdo->prepare('UPDATE teacher_profiles SET subject_id = ? WHERE user_id = ?');
                $stmt->execute([$subject_id, $user_id]);
            } else {
                // Insert teacher profile
                $stmt = $pdo->prepare('INSERT INTO teacher_profiles (user_id, subject_id) VALUES (?, ?)');
                $stmt->execute([$user_id, $subject_id]);
            }
            
            // Record profile update
            $stmt = $pdo->prepare('INSERT INTO profile_updates (user_id, updated_fields) VALUES (?, ?)');
            $updated_fields = [];
            if ($name !== $profile['name']) $updated_fields[] = 'name';
            if ($email !== $profile['email']) $updated_fields[] = 'email';
            if ($subject_id != $profile['subject_id']) $updated_fields[] = 'subject';
            if ($bio) $updated_fields[] = 'bio';
            if ($phone) $updated_fields[] = 'phone';
            
            $stmt->execute([$user_id, implode(', ', $updated_fields)]);
            
            $pdo->commit();
            $success = 'Profile updated successfully!';
            
            // Refresh profile data
            $stmt = $pdo->prepare('
                SELECT u.name, u.email, u.created_at, s.subject_id, sub.subject_name, sub.class_id, c.class_name
                FROM users u
                LEFT JOIN teacher_profiles s ON s.user_id = u.id
                LEFT JOIN subjects sub ON s.subject_id = sub.id
                LEFT JOIN classes c ON sub.class_id = c.id
                WHERE u.id = ?
            ');
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            
            // Refresh activity
            $stmt = $pdo->prepare('
                SELECT "Login" as type, created_at FROM users WHERE id = ? 
                UNION 
                SELECT "Profile Update" as type, created_at FROM profile_updates WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ');
            $stmt->execute([$user_id, $user_id]);
            $activity = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
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
                    <h1 class="text-3xl font-bold text-gray-900">Teacher Profile</h1>
                    <p class="mt-2 text-gray-600">Manage your personal information and account settings</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="bg-blue-50 text-blue-800 py-2 px-4 rounded-lg text-sm">
                        Member since: <?php echo date('M d, Y', strtotime($profile['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-24"></div>
                        <div class="px-6 pb-6 relative">
                            <div class="flex justify-center -mt-12">
                                <div class="bg-gray-200 border-2 border-dashed rounded-full w-24 h-24 flex items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($profile['name'] ?? 'Teacher'); ?></h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                                
                                <div class="mt-4 space-y-2">
                                    <?php if ($profile['subject_name']): ?>
                                    <div class="flex items-center justify-center text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        <span><?php echo htmlspecialchars($profile['subject_name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($profile['class_name']): ?>
                                    <div class="flex items-center justify-center text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span><?php echo htmlspecialchars($profile['class_name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone)): ?>
                                    <div class="flex items-center justify-center text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span><?php echo htmlspecialchars($phone); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($bio)): ?>
                                <div class="mt-4 bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 mb-2">About Me</h3>
                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($bio); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="mt-6 bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity</h3>
                        <div class="space-y-4">
                            <?php if (!empty($activity)): ?>
                                <?php foreach ($activity as $item): ?>
                                    <div class="flex items-start">
                                        <div class="bg-blue-100 p-2 rounded-full mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['type']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-2">No recent activity</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Profile Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Edit Profile</h2>
                            <div class="bg-blue-50 text-blue-700 py-1 px-3 rounded-full text-sm font-medium">
                                Educator Account
                            </div>
                        </div>
                        
                        <?php if ($errors): ?>
                            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6">
                                <h3 class="font-bold mb-2">Please fix the following issues:</h3>
                                <ul class="list-disc list-inside space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <?php echo $success; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form action="teacher_profile.php" method="post" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block mb-2 font-medium text-gray-700">Full Name</label>
                                    <input type="text" id="name" name="name" required 
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label for="email" class="block mb-2 font-medium text-gray-700">Email Address</label>
                                    <input type="email" id="email" name="email" required 
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label for="subject_id" class="block mb-2 font-medium text-gray-700">Teaching Subject</label>
                                    <select id="subject_id" name="subject_id" required 
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>" 
                                                <?php if (($profile['subject_id'] ?? '') == $subject['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['class_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="phone" class="block mb-2 font-medium text-gray-700">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" 
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                        placeholder="Optional" />
                                </div>
                                <div class="md:col-span-2">
                                    <label for="bio" class="block mb-2 font-medium text-gray-700">Bio</label>
                                    <textarea id="bio" name="bio" rows="3"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Tell us about your teaching experience..."><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="pt-4 flex justify-end">
                                <button type="submit" 
                                    class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-3 px-6 rounded-lg font-medium hover:opacity-90 transition-opacity shadow-md">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Security Section -->
                    <div class="mt-6 bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Security Settings</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="border border-gray-200 rounded-lg p-5">
                                <h3 class="font-medium text-gray-900 mb-3">Password</h3>
                                <p class="text-gray-600 mb-4">Change your password to keep your account secure</p>
                                <button class="text-blue-600 font-medium hover:text-blue-800 transition-colors">
                                    Change Password
                                </button>
                            </div>
                            
                            <div class="border border-gray-200 rounded-lg p-5">
                                <h3 class="font-medium text-gray-900 mb-3">Two-Factor Authentication</h3>
                                <p class="text-gray-600 mb-4">Add an extra layer of security to your account</p>
                                <button class="text-blue-600 font-medium hover:text-blue-800 transition-colors">
                                    Enable 2FA
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Actions -->
                    <div class="mt-6 bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Account Actions</h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <div>
                                    <h3 class="font-medium text-gray-900">Download Your Data</h3>
                                    <p class="text-gray-600 text-sm">Request a copy of all your personal data</p>
                                </div>
                                <button class="text-blue-600 font-medium hover:text-blue-800 transition-colors">
                                    Request Data
                                </button>
                            </div>
                            
                            <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                                <div>
                                    <h3 class="font-medium text-gray-900">Deactivate Account</h3>
                                    <p class="text-gray-600 text-sm">Temporarily disable your account</p>
                                </div>
                                <button class="text-red-600 font-medium hover:text-red-800 transition-colors">
                                    Deactivate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>