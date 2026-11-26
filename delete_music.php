<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Login protection
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate music ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die('Invalid music ID.');
}

// Find music record
$stmt = $pdo->prepare(
    "SELECT music_id, title FROM music WHERE music_id = ?"
);

$stmt->execute([$id]);

$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die('Music record not found.');
}

// Process deletion only through POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        die('Invalid security token. Please refresh and try again.');
    }

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM music WHERE music_id = ?"
        );

        $stmt->execute([$id]);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: index.php?deleted=1');
        exit;

    } catch (PDOException $e) {

        error_log($e->getMessage());

        die('Unable to delete music. Please try again.');
    }
}

// Twig setup
$loader = new \Twig\Loader\FilesystemLoader(
    __DIR__ . '/templates'
);

$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html'
]);

echo $twig->render('delete_music.html.twig', [

    'id' => $id,
    'song' => $song,
    'csrf_token' => $_SESSION['csrf_token']

]);
