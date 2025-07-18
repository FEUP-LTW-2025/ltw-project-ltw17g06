<?php
declare(strict_types=1);

require_once '../utils/database.php';
require_once '../utils/session.php';
require_once '../database/user_class.php';
require_once '../utils/csrf.php';

$session = Session::getInstance();

$csrf_token = $_POST['csrf_token'] ?? '';
if (!CSRF::verifyCSRF($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

if ($session->isLoggedIn()) {
    header('Location: ../pages/profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: ../pages/signup.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email address.';
        header('Location: ../pages/signup.php');
        exit;
    }
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters long.';
        header('Location: ../pages/signup.php');
        exit;
    }
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/\d/', $password)) {
        $_SESSION['error'] = 'Password must contain at least one letter and one number.';
        header('Location: ../pages/signup.php');
        exit;
    }
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: ../pages/signup.php');
        exit;
    }

    // Check if email already exists
    $db = Database::getInstance();
    $stmt = $db->prepare('SELECT COUNT(*) FROM user_registry WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = 'Email is already registered.';
        header('Location: ../pages/signup.php');
        exit;
    }

    User::create($full_name, $email, $password);
    unset($_SESSION['error']);

    $user = User::getUserByEmailPassword($email, $password);
    if ($user) {
        $session->login($user);
        header('Location: ../pages/profile.php');
        exit;
    } else {
        $_SESSION['error'] = 'Signup failed. Please try again.';
        header('Location: ../pages/signup.php');
        exit;
    }
} else {
    http_response_code(405);
    echo "Only POST requests are allowed.";
    exit;
}
?>
