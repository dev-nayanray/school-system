<?php
require_once 'includes/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (!$password) {
        $errors[] = 'Password is required.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already registered.';
        } else {
            // Insert new user with role 'student'
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hashed_password, 'student']);
            $success = 'Registration successful. You can now <a href="index.php" class="text-blue-600 underline">login</a>.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - School Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Student Registration</h2>

        <?php if ($errors): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post" class="space-y-4">
            <div>
                <label for="name" class="block mb-1 font-semibold">Name</label>
                <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" />
            </div>
            <div>
                <label for="email" class="block mb-1 font-semibold">Email</label>
                <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
            </div>
            <div>
                <label for="password" class="block mb-1 font-semibold">Password</label>
                <input type="password" id="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label for="confirm_password" class="block mb-1 font-semibold">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Register</button>
        </form>

        <p class="mt-4 text-center">
            Already have an account? <a href="index.php" class="text-blue-600 underline">Login here</a>.
        </p>
    </div>
</body>
</html>
