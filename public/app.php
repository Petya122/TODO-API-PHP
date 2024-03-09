<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$databasePath = __DIR__ . '/todo.db';
$db = new SQLite3($databasePath);
$db->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY, feladat TEXT, kategoria TEXT, befejezve BOOLEAN)');

$app = AppFactory::create();

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

    $responseBody = "<p>Feladat: $feladat, Kategória: $kategoria, Befejezve: " . ($befejezve ? 'Igen' : 'Nem') . "</p>";
    $response->getBody()->write($responseBody);
    return $response;
});

$app->run();
?>
