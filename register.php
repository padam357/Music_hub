
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {

        $message = 'Invalid security token. Please try again.';

    } else {

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (
            !is_string($username) ||
            !is_string($email) ||
            !is_string($password) ||
            !is_string($confirmPassword)
        ) {

            $message = 'Please enter valid information.';

        } elseif (
            $username === '' ||
            $email === '' ||
            $password === '' ||
            $confirmPassword === ''
        ) {

            $message = 'Please complete all fields.';

        } elseif (strlen($username) > 100) {

            $message = 'Username must not be longer than 100 characters.';

        } elseif (
            strlen($email) > 150 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $message = 'Please enter a valid email address.';

        } elseif (strlen($password) < 8) {

            $message = 'Password must contain at least 8 characters.';

        } elseif ($password !== $confirmPassword) {

            $message = 'Passwords do not match.';

        } else {

            try {

                // Hash password
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $sql = "INSERT INTO musichub_users
                        (username, email, password)
                        VALUES (:username, :email, :password)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);

                // Generate new CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: login.php?registered=1');
                exit;

            } catch (PDOException $e) {

                if ($e->getCode() === '23000') {

                    $message = 'This email address is already registered.';

                } else {

                    error_log($e->getMessage());

                    $message = 'Registration failed. Please try again later.';
                }
            }
        }
    }
}

// Set up Twig
$loader = new \Twig\Loader\FilesystemLoader(
    __DIR__ . '/templates'
);

$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html'
]);

echo $twig->render('register.html.twig', [
    'message' => $message,
    'username' => $username,
    'email' => $email,
    'csrf_token' => $_SESSION['csrf_token']
]);
