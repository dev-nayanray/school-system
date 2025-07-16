<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirect logged-in users to their dashboard based on role
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
            header('Location: dashboard/admin.php');
            break;
        case 'teacher':
            header('Location: dashboard/teacher.php');
            break;
        case 'student':
            header('Location: dashboard/student.php');
            break;
        default:
            header('Location: dashboard/user.php');
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - School Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login to Your Account</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <form action="login.php" method="post" class="space-y-4">
            <div>
                <label for="email" class="block mb-1 font-semibold">Email</label>
                <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label for="password" class="block mb-1 font-semibold">Password</label>
                <input type="password" id="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Login</button>
        </form>
    </div>
</body>
</html>
