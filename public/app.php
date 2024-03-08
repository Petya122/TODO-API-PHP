<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$database = new SQLite3('todo.db');

$database->exec('CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY, task TEXT, completed INTEGER DEFAULT 0)');

$app->post('/add-todo', function (Request $request, Response $response, $args) use ($database) {
    $data = $request->getParsedBody();
    $task = $data['task'] ?? '';
    $completed = isset($data['completed']) ? 1 : 0;

    $statement = $database->prepare('INSERT INTO tasks (task, completed) VALUES (:task, :completed)');
    $statement->bindValue(':task', $task, SQLITE3_TEXT);
    $statement->bindValue(':completed', $completed, SQLITE3_INTEGER);
    $statement->execute();

    return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);
});

$app->get('/tasks', function (Request $request, Response $response, $args) use ($database) {
    $tasks = [];
    $result = $database->query('SELECT * FROM tasks');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tasks[] = $row;
    }

    return $response->withHeader('Content-Type', 'application/json')->withJson($tasks);
});

$app->run();

$database->close();
