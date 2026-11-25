```php
<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isLoggedIn = isset($_SESSION['user_id']);

$search = '';
$searchError = '';
$databaseError = '';
$songs = [];

function clean($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate search input
if (isset($_GET['search'])) {

    if (!is_string($_GET['search'])) {
        $searchError = "Invalid search input.";
    } else {
        $search = trim($_GET['search']);

        if (strlen($search) > 100) {
            $searchError = "Search must not exceed 100 characters.";
            $search = '';
        }
    }
}

try {

    if ($searchError === '' && $search !== '') {

        $sql = "SELECT * FROM music
                WHERE title LIKE :title_search
                OR artist LIKE :artist_search
                OR album LIKE :album_search
                OR genre LIKE :genre_search
                ORDER BY music_id DESC";

        $stmt = $pdo->prepare($sql);

        $searchTerm = '%' . $search . '%';

        $stmt->execute([
            ':title_search' => $searchTerm,
            ':artist_search' => $searchTerm,
            ':album_search' => $searchTerm,
            ':genre_search' => $searchTerm
        ]);

    } else {

        $stmt = $pdo->query(
            "SELECT * FROM music ORDER BY music_id DESC"
        );
    }

    if ($searchError === '') {
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {

    error_log($e->getMessage());
    $databaseError = "Unable to load music. Please try again later.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MusicHub - Music Library</title>

<style>

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    color: #222;
}

header {
    background: #202040;
    color: white;
    padding: 25px;
    text-align: center;
}

.container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.account-area {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}

.welcome {
    font-weight: bold;
    color: #202040;
}

.search-area {
    margin: 25px 0;
    position: relative;
}

.search-form {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

input[type="text"] {
    padding: 12px;
    width: 280px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button, .btn {
    background: #3939a5;
    color: white;
    padding: 11px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
}

button:hover, .btn:hover {
    background: #25256e;
}

.register {
    background: #16834a;
}

.register:hover {
    background: #106437;
}

.logout {
    background: #c0392b;
}

.logout:hover {
    background: #962d22;
}

.ajax-button {
    background: #16834a;
}

.ajax-button:hover {
    background: #106437;
}

#suggestions {
    display: none;
    width: 100%;
    max-width: 500px;
    background: white;
    border: 1px solid #ccc;
    margin-top: 5px;
    border-radius: 5px;
    overflow: hidden;
}

.suggestion {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.suggestion:hover {
    background: #eeeeff;
}

.song {
    background: white;
    padding: 20px;
    margin-bottom: 18px;
    border-radius: 8px;
    box-shadow: 0 2px 7px rgba(0,0,0,0.08);
}

.song h2 {
    color: #202040;
    margin-top: 0;
}

.details {
    color: #555;
    line-height: 1.7;
}

details {
    margin-top: 12px;
}

summary {
    cursor: pointer;
    color: #3939a5;
    font-weight: bold;
}

.actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.edit {
    background: #16834a;
}

.delete {
    background: #c0392b;
}

.message {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.error {
    background: #ffecec;
    color: #c0392b;
    padding: 12px;
    border-radius: 5px;
}

.success {
    background: #e5f6e9;
    color: #236b35;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
}

#ajaxStatus {
    margin-top: 8px;
    color: #555;
}

footer {
    text-align: center;
    padding: 20px;
    color: #777;
}

@media (max-width: 600px) {

    .container {
        padding: 12px;
    }

    input[type="text"] {
        width: 100%;
    }

    .account-area {
        justify-content: flex-start;
    }
}

</style>

</head>

<body>

<header>
    <h1>MusicHub</h1>
    <p>Your Personal Music Library</p>
</header>

<div class="container">

    <div class="top-bar">

        <h2>Music Collection</h2>

        <div class="account-area">

            <?php if ($isLoggedIn): ?>

                <span class="welcome">
                    Welcome, <?= clean($_SESSION['username'] ?? 'User') ?>
                </span>

                <a href="add_music.php" class="btn">
                    + Add New Music
                </a>

                <form method="POST" action="logout.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= clean($_SESSION['csrf_token']) ?>"
                    >

                    <button type="submit" class="logout">
                        Logout
                    </button>

                </form>

            <?php else: ?>

                <a href="login.php" class="btn">
                    Login
                </a>

                <a href="register.php" class="btn register">
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>

    <?php if (isset($_GET['added']) && $_GET['added'] === '1' && $isLoggedIn): ?>

        <div class="success">
            Music added successfully!
        </div>

    <?php endif; ?>

    <!-- SEARCH SECTION -->

    <div class="search-area">

        <form method="GET" action="index.php" class="search-form">

            <input
                type="text"
                id="musicSearch"
                name="search"
                maxlength="100"
                placeholder="Enter song or artist name..."
                value="<?php echo clean($search); ?>"
                autocomplete="off"
            >

            <button type="submit">
                Normal Search
            </button>

            <button
                type="button"
                id="ajaxSearchButton"
                class="ajax-button"
            >
                AJAX Search
            </button>

            <a href="index.php" class="btn">
                Show All
            </a>

        </form>

        <div id="suggestions"></div>

        <div id="ajaxStatus" aria-live="polite"></div>

    </div>

    <?php if ($searchError !== ''): ?>

        <p class="error">
            <?php echo clean($searchError); ?>
        </p>

    <?php endif; ?>

    <?php if ($databaseError !== ''): ?>

        <p class="error">
            <?php echo clean($databaseError); ?>
        </p>

    <?php else: ?>

        <p>
            <?php echo count($songs); ?> music record(s) found.
        </p>

        <!-- MUSIC RECORDS -->

        <?php if (count($songs) > 0): ?>

            <?php foreach ($songs as $song): ?>

                <div class="song">

                    <h2>
                        <?php echo clean($song['title']); ?>
                    </h2>

                    <div class="details">

                        <strong>Artist:</strong>
                        <?php echo clean($song['artist']); ?><br>

                        <strong>Album:</strong>
                        <?php echo clean($song['album']); ?><br>

                        <strong>Genre:</strong>
                        <?php echo clean($song['genre']); ?><br>

                        <strong>Release Year:</strong>
                        <?php echo clean($song['release_year']); ?><br>

                        <strong>Description:</strong>
                        <?php echo clean($song['description']); ?>

                    </div>

                    <?php if (!empty($song['lyrics'])): ?>

                        <details>
                            <summary>View Lyrics</summary>

                            <p>
                                <?php echo nl2br(clean($song['lyrics'])); ?>
                            </p>
                        </details>

                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>

                        <div class="actions">

                            <a
                                href="edit_music.php?id=<?php echo (int)$song['music_id']; ?>"
                                class="btn edit"
                            >Edit</a>

                            <a
                                href="delete_music.php?id=<?php echo (int)$song['music_id']; ?>"
                                class="btn delete"
                            >Delete</a>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="message">

                <h3>No music found</h3>

                <p>Try another song or artist name.</p>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<footer>
    <p>
        &copy; <?php echo date('Y'); ?> MusicHub. All rights reserved.
    </p>
</footer>

<!-- AJAX SEARCH JAVASCRIPT -->

<script>

const searchInput = document.getElementById('musicSearch');
const ajaxButton = document.getElementById('ajaxSearchButton');
const suggestionBox = document.getElementById('suggestions');
const ajaxStatus = document.getElementById('ajaxStatus');

function performAjaxSearch() {

    const term = searchInput.value.trim();

    suggestionBox.innerHTML = '';
    ajaxStatus.textContent = '';

    if (term === '') {
        ajaxStatus.textContent = 'Please enter a song or artist name.';
        return;
    }

    if (term.length > 100) {
        ajaxStatus.textContent = 'Search must not exceed 100 characters.';
        return;
    }

    ajaxButton.disabled = true;
    ajaxButton.textContent = 'Searching...';

    fetch('ajax_search.php?term=' + encodeURIComponent(term))

        .then(function(response) {

            if (!response.ok) {
                throw new Error('Server returned an error.');
            }

            return response.json();

        })

        .then(function(data) {

            suggestionBox.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                ajaxStatus.textContent = 'No music found.';
                suggestionBox.style.display = 'none';
                return;
            }

            ajaxStatus.textContent = data.length + ' result(s) found.';

            data.forEach(function(song) {

                const item = document.createElement('div');

                item.className = 'suggestion';

                item.textContent = song.title + ' - ' + song.artist;

                item.addEventListener('click', function() {

                    searchInput.value = song.title;
                    searchInput.form.submit();

                });

                suggestionBox.appendChild(item);

            });

            suggestionBox.style.display = 'block';

        })

        .catch(function(error) {

            console.error('AJAX Search Error:', error);

            ajaxStatus.textContent =
                'Search failed. Please check the AJAX file.';

        })

        .finally(function() {

            ajaxButton.disabled = false;
            ajaxButton.textContent = 'AJAX Search';

        });

}

ajaxButton.addEventListener('click', performAjaxSearch);

document.addEventListener('click', function(event) {

    if (!event.target.closest('.search-area')) {
        suggestionBox.style.display = 'none';
    }

});

</script>

</body>
</html>
```