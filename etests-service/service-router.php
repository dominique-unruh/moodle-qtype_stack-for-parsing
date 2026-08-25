<?php

/**
 * Router for the PHP built-in web server:
 *   php -S 0.0.0.0:8080 etests-service/service-router.php
 *
 * Routes:
 *   GET  /health  -> {"status":"ok"}   (for container/k8s health checks)
 *   POST /parse   -> api/public/parseservice.php
 *   *             -> 404 JSON
 */

// STACK's MoodleEmulation.php does a relative `require '../config.php'`, so the
// working directory must be api/public regardless of where the server was
// launched from (the old parse.sh `cd`s there; `php -S` starts at cwd "/").
chdir(__DIR__ . '/../api/public');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
    case '/health':
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        return true;

    case '/parse':
        require __DIR__ . '/../api/public/parseservice.php';
        return true;

    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not found', 'path' => $path]);
        return true;
}
