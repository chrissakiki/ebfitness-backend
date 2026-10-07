<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;

class MilestoneController
{

    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    public function index($params, $user)
    {
        // inspectAndDie($user);
        // Get the current page from the query parameter, default to page 1 if not provided
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        // Calculate the offset for the SQL query
        $offset = ($page - 1) * $limit;

        // Get the total count of milestones to calculate total pages
        $totalMilestones = $this->db->query('SELECT COUNT(*) AS count FROM milestones')->fetch()->count;
        $totalPages = ceil($totalMilestones / $limit);

        // Fetch the paginated milestones
        $milestones = $this->db->query('SELECT * FROM milestones LIMIT :limit OFFSET :offset', [
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ])->fetchAll();

        // Create a response structure with pagination details
        $response = [
            'data' => $milestones,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalMilestones,
                'items_per_page' => $limit,
            ],
        ];

        // Send the response as JSON
        http_response_code(200);
        echo json_encode($response);
    }

    public function show($params)
    {
        $id = $params['id'] ?? '';

        $milestone = $this->db->query('SELECT * FROM milestones WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (!$milestone) {
            ErrorController::notFound('Milestone not found');
            return;
        }

        http_response_code(200);
        echo json_encode($milestone);
    }

    public function store()
    {
        $allowedFields = ['title', 'quantity'];
        $body = json_decode(file_get_contents("php://input"), true);

        $data = [];
        $errors = [];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $data[$field] = $body[$field];
            } else {
                $errors[$field] = "$field is required.";
            }
        }


        if (!empty($errors)) {
            return ErrorController::badRequest($errors, 'Validation Errors');
        }

        $this->db->query('INSERT INTO milestones (title, quantity) VALUES (:title, :quantity)', $data);

        $milestoneId = $this->db->conn->lastInsertId(); // Get the last inserted ID
        $newMilestone = $this->db->query('SELECT * FROM milestones WHERE id = :id', [
            'id' => $milestoneId,
        ])->fetch();

        http_response_code(201);
        echo json_encode([
            'message' => 'Milestone created successfully',
            'data' => $newMilestone
        ]);
    }

    public function update($params)
    {
        $id = $params['id'] ?? '';
        $milestone = $this->db->query('SELECT * FROM milestones WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$milestone) {
            ErrorController::notFound('Milestone not found');
            return;
        }

        $allowedFields = ['title', 'quantity'];

        $body = json_decode(file_get_contents("php://input"), true);
        $data = [];
        $errors = [];


        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $data[$field] = $body[$field];
            } else {
                $errors[$field] = "$field is required.";
            }
        }

        if (!empty($errors)) {
            http_response_code(400);
            return ErrorController::badRequest($errors, 'Validation Errors');
            return;
        }

        if (!empty($data)) {
            $setPart = [];
            foreach ($data as $key => $value) {
                $setPart[] = "$key = :$key";
            }
            $setPartString = implode(', ', $setPart);

            $this->db->query("UPDATE milestones SET $setPartString WHERE id = :id", array_merge($data, ['id' => $id]));
        }

        $updatedMilestone = $this->db->query('SELECT * FROM milestones WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        http_response_code(200);
        echo json_encode([
            'message' => 'Milestone updated successfully',
            'data' => $updatedMilestone
        ]);
    }


    public function destroy($params)
    {
        $id = $params['id'] ?? '';

        $milestone = $this->db->query('SELECT * FROM milestones WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$milestone) {
            ErrorController::notFound('Milestone not found');
            return;
        }

        $this->db->query('DELETE FROM milestones WHERE id = :id', [
            'id' => $id,
        ]);

        http_response_code(200);
        echo json_encode([
            'message' => 'Milestone deleted successfully',
        ]);
    }
}
