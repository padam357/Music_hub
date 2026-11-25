```php
<?php

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    // Check search input
    if (!isset($_GET['term']) || !is_string($_GET['term'])) {
        echo json_encode([]);
        exit;
    }

    $term = trim($_GET['term']);

    // Validate search length
    if ($term === '' || strlen($term) > 100) {
        echo json_encode([]);
        exit;
    }

    // Search using prepared statements
    $sql = "SELECT music_id, title, artist
            FROM music
            WHERE title LIKE :title_term
            OR artist LIKE :artist_term
            ORDER BY title ASC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);

    $searchTerm = '%' . $term . '%';

    $stmt->execute([
        ':title_term' => $searchTerm,
        ':artist_term' => $searchTerm
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON results
    echo json_encode(
        $results,
        JSON_INVALID_UTF8_SUBSTITUTE
    );

} catch (PDOException $e) {

    // Record technical error privately
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'Database search failed. Please try again.'
    ]);

}

?>
```