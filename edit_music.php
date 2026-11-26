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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate music ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die('Invalid music ID.');
}

// Fetch existing music
$stmt = $pdo->prepare(
    "SELECT * FROM music WHERE music_id = ?"
);

$stmt->execute([$id]);

$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die('Music record not found.');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {

        $message = 'Invalid security token. Please refresh the page.';

    } else {

        $title = trim($_POST['title'] ?? '');
        $artist = trim($_POST['artist'] ?? '');
        $album = trim($_POST['album'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $year = trim($_POST['release_year'] ?? '');
        $lyrics = trim($_POST['lyrics'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Keep submitted values in the form
        $song['title'] = $title;
        $song['artist'] = $artist;
        $song['album'] = $album;
        $song['genre'] = $genre;
        $song['release_year'] = $year;
        $song['lyrics'] = $lyrics;
        $song['description'] = $description;

        if ($title === '' || $artist === '') {

            $message = 'Title and artist are required.';

        } elseif (
            strlen($title) > 150 ||
            strlen($artist) > 100 ||
            strlen($album) > 150 ||
            strlen($genre) > 50
        ) {

            $message = 'One or more fields are too long.';

        } elseif (strlen($lyrics) > 20000) {

            $message = 'Lyrics must be 20,000 characters or fewer.';

        } elseif (strlen($description) > 5000) {

            $message = 'Description must be 5,000 characters or fewer.';

        } elseif (
            $year !== '' &&
            (
                !ctype_digit($year) ||
                (int)$year < 1000 ||
                (int)$year > (int)date('Y') + 1
            )
        ) {

            $message = 'Please enter a valid release year.';

        } else {

            try {

                $sql = "UPDATE music SET
                        title = ?,
                        artist = ?,
                        album = ?,
                        genre = ?,
                        release_year = ?,
                        lyrics = ?,
                        description = ?
                        WHERE music_id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $title,
                    $artist,
                    $album !== '' ? $album : null,
                    $genre !== '' ? $genre : null,
                    $year === '' ? null : (int)$year,
                    $lyrics !== '' ? $lyrics : null,
                    $description !== '' ? $description : null,
                    $id
                ]);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: index.php?updated=1');
                exit;

            } catch (PDOException $e) {

                error_log($e->getMessage());

                $message = 'Unable to update music. Please try again.';
            }
        }
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

echo $twig->render('edit_music.html.twig', [

    'id' => $id,
    'song' => $song,
    'message' => $message,
    'csrf_token' => $_SESSION['csrf_token'],
    'current_year' => (int)date('Y') + 1

]);
