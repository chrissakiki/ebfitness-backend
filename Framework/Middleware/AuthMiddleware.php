<?php

namespace Framework\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Framework\Database;

class AuthMiddleware
{
    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    private function getTokenFromCookies()
    {
        return $_COOKIE['eb_auth'] ?? null;
    }

    private function fetchUser($id)
    {

        return $this->db->query('SELECT * FROM users WHERE id = :id', ['id' => $id])->fetch();
    }

    public function handle($requiredRole)
    {
        $token = $this->getTokenFromCookies();

        if (!$token) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized: Token not provided']);
            exit();
        }

        try {
            // Decode the token
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET_KEY'], 'HS256'));

            // Fetch user from the database
            $userId = $decoded->id;
            $user = $this->fetchUser($userId);

            if (!$user) {
                http_response_code(404);
                echo json_encode(['message' => 'User not found']);
                exit();
            }

            // Check user role
            if (!in_array($user->role, (array)$requiredRole)) {
                http_response_code(403);
                echo json_encode(['message' => 'Forbidden: Insufficient permissions']);
                exit();
            }


            return $user;
        } catch (ExpiredException $e) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized: Token expired']);
            exit();
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized: Invalid token']);
            exit();
        }
    }
}
