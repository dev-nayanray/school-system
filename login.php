<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: index.php?error=' . urlencode('Please fill in all fields.'));
        exit();
    }

    // Fetch user by email
    $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header('Location: /dashboard/admin.php');
                break;
            case 'teacher':
                header('Location: /dashboard/teacher.php');
                break;
            case 'student':
                header('Location: /dashboard/student.php');
                break;
            default:
                header('Location: /dashboard/user.php');
                break;
        }
        exit();
    } else {
        // Invalid credentials
        header('Location: index.php?error=' . urlencode('Invalid email or password.'));
        exit();
    }
} else {
    // Invalid request method
    header('Location: index.php');
    exit();
}
?>
