<?php

namespace App\Controllers;

use Framework\Database;

class AchievementController
{

    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    public function index()
    {

        $type = isset($_GET['type']) ? $_GET['type'] : 'logos';
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $totalAchievements = $this->db->query('SELECT COUNT(*) AS count FROM achievements WHERE type = :type', [
            'type' => $type,
        ])->fetch()->count;
        $totalPages = ceil($totalAchievements / $limit);

        $achievements = $this->db->query('SELECT * FROM achievements WHERE type = :type
        LIMIT :limit
        OFFSET :offset', [
            'type' => $type,
            'limit' => (int)$limit,
            'offset' => (int)$offset,
        ])->fetchAll();

        $response = [
            'data' => $achievements,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalAchievements,
                'items_per_page' => $limit,
            ],
        ];

        http_response_code(200);
        echo json_encode($response);
    }

    public function show($params)
    {
        $id = $params['id'] ?? '';

        $achievement = $this->db->query('SELECT * FROM achievements WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$achievement) {
            ErrorController::notFound('Achievement not found');
            return;
        }

        http_response_code(200);
        echo json_encode($achievement);
    }

    public function store()
    {
        // $body = json_decode(file_get_contents("php://input"), true);
        $body = $_POST;
        $allowedFields = ['name', 'type'];

        $data = [];
        $errors = [];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                if ($field === 'type' && ($body[$field] !== 'logos' && $body[$field] !== 'certificates')) {
                    $errors[$field] = "Invalid type. Must be either logos or certificates.";
                    continue;
                }
                $data[$field] = $body[$field];
            } else {
                $errors[$field] = "$field is required.";
            }
        }

        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
            $imageResult = handleImageUpload($_FILES['image_url'], '/achievements');

            if (!empty($imageResult['errors'])) {
                $errors = array_merge($errors, $imageResult['errors']);
            } else {
                $data['image_url'] = $imageResult['path'];
            }
        }



        if (!empty($errors)) {
            return ErrorController::badRequest($errors, 'Validation errors');
        }


        $this->db->query('INSERT INTO achievements (name, type, image_url) VALUES (:name, :type, :image_url)', $data);

        $achievementId = $this->db->conn->lastInsertId(); // Get the last inserted ID
        $newAchievement = $this->db->query('SELECT * FROM achievements WHERE id = :id', [
            'id' => $achievementId,
        ])->fetch();

        http_response_code(201); // Created
        echo json_encode([
            'message' => 'Achievement created successfully',
            'data' => $newAchievement
        ]);
    }
    public function update($params)
    {
        $id = $params['id'] ?? '';
        $body = $_POST;
        $allowedFields = ['name',  'image_url'];

        if (empty($id)) return;

        $achievementExists = $this->db->query('SELECT * FROM achievements WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (!$achievementExists) {
            ErrorController::notFound('Achievement not found');
            return;
        }

        $data = [];
        $errors = [];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $data[$field] = $body[$field];
            }
        }

        // Handle image upload and deletion of the old image
        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
            $imageResult = handleImageUpload($_FILES['image_url'], '/achievements', $achievementExists->image_url);

            if (!empty($imageResult['errors'])) {
                $errors = array_merge($errors, $imageResult['errors']);
            } else {
                $data['image_url'] = $imageResult['path'];
            }
        }


        if (!empty($errors) || empty($data)) {
            return ErrorController::badRequest($errors, 'Validation errors');
            return;
        }

        $setPart = [];
        foreach ($data as $key => $value) {
            $setPart[] = "$key = :$key";
        }
        $setPartString = implode(', ', $setPart);

        $this->db->query("UPDATE achievements SET $setPartString WHERE id = :id", array_merge($data, ['id' => $id]));
        $updatedAchievement = $this->db->query('SELECT * FROM achievements WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        http_response_code(200);
        echo json_encode([
            'message' => 'Achievement updated successfully',
            'data' => $updatedAchievement
        ]);
    }

    public function destroy($params)
    {
        $id = $params['id'] ?? '';

        $achievementExists = $this->db->query('SELECT * FROM achievements WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$achievementExists) {
            ErrorController::notFound('Achievement not found');
            return;
        }

        $this->db->query('DELETE FROM achievements WHERE id = :id', [
            'id' => $id,
        ]);

        http_response_code(200);
        echo json_encode([
            'message' => 'Achievement deleted successfully',
        ]);
    }
}
