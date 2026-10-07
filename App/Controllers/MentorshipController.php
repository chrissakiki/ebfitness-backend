<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;


class MentorshipController
{

    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    public function index($params, $user)
    {

        $page = isset($_GET['page']) ?  (int)$_GET('page') : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET('limit') : 10;

        $offset = ($page - 1) * $limit;

        $totalMentorships = $this->db->query('SELECT COUNT(*) AS count FROM mentorship_applications')->fetch()->count;
        $totalPages = ceil($totalMentorships / $limit);

        $query = $this->db->query('SELECT * FROM mentorship_applications LIMIT :limit OFFSET :offset', [
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ])->fetchAll();

        $response  = [
            'data' => $query,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalMentorships,
                'items_per_page' => $limit,
            ]
        ];


        http_response_code(200);
        echo json_encode($response);
        // inspectAndDie($query);
    }


    public function show($params, $user)
    {
        $id = $params['id'] ?? '';

        $query = $this->db->query('SELECT * FROM mentorship_applications WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (!$query) {
            ErrorController::notFound('Mentorship Application not found');
            return;
        }

        http_response_code(200);
        echo json_encode($query);
    }

    public function store($params, $user)
    {
        $body = $_POST;
        $errors = [];

        $fields = [
            'full_name' => 'Full Name',
            'email_address' => 'Email Address',
            'phone_number' => 'Phone Number',
            'location' => 'Location',
            'job_title' => 'Job Title',
            'company' => 'Company',
            'experience' => 'Experience',
            'certifications' => 'Certifications',
            'how_heard' => 'How Heard',
            'reason' => 'Reason',
            'skills' => 'Skills',
            'coaching' => 'Coaching',
            'agreement' => 'Agreement',
        ];

        foreach ($fields as $field => $label) {
            if (empty($body[$field])) {
                $errors[$field] = "$label is required.";
            }
        }

        // Validate email
        if (!Validation::email($body['email_address'])) {
            $errors['email_address'] = 'Invalid email format';
        } else {
            // Check if email already exists in the database
            $existingEmail = $this->db->query('SELECT 1 FROM mentorship_applications WHERE email_address = :email', [
                'email' => $body['email_address']
            ])->fetch();

            if ($existingEmail) {
                $errors['email_address'] = 'Email address is already in use.';
            }
        }
        // Validate phone
        if (!preg_match('/^\+?[0-9]{8,15}$/', $body['phone_number'])) {
            $errors['phone_number'] = "Invalid phone number.";
        }

        // Validate experience (integer, non-negative)
        if (!is_numeric($body['experience']) || (int)$body['experience'] < 0) {
            $errors['experience'] = "Experience must be a non-negative integer.";
        } else {
            $body['experience'] = (int)$body['experience'];
        }

        // Validate agreement (must be explicitly true)
        if (!isset($body['agreement']) || !filter_var($body['agreement'], FILTER_VALIDATE_BOOLEAN)) {
            $errors['agreement'] = "You must agree to the terms.";
        } else {
            $body['agreement'] = 1;
        }


        // Validate CV file
        if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
            $errors['cv'] = "CV file is required.";
        } else {
            $file = $_FILES['cv'];
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors['cv'] = "CV must be a PDF or Word document.";
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                $errors['cv'] = "CV file size must not exceed 5MB.";
            }
        }

        if (!empty($errors)) {
            ErrorController::badRequest($errors, 'No data provided to update.');
            return;
        }

        // Store CV
        $cvPathMove = PROJECT_ROOT . '/public/uploads/mentorships/' . basename($_FILES['cv']['name']) . '_' . uniqid() . '.' . pathinfo(basename($_FILES['cv']['name']), PATHINFO_EXTENSION);
        if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cvPathMove)) {
            ErrorController::badRequest([
                'cv' => 'Failed to upload CV.'
            ]);
            return;
        }


        $body['cv'] = '/uploads/mentorships/' .  basename($_FILES['cv']['name']) . '_' . uniqid() . '.' . pathinfo(basename($_FILES['cv']['name']), PATHINFO_EXTENSION);

        $columns = array_keys($fields);
        $columns[] = 'cv';
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $query = sprintf(
            'INSERT INTO mentorship_applications (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($columns as $column) {
            $params[$column] = $body[$column];
        }

        // Execute query
        $this->db->query($query, $params);

        http_response_code(201);
        echo json_encode(['message' => 'Mentorship application submitted successfully.']);
    }

    public function destroy($params, $user)
    {
        $id = $params['id'] ?? '';

        $record = $this->db->query('SELECT * FROM mentorship_applications WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (!$record) {
            ErrorController::notFound('Mentorship Application not found');
            return;
        }

        $cvPath = $record->cv;
        if (file_exists($cvPath)) {
            if (!unlink($cvPath)) {
                ErrorController::badRequest(['cv' => 'Failed to delete CV file.']);
                return;
            }
        }

        $this->db->query('DELETE FROM mentorship_applications WHERE id = :id', [
            'id' => $id
        ]);

        http_response_code(200);
        echo json_encode(['message' => 'Mentorship application deleted successfully.']);
    }
}
