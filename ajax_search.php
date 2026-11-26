
<?php

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $term = $_GET['term'] ?? '';

    if (!is_string($term)) {
        echo json_encode([]);
        exit;
    }

    $term = trim($term);

    if ($term === '' || strlen($term) > 100) {
        echo json_encode([]);
        exit;
    }

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

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC),
        JSON_INVALID_UTF8_SUBSTITUTE
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'Database search failed.'
    ]);
}
