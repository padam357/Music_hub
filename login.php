
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if (isset($_GET['registered'])) {
    $message = 'Registration successful. You can now log in.';
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $message = 'Invalid security token. Please refresh the page.';

    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $message = 'Please enter your email and password.';

        } else {

            try {

                $stmt = $pdo->prepare(
                    "SELECT user_id, username, email, password
                     FROM musichub_users
                     WHERE email = :email
                     LIMIT 1"
                );

                $stmt->execute([':email' => $email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];

                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: index.php');
                    exit;

                } else {
                    $message = 'Invalid email address or password.';
                }

            } catch (PDOException $e) {

                error_log($e->getMessage());

                $message = 'Login failed. Please try again later.';
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

echo $twig->render('login.html.twig', [
    'message' => $message,
    'csrf_token' => $_SESSION['csrf_token']
]);
