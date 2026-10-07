<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;



class UserController
{

    protected $db;

    public function __construct()
    {
        // configuration as host port dbname username pass
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    // Generate JWT
    private function generateJWT($userId, $username)
    {
        $payload = [
            'iss' => $_ENV['APP_URL'],  // Issuer
            'iat' => time(),             // Issued at
            'id' => $userId,        // Custom claim: user ID
            'username' => $username,      // Custom claim: username
            'exp' => time() + $_ENV['COOKIE_EXPIRY_TIME'],  // Expiry time (1 hour)
        ];

        // Generate the JWT token
        return JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');
    }

    // Set JWT in cookie
    private function setJWTInCookie($token)
    {
        // Set cookie options
        setcookie(
            'eb_auth',       // Cookie name
            $token,            // Cookie value
            time() + $_ENV['COOKIE_EXPIRY_TIME'], // Expiry time
            '/',               // Path (root level)
            '',                // Domain (leave empty for default)
            true,              // Secure (use true if HTTPS)
            true,               // HttpOnly (prevents JS access)
            // [
            //     'expires' => time() + $_ENV['COOKIE_EXPIRY_TIME'],  // Expiry time
            //     'path' => '/',               // Path (root level)
            //     'domain' => '',              // Domain (leave empty for default)
            //     'secure' => false,            // Secure (use true if HTTPS)
            //     'httponly' => true,          // HttpOnly (prevents JS access)
            //     'samesite' => 'None'         // SameSite setting for cross-origin requests
            // ]
        );
    }

    /**
     * Respond with errors
     *
     * @param array $errors
     * @return void
     */
    // Error response
    private function respondWithErrors($errors)
    {
        echo json_encode(['errors' => $errors]);
        http_response_code(400);
    }

    /**
     * Login user
     *
     * @return void
     */
    public function login()
    {
        $body = json_decode(file_get_contents("php://input"), true);

        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        $errors = [];

        // Validate email
        if (!Validation::string($username, 1)) {
            $errors['username'] = 'username is required';
        }
        if (!Validation::string($password, 1)) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            return ErrorController::badRequest($errors);
        }

        // Check for username
        $params = [
            'username' => $username
        ];

        $userExists = $this->db->query('SELECT * FROM users WHERE username = :username', $params)->fetch();

        if (!$userExists) {
            $errors['username'] = 'Invalid Credentials';
            return ErrorController::badRequest($errors);
        }

        //Check if password is correct
        if (!password_verify($password, $userExists->password)) {
            $errors['username'] = 'Invalid Credentials';
            return ErrorController::badRequest($errors);
        }

        // Generate JWT
        $token = $this->generateJWT($userExists->id, $userExists->username);

        // Set JWT in cookie
        $this->setJWTInCookie($token);

        http_response_code(200);
        echo json_encode(['message' => 'User logged in successfully', 'data' => [
            'id' => $userExists->id,
            'username' => $userExists->username,
        ]]);
    }

    /**
     * Register user
     * 
     * @return void
     */

    public function register()
    {
        $body = json_decode(file_get_contents("php://input"), true);

        $username = $body['username'] ?? '';
        $email_address = $body['email_address'] ?? '';
        $password = $body['password'] ?? '';

        // inspectAndDie($body);

        $errors = [];

        if (!Validation::email($email_address)) {
            $errors['email_address'] = 'Invalid email format';
        }

        if (!Validation::string($username, 2, 50)) {
            $errors['username'] = 'Username must be between 2 and 50 characters';
        }

        if (!validation::string($password, 6, 50)) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (!empty($errors)) {
            return ErrorController::badRequest($errors);
        }

        $params = [
            'username' => $username,
            'email_address' => $email_address
        ];

        $userExists = $this->db->query('SELECT * FROM users WHERE username = :username OR email_address = :email_address', $params)->fetch();

        if ($userExists) {
            // Check which one exists and add the appropriate error message
            if ($userExists->username === $username) {
                $errors['username'] = 'Username already exists';
            }
            if ($userExists->email_address === $email_address) {
                $errors['email_address'] = 'Email already exists';
            }
            return $this->respondWithErrors($errors); // Respond with errors if username or email exists
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $params = [
            'username' => $username,
            'email_address' => $email_address,
            'password' => $hashed_password,
            'role' => 'guest'
        ];

        $this->db->query('INSERT INTO users (username, email_address, password, role) VALUES (:username, :email_address, :password, :role)', $params);

        http_response_code(201);
        echo json_encode(['message' => 'User registered successfully']);
        // $userID = $this->db->conn->lastInsertId();
    }

    public function logout()
    {
        // Delete JWT cookie
        setcookie('eb_auth', '', time() - 3600, '/', '', true, true);
        http_response_code(200);
        echo json_encode(['message' => 'User  logout successfully']);
    }

    public function checkAuth()
    {
        // Check if JWT cookie exists
        if (!isset($_COOKIE['eb_auth'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            exit;
        }

        http_response_code(200);
        echo json_encode(['message' => 'Authorized']);
    }
}
