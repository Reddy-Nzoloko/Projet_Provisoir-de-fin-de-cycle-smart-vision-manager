<?php

declare(strict_types=1);

require __DIR__ . '/app/Core/Database.php';
require __DIR__ . '/app/Core/Session.php';
require __DIR__ . '/app/Core/Auth.php';
require __DIR__ . '/app/Models/User.php';
require __DIR__ . '/app/Models/Dashboard.php';
require __DIR__ . '/app/Controllers/AuthController.php';
require __DIR__ . '/app/Controllers/DashboardController.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Models\Dashboard;
use App\Models\User;

try {
    Session::start();
    $connection = Database::connect();
    $users = new User($connection);
    $action = $_GET['action'] ?? 'dashboard';

    if (!is_string($action)) {
        http_response_code(404);
        require __DIR__ . '/app/Views/errors/not-found.php';
        exit;
    }

    if ($action === 'login' || $action === 'setup' || $action === 'logout') {
        $controller = new AuthController($users);

        match ($action) {
            'login' => $controller->login(),
            'setup' => $controller->setup(),
            'logout' => $controller->logout(),
        };
        exit;
    }

    if ($action !== 'dashboard') {
        http_response_code(404);
        require __DIR__ . '/app/Views/errors/not-found.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit;
    }

    if (Auth::user() === null) {
        header('Location: index.php?action=login', true, 303);
        exit;
    }

    (new DashboardController(new Dashboard($connection)))->index();
} catch (PDOException $exception) {
    error_log('Smart Vision Manager database error: ' . $exception->getMessage());
    http_response_code(503);
    require __DIR__ . '/app/Views/errors/database.php';
}
