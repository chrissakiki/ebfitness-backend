<?php

require __DIR__ . '/../vendor/autoload.php';
define('PROJECT_ROOT', __DIR__ . '/..');
// ENV
use Dotenv\Dotenv;

// Point to the root directory (where your .env file exists)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');  // Make sure this is pointing to your project's root
$dotenv->load();  // Load the environment variables from the .env file


use Framework\Router;

require '../helpers.php';

$router = new Router();

$routes = require basePath('routes.php');


// to remove any query parameters;
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$router->handleRoute($uri, $requestMethod);
