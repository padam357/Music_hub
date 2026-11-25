```php
<?php
require_once 'db.php';

// Make sure the session is running.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create a CSRF token if one does not already exist.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect users who are already logged in.
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$messageType = 'error';

$username = '';
$email = '';

function clean($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check CSRF token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)) {

        $message = "Invalid security token. Please try again.";

    } else {

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!is_string($username) ||
            !is_string($email) ||
            !is_string($password) ||
            !is_string($confirmPassword)) {

            $message = "Please enter valid information.";

        } elseif ($username === '' || $email === '' ||
                  $password === '' || $confirmPassword === '') {

            $message = "Please complete all fields.";

        } elseif (strlen($username) > 100) {

            $message = "Username must not be longer than 100 characters.";

        } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";

        } elseif (strlen($password) < 8) {

            $message = "Password must contain at least 8 characters.";

        } elseif ($password !== $confirmPassword) {

            $message = "Passwords do not match.";

        } else {

            try {
                // Hash the password before saving it.
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO musichub_users (username, email, password)
                        VALUES (:username, :email, :password)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);

                // Create a new security token.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: login.php?registered=1");
                exit;

            } catch (PDOException $e) {

                // Handle duplicate email addresses.
                if ($e->getCode() === '23000') {
                    $message = "This email address is already registered.";
                } else {
                    error_log($e->getMessage());
                    $message = "Registration failed. Please try again later.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MusicHub</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #f2f4f8;
        }

        .container {
            max-width: 450px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            color: #222;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 5px;
            background: #2457d6;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1742ad;
        }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #ffe8e8;
            color: #a00000;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: #2457d6;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>MusicHub</h1>
    <p class="subtitle">Create your MusicHub account</p>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?php echo clean($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo clean($_SESSION['csrf_token']); ?>"
        >

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            maxlength="100"
            value="<?php echo clean($username); ?>"
            required
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            value="<?php echo clean($email); ?>"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            required
        >

        <label for="confirm_password">Confirm Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            required
        >

        <button type="submit">Create Account</button>

    </form>

    <div class="links">
        Already have an account?
        <a href="login.php">Login here</a>
        <br><br>
        <a href="index.php">Back to MusicHub</a>
    </div>

</div>

</body>
</html>
```