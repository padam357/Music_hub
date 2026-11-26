
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

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

if (isset($_GET['search'])) {

    if (!is_string($_GET['search'])) {
        $searchError = 'Invalid search input.';
    } else {
        $search = trim($_GET['search']);

        if (strlen($search) > 100) {
            $searchError = 'Search must not exceed 100 characters.';
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

    $databaseError = 'Unable to load music. Please try again later.';
}

$loader = new \Twig\Loader\FilesystemLoader(
    __DIR__ . '/templates'
);

$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'autoescape' => 'html'
]);

echo $twig->render('index.html.twig', [

    'isLoggedIn' => $isLoggedIn,

    'username' => $_SESSION['username'] ?? 'User',

    'csrf_token' => $_SESSION['csrf_token'],

    'search' => $search,

    'searchError' => $searchError,

    'databaseError' => $databaseError,

    'songs' => $songs,

   'added' => isset($_GET['added']) && $_GET['added'] === '1',

'updated' => isset($_GET['updated']) && $_GET['updated'] === '1',

'deleted' => isset($_GET['deleted']) && $_GET['deleted'] === '1'

]);
