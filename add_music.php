```php
<?php
require_once 'db.php';

// Only logged-in users can add music
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate maths CAPTCHA
function generateCaptcha() {
    $number1 = random_int(1, 9);
    $number2 = random_int(1, 9);

    $_SESSION['captcha_answer'] = $number1 + $number2;
    $_SESSION['captcha_question'] = "$number1 + $number2";
}

if (!isset($_SESSION['captcha_answer'])) {
    generateCaptcha();
}

$message = '';

$title = '';
$artist = '';
$album = '';
$genre = '';
$release_year = '';
$lyrics = '';
$description = '';

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)) {

        $message = 'Invalid security token. Please refresh the page.';

    } else {

        $title = trim($_POST['title'] ?? '');
        $artist = trim($_POST['artist'] ?? '');
        $album = trim($_POST['album'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $release_year = trim($_POST['release_year'] ?? '');
        $lyrics = trim($_POST['lyrics'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $captcha = trim($_POST['captcha'] ?? '');

        if ($title === '' || $artist === '') {
            $message = 'Music title and artist are required.';

        } elseif (strlen($title) > 150 ||
                  strlen($artist) > 100 ||
                  strlen($album) > 150 ||
                  strlen($genre) > 50) {

            $message = 'One or more fields exceed the allowed length.';

        } elseif (strlen($lyrics) > 20000) {
            $message = 'Lyrics must be 20,000 characters or fewer.';

        } elseif (strlen($description) > 5000) {
            $message = 'Description must be 5,000 characters or fewer.';

        } elseif ($release_year !== '' &&
                 (!ctype_digit($release_year) ||
                  (int)$release_year < 1000 ||
                  (int)$release_year > (int)date('Y') + 1)) {

            $message = 'Please enter a valid release year.';

        } elseif ($captcha === '' ||
                  !ctype_digit($captcha) ||
                  (int)$captcha !== (int)$_SESSION['captcha_answer']) {

            $message = 'Incorrect CAPTCHA answer. Please try again.';

        } else {

            try {

                $sql = "INSERT INTO music
                        (title, artist, album, genre, release_year, lyrics, description)
                        VALUES
                        (:title, :artist, :album, :genre, :release_year, :lyrics, :description)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':title' => $title,
                    ':artist' => $artist,
                    ':album' => $album !== '' ? $album : null,
                    ':genre' => $genre !== '' ? $genre : null,
                    ':release_year' => $release_year !== '' ? (int)$release_year : null,
                    ':lyrics' => $lyrics !== '' ? $lyrics : null,
                    ':description' => $description !== '' ? $description : null
                ]);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                unset($_SESSION['captcha_answer']);
                unset($_SESSION['captcha_question']);

                header('Location: index.php?added=1');
                exit;

            } catch (PDOException $e) {

                error_log($e->getMessage());
                $message = 'Unable to add music. Please try again later.';
            }
        }

        // Generate a fresh CAPTCHA after a submission
        generateCaptcha();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Music - MusicHub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 30px 15px;
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
            color: #222;
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
            min-height: 110px;
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            background: #222;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #444;
        }

        .message {
            background: #ffe6e6;
            color: #b00020;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .captcha {
            background: #f1f1f1;
            padding: 12px;
            border-radius: 5px;
            margin-top: 15px;
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

    <h1>Add New Music</h1>

    <p style="text-align:center;">
        Add a new song to your MusicHub library.
    </p>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= clean($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_music.php">

        <input type="hidden" name="csrf_token"
               value="<?= clean($_SESSION['csrf_token']) ?>">

        <label>Music Title *</label>
        <input type="text" name="title"
               maxlength="150"
               value="<?= clean($title) ?>"
               required>

        <label>Artist *</label>
        <input type="text" name="artist"
               maxlength="100"
               value="<?= clean($artist) ?>"
               required>

        <label>Album</label>
        <input type="text" name="album"
               maxlength="150"
               value="<?= clean($album) ?>">

        <label>Genre</label>
        <input type="text" name="genre"
               maxlength="50"
               value="<?= clean($genre) ?>">

        <label>Release Year</label>
        <input type="number" name="release_year"
               min="1000"
               max="<?= (int)date('Y') + 1 ?>"
               value="<?= clean($release_year) ?>">

        <label>Lyrics</label>
        <textarea name="lyrics"><?= clean($lyrics) ?></textarea>

        <label>Description</label>
        <textarea name="description"><?= clean($description) ?></textarea>

        <div class="captcha">
            <label>
                Security Question: What is
                <?= clean($_SESSION['captcha_question']) ?>?
            </label>

            <input type="number" name="captcha"
                   placeholder="Enter your answer"
                   required>
        </div>

        <button type="submit">Add Music</button>

    </form>

    <div class="links">
        <a href="index.php">Back to MusicHub</a>
    </div>

</div>

</body>
</html>
```