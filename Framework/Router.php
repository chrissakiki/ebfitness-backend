<?php

namespace Framework;

use App\Controllers\ErrorController;
use Framework\Middleware\AuthMiddleware;

class Router
{

    protected $routes = [];

    /**
     * Add a new route
     *
     * @param string $method
     * @param string $uri
     * @param string $action
     * @param array $middleware
     * @return void
     */

    public function registerRoute($method, $uri, $action, $middleware)
    {

        $uri = '/api' . $uri;

        list($controller, $controllerMethod)  = explode('@', $action);

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller,
            'controllerMethod' => $controllerMethod,
            'middleware' => $middleware,

        ];
    }

    /**
     * Add Get Route
     * 
     * @param string $uri
     * @param string $controller
     * @param string $middleware
     */

    public function get($uri, $controller, $middleware = [])
    {
        $this->registerRoute('GET', $uri, $controller, $middleware);
    }

    /**
     * Add a POST route
     * @param string $uri
     * @param string $controller
     * @param array $middleware
     * @return void
     */

    public function post($uri, $controller, $middleware = [])
    {
        $this->registerRoute('POST', $uri, $controller, $middleware);
    }

    /**
     * Add a PUT route
     * @param string $uri
     * @param string $controller
     * @param array $middleware
     * @return void
     */

    public function put($uri, $controller, $middleware = [])
    {
        $this->registerRoute('PUT', $uri, $controller, $middleware);
    }

    /**
     * Add a DELETE route
     * @param string $uri
     * @param string $controller
     * @param array $middleware
     * @return void
     */

    public function delete($uri, $controller, $middleware = [])
    {
        $this->registerRoute('DELETE', $uri, $controller, $middleware);
    }

    /**
     * Route the request
     * 
     * @param string $uri
     * @param string requestMethod
     * @return void
     */

    public function handleRoute($uri, $requestMethod)
    {

        $allowedOrigins = [
            "http://localhost:4173",
            "http://localhost:5173",
            "http://localhost:5174",
            "https://ebfitness.co",
            "https://www.ebfitness.co",
            "https://staging.ebfitness.co",
            "http://ebfitness.co",
            "http://www.ebfitness.co"
        ];

        // Get the origin of the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';


        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin");
        }

        // header("Access-Control-Allow-Origin: http://localhost:5173"); 

        header("Access-Control-Allow-Credentials: true");             // Allow credentials (cookie)        


        // If the request method is OPTIONS, terminate the script
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Check if the requested URI is for a static file
        if (preg_match('/^\/uploads\/(.*\.(jpg|jpeg|png|gif|webp|css|js|html|txt))$/', $uri)) {
            $filePath = __DIR__ . '/..' . $uri; // Adjust path as needed
            if (file_exists($filePath)) {
                header('Content-Type: ' . mime_content_type($filePath));
                readfile($filePath);
                exit();
            } else {
                ErrorController::notFound();
                exit();
            }
        }

        foreach ($this->routes as $route) {

            $uriSegments = explode('/', trim($uri, '/'));
            $routeSegments = explode('/', trim($route['uri'], '/'));

            $match = true;

            if (count($uriSegments) === count($routeSegments) && strtoupper($route['method']) === $requestMethod) {

                $params = [];

                for ($i = 0; $i < count($uriSegments); $i++) {
                    if ($routeSegments[$i] !== $uriSegments[$i] && !preg_match('/\{(.+?)\}/', $routeSegments[$i])) {
                        $match = false;
                        break;
                    }

                    if (preg_match('/\{(.+?)\}/', $routeSegments[$i], $matches)) {

                        $params[$matches[1]] = $uriSegments[$i];
                    }
                }


                if ($match) {
                    $user = null;
                    foreach ($route['middleware'] as $role) {
                        $user = (new AuthMiddleware())->handle($role);
                    }

                    $controller = 'App\\Controllers\\' . $route['controller'];
                    $controllerMethod = $route['controllerMethod'];

                    $controllerInstance = new $controller;
                    $controllerInstance->$controllerMethod($params ?: null, $user ?: null);
                    return;
                }
            }
        }

        ErrorController::notFound();
        // echo $requestMethod;

    }
}
