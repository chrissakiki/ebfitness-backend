<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;

class TestimonialController
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

        // Get the total count of testimonials to calculate total pages
        $totalTestimonials = $this->db->query('SELECT COUNT(*) AS count FROM testimonials')->fetch()->count;
        $totalPages = ceil($totalTestimonials / $limit);

        // Fetch the paginated testimonials
        $testimonials = $this->db->query('SELECT * FROM testimonials LIMIT :limit OFFSET :offset', [
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ])->fetchAll();

        // Create a response structure with pagination details
        $response = [
            'data' => $testimonials,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalTestimonials,
                'items_per_page' => $limit,
            ],
        ];

        // inspectAndDie($response);

        // Send the response as JSON
        http_response_code(200);
        echo json_encode($response);
    }

    public function show($params)
    {
        // Get the testimonial ID from the URL parameter
        $id = $params['id'] ?? '';

        $testimonial = $this->db->query('SELECT * FROM testimonials WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (!$testimonial) {
            ErrorController::notFound('Testimonial not found');
            // echo json_encode(['error' => 'Testimonial not found']);
            return;
        }

        http_response_code(200);
        echo json_encode($testimonial);
    }

    public function store()
    {
        $allowedFields = ['author_name', 'text', 'rating'];
        $body = json_decode(file_get_contents("php://input"), true);

        $data = [];
        $errors = [];

        // foreach ($allowedFields as $field) {
        //     if (isset($body[$field]) ) {    
        //         if ()
        //         $data[$field] = $body[$field];
        //     } else {
        //         $errors[$field] = "$field is required.";
        //     }
        // }

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                // Check for author_name and text as non-empty strings
                if ($field === 'author_name' || $field === 'text') {
                    if (Validation::string($body[$field])) {
                        $data[$field] = $body[$field];
                    } else {
                        $errors[$field] = "$field is required.";
                    }
                }
                // Check for rating as a numeric value and within a range
                elseif ($field === 'rating') {
                    if (isset($body[$field]) && is_numeric($body[$field])) {
                        $rating = (int)$body[$field]; // Cast to integer
                        if ($rating >= 1 && $rating <= 5) {
                            $data[$field] = $rating;
                        } else {
                            $errors[$field] = "Rating must be a number between 1 and 5.";
                        }
                    } else {
                        $errors[$field] = "Rating must be a valid number.";
                    }
                }
            } else {
                $errors[$field] = "$field is required.";
            }
        }

        if (!empty($errors)) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Validation errors', 'errors' => $errors]);
            return;
        }

        // $author_name = $body['author_name'];
        // $text = $body['text'];
        // $rating = $body['rating'];

        $this->db->query('INSERT INTO testimonials (author_name, text, rating) VALUES (:author_name, :text, :rating)', $data);

        $testimonialId = $this->db->conn->lastInsertId(); // Get the last inserted ID
        $newTestimonial = $this->db->query('SELECT * FROM testimonials WHERE id = :id', [
            'id' => $testimonialId,
        ])->fetch();

        http_response_code(201);
        echo json_encode([
            'message' => 'Testimonial created successfully',
            'data' => $newTestimonial
        ]);
    }

    public function update($params)
    {
        $id = $params['id'] ?? '';
        $testimonial = $this->db->query('SELECT * FROM testimonials WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$testimonial) {
            ErrorController::notFound('Testimonial not found');
            return;
        }

        $allowedFields = ['author_name', 'text', 'rating'];


        $body = json_decode(file_get_contents("php://input"), true);
        $data = [];
        $errors = [];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                // Check for author_name and text as non-empty strings
                if ($field === 'author_name' || $field === 'text') {
                    if (Validation::string($body[$field])) {
                        $data[$field] = $body[$field];
                    }
                }
                // Check for rating as a numeric value and within a range
                elseif ($field === 'rating') {
                    if (isset($body[$field]) && is_numeric($body[$field])) {
                        $rating = (int)$body[$field]; // Cast to integer
                        if ($rating >= 1 && $rating <= 5) {
                            $data[$field] = $rating;
                        } else {
                            $errors[$field] = "Rating must be a number between 1 and 5.";
                        }
                    } else {
                        $errors[$field] = "Rating must be a valid number.";
                    }
                }
            }
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['message' => 'Validation errors', 'errors' => $errors]);
            return;
        }

        if (!empty($data)) {
            $setPart = [];
            foreach ($data as $key => $value) {
                $setPart[] = "$key = :$key";
            }
            $setPartString = implode(', ', $setPart);

            $this->db->query("UPDATE testimonials SET $setPartString WHERE id = :id", array_merge($data, ['id' => $id]));
        }

        $updatedTestimonial = $this->db->query('SELECT * FROM testimonials WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        http_response_code(200);
        echo json_encode([
            'message' => 'Testimonial updated successfully',
            'data' => $updatedTestimonial
        ]);
    }


    public function destroy($params)
    {
        $id = $params['id'] ?? '';

        $testimonial = $this->db->query('SELECT * FROM testimonials WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$testimonial) {
            ErrorController::notFound('Testimonial not found');
            return;
        }

        $this->db->query('DELETE FROM testimonials WHERE id = :id', [
            'id' => $id,
        ]);

        http_response_code(200);
        echo json_encode([
            'message' => 'Testimonial deleted successfully',
        ]);
    }
}
