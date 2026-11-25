```php
<?php
require_once 'db.php';

// Only logged-in users can edit music
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function clean($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate music ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid music ID.");
}

// Fetch existing music
$stmt = $pdo->prepare(
    "SELECT * FROM music WHERE music_id = ?"
);
$stmt->execute([$id]);

$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Music record not found.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {

        $message = "Invalid security token. Please refresh the page.";

    } else {

        $title = trim($_POST['title'] ?? '');
        $artist = trim($_POST['artist'] ?? '');
        $album = trim($_POST['album'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $year = trim($_POST['release_year'] ?? '');
        $lyrics = trim($_POST['lyrics'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Keep submitted values if validation fails
        $song['title'] = $title;
        $song['artist'] = $artist;
        $song['album'] = $album;
        $song['genre'] = $genre;
        $song['release_year'] = $year;
        $song['lyrics'] = $lyrics;
        $song['description'] = $description;

        if ($title === '' || $artist === '') {

            $message = "Title and artist are required.";

        } elseif (
            strlen($title) > 150 ||
            strlen($artist) > 100 ||
            strlen($album) > 150 ||
            strlen($genre) > 50
        ) {

            $message = "One or more fields are too long.";

        } elseif (strlen($lyrics) > 20000) {

            $message = "Lyrics must be 20,000 characters or fewer.";

        } elseif (strlen($description) > 5000) {

            $message = "Description must be 5,000 characters or fewer.";

        } elseif (
            $year !== '' &&
            (
                !ctype_digit($year) ||
                (int)$year < 1000 ||
                (int)$year > 2155
            )
        ) {

            $message = "Please enter a valid release year.";

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

                header("Location: index.php?updated=1");
                exit;

            } catch (PDOException $e) {

                error_log($e->getMessage());
                $message = "Unable to update music. Please try again.";
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

<title>Edit Music - MusicHub</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 30px 15px;
    color: #222;
}

.container {
    max-width: 650px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

h1 {
    text-align: center;
    color: #202040;
}

label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
}

input, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 7px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 15px;
}

textarea {
    resize: vertical;
}

button {
    width: 100%;
    padding: 13px;
    margin-top: 20px;
    background: #3939a5;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #25256e;
}

.message {
    background: #ffecec;
    color: #c0392b;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.links {
    text-align: center;
    margin-top: 20px;
}

a {
    color: #3939a5;
}

</style>

</head>

<body>

<div class="container">

    <h1>Edit Music</h1>

    <p>Edit your selected music record below.</p>

    <?php if ($message !== ''): ?>

        <div class="message">
            <?= clean($message) ?>
        </div>

    <?php endif; ?>

    <form method="POST"
          action="edit_music.php?id=<?= (int)$id ?>">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= clean($_SESSION['csrf_token']) ?>"
        >

        <label>Song Title *</label>

        <input
            type="text"
            name="title"
            maxlength="150"
            value="<?= clean($song['title']) ?>"
            required
        >

        <label>Artist Name *</label>

        <input
            type="text"
            name="artist"
            maxlength="100"
            value="<?= clean($song['artist']) ?>"
            required
        >

        <label>Album Name</label>

        <input
            type="text"
            name="album"
            maxlength="150"
            value="<?= clean($song['album']) ?>"
        >

        <label>Genre</label>

        <input
            type="text"
            name="genre"
            maxlength="50"
            value="<?= clean($song['genre']) ?>"
        >

        <label>Release Year</label>

        <input
            type="number"
            name="release_year"
            min="1000"
            max="2155"
            value="<?= clean($song['release_year']) ?>"
        >

        <label>Lyrics</label>

        <textarea
            name="lyrics"
            rows="6"
            maxlength="20000"
        ><?= clean($song['lyrics']) ?></textarea>

        <label>Description</label>

        <textarea
            name="description"
            rows="4"
            maxlength="5000"
        ><?= clean($song['description']) ?></textarea>

        <button type="submit">
            Update Music
        </button>

    </form>

    <div class="links">
        <a href="index.php">Back to Music Library</a>
    </div>

</div>

</body>
</html>
```