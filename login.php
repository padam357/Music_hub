```php
<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if (isset($_GET['registered'])) {
    $message = 'Registration successful. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
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

function clean($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - MusicHub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 40px 15px;
        }

        .container {
            max-width: 420px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #222;
        }

        label {
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 8px 0 18px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #222;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #444;
        }

        .message {
            background: #e8f5e9;
            color: #236b35;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: #222;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>MusicHub Login</h1>
    <p style="text-align:center;">Welcome back!</p>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= clean($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input type="hidden" name="csrf_token"
               value="<?= clean($_SESSION['csrf_token']) ?>">

        <label>Email Address</label>
        <input type="email" name="email"
               maxlength="150" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>

    </form>

    <div class="links">
        <p>Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <a href="index.php">Back to MusicHub</a>
    </div>

</div>

</body>
</html>
```