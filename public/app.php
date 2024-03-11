<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$databasePath = __DIR__ . '/todo.db';
$db = new SQLite3($databasePath);
$db->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY, feladat TEXT, kategoria TEXT, befejezve BOOLEAN)');

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) use ($db) {
    $todos = [];
    $result = $db->query('SELECT * FROM todos');

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $todos[] = $row;
    }

    return $response->withJson($todos);
});

$app->post('/', function (Request $request, Response $response, $args) use ($db) {
    $postData = $request->getParsedBody();
    $feladat = $postData['feladat'] ?? '';
    $kategoria = $postData['kategoria'] ?? '';
    $befejezve = isset($postData['befejezve']) ? 1 : 0;

    $stmt = $db->prepare('INSERT INTO todos (feladat, kategoria, befejezve) VALUES (:feladat, :kategoria, :befejezve)');
    $stmt->bindValue(':feladat', $feladat, SQLITE3_TEXT);
    $stmt->bindValue(':kategoria', $kategoria, SQLITE3_TEXT);
    $stmt->bindValue(':befejezve', $befejezve, SQLITE3_INTEGER);
    $stmt->execute();

    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->delete('/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'] ?? null;

    if (!$id) {
        return $response->withStatus(400)->withJson(['error' => 'Missing todo ID']);
    }

    $stmt = $db->prepare('DELETE FROM todos WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    return $response->withStatus(204);
});

$app->patch('/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'] ?? null;

    if (!$id) {
        return $response->withStatus(400)->withJson(['error' => 'Missing todo ID']);
    }

    $postData = $request->getParsedBody();
    $feladat = $postData['feladat'] ?? null;
    $kategoria = $postData['kategoria'] ?? null;
    $befejezve = isset($postData['befejezve']) ? 1 : 0;

    $setStatements = [];
    if ($feladat !== null) {
        $setStatements[] = 'feladat = :feladat';
    }
    if ($kategoria !== null) {
        $setStatements[] = 'kategoria = :kategoria';
    }
    if ($befejezve !== null) {
        $setStatements[] = 'befejezve = :befejezve';
    }

    if (empty($setStatements)) {
        return $response->withStatus(400)->withJson(['error' => 'No fields to update']);
    }

    $sql = 'UPDATE todos SET ' . implode(', ', $setStatements) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    if ($feladat !== null) {
        $stmt->bindValue(':feladat', $feladat, SQLITE3_TEXT);
    }
    if ($kategoria !== null) {
        $stmt->bindValue(':kategoria', $kategoria, SQLITE3_TEXT);
    }
    if ($befejezve !== null) {
        $stmt->bindValue(':befejezve', $befejezve, SQLITE3_INTEGER);
    }
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    return $response->withStatus(204);
});

$app->run();
?>
