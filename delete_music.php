
<?php
require_once 'db.php';

// Only logged-in users can delete music
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Make sure a CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get and validate music ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid music ID.");
}

// Find the music record
$stmt = $pdo->prepare(
    "SELECT music_id, title FROM music WHERE music_id = ?"
);
$stmt->execute([$id]);

$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Music record not found.");
}

// Process deletion only through POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        die("Invalid security token. Please refresh and try again.");
    }

    try {
        // Delete using a prepared statement
        $stmt = $pdo->prepare(
            "DELETE FROM music WHERE music_id = ?"
        );

        $stmt->execute([$id]);

        // Generate a new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Return to music library
        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        die("Unable to delete music. Please try again.");
    }
}

// Safe output function
function clean($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Delete Music - MusicHub</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 20px;
    color: #222;
}

.container {
    max-width: 500px;
    margin: 60px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

h1 {
    color: #c0392b;
}

button {
    background: #c0392b;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 15px;
}

button:hover {
    background: #922b21;
}

.cancel {
    display: inline-block;
    margin-left: 15px;
    color: #3939a5;
    text-decoration: none;
}
</style>

</head>

<body>

<div class="container">

    <h1>Delete Music</h1>

    <p>
        Are you sure you want to delete
        <strong><?php echo clean($song['title']); ?></strong>?
    </p>

    <p>This action cannot be undone.</p>

    <form method="POST"
          action="delete_music.php?id=<?php echo (int)$id; ?>">

        <input type="hidden" name="csrf_token"
               value="<?php echo clean($_SESSION['csrf_token']); ?>">

        <button type="submit">Yes, Delete Music</button>

        <a href="index.php" class="cancel">Cancel</a>

    </form>

</div>

</body>
</html>
