<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;

class SectionController
{

    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $type = isset($_GET['type']) ? $_GET['type'] : null;

        $query = 'SELECT COUNT(*) AS count FROM sections';
        $params = [];

        if (!empty($type)) {
            $query .= ' WHERE type = :type';
            $params['type'] = $type;
        }

        // Count total sections for pagination
        $totalSections = $this->db->query($query, $params)->fetch()->count;
        $totalPages = ceil($totalSections / $limit);

        // Fetch sections with their items
        $query = '
            SELECT 
                s.id AS section_id, s.type, s.title AS section_title, s.image_url AS section_image_url, s.thumbnail_url AS section_thumbnail_url,
                si.id AS item_id, si.title AS item_title, si.image_url AS item_image_url, si.description AS item_description
            FROM (SELECT * FROM sections ORDER BY id ASC LIMIT :limit OFFSET :offset) s
            LEFT JOIN section_items si ON s.id = si.section_id';

        if (!empty($type)) {
            $query .= ' WHERE s.type = :type';
        }

        $query .= ' ORDER BY s.id ASC, si.id ASC';

        $params['limit'] = (int)$limit;
        $params['offset'] = (int)$offset;

        $sections = $this->db->query($query, $params)->fetchAll();

        // Format the response
        $formattedSections = [];
        foreach ($sections as $section) {
            if (!isset($formattedSections[$section->section_id])) {
                $formattedSections[$section->section_id] = [
                    'id' => $section->section_id,
                    'type' => $section->type,
                    'title' => $section->section_title,
                    'image_url' => $section->section_image_url,
                    'thumbnail_url' => $section->section_thumbnail_url,
                    'items' => []
                ];
            }

            // Only add item if it exists (some sections may have no items)
            if (!empty($section->item_id)) {
                $formattedSections[$section->section_id]['items'][] = [
                    'id' => $section->item_id,
                    'title' => $section->item_title,
                    'image_url' => $section->item_image_url,
                    'description' => $section->item_description
                ];
            }
        }

        // Prepare the response with pagination
        $response = [
            'data' => array_values($formattedSections), // Reset keys to 0, 1, 2...
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalSections,
                'items_per_page' => $limit,
            ],
        ];

        http_response_code(200);
        echo json_encode($response);
    }



    public function show($params)
    {
        $id = $params['id'] ?? '';

        // Fetch the section and its items
        $rows = $this->db->query(
            'SELECT 
                s.id AS section_id, 
                s.type, 
                s.title AS section_title, 
                s.image_url AS section_image_url, 
                s.thumbnail_url AS section_thumbnail_url,
                si.id AS item_id, 
                si.title AS item_title, 
                si.description AS item_description, 
                si.image_url AS item_image_url
             FROM sections s 
             LEFT JOIN section_items si ON s.id = si.section_id
             WHERE s.id = :id
             ORDER BY si.id ASC',
            [
                'id' => $id
            ]
        )->fetchAll();

        if (!$rows) {
            // Handle case where section is not found
            ErrorController::notFound('Section not found');
            return;
        }

        // Organize the data into a structured format
        $section = [
            'id' => $rows[0]->section_id, // All rows will have the same section data
            'type' => $rows[0]->type,
            'title' => $rows[0]->section_title,
            'image_url' => $rows[0]->section_image_url,
            'thumbnail_url' => $rows[0]->section_thumbnail_url,
            'items' => []
        ];

        // Loop through the rows and add each item
        foreach ($rows as $row) {
            if ($row->item_id) {
                $section['items'][] = [
                    'id' => $row->item_id,
                    'title' => $row->item_title,
                    'description' => $row->item_description,
                    'image_url' => $row->item_image_url,
                ];
            }
        }

        // Return the section with its items
        http_response_code(200);
        echo json_encode($section);
    }


    public function showByType($params)
    {
        $type = $params['type'] ?? '';

        // Fetch the single section and its items based on type
        $rows = $this->db->query(
            'SELECT 
                s.id AS section_id, 
                s.type, 
                s.title AS section_title, 
                s.image_url AS section_image_url, 
                s.thumbnail_url AS section_thumbnail_url,
                si.id AS item_id, 
                si.title AS item_title, 
                si.description AS item_description, 
                si.image_url AS item_image_url
             FROM sections s 
             LEFT JOIN section_items si ON s.id = si.section_id
             WHERE s.type = :type
             ORDER BY si.id ASC',
            [
                'type' => $type
            ]
        )->fetchAll();

        if (!$rows) {
            // Handle case where section is not found
            ErrorController::notFound('Section not found for this type');
            return;
        }

        // Organize the data into a single structured section
        $section = [
            'id' => $rows[0]->section_id,
            'type' => $rows[0]->type,
            'title' => $rows[0]->section_title,
            'image_url' => $rows[0]->section_image_url,
            'thumbnail_url' => $rows[0]->section_thumbnail_url,
            'items' => []
        ];

        // Loop through the rows to add each item
        foreach ($rows as $row) {
            if ($row->item_id) {
                $section['items'][] = [
                    'id' => $row->item_id,
                    'title' => $row->item_title,
                    'description' => $row->item_description,
                    'image_url' => $row->item_image_url,
                ];
            }
        }

        http_response_code(200);
        echo json_encode($section);
    }



    public function create()
    {
        // Read the JSON body from the request
        // $body = json_decode(file_get_contents("php://input"), true);
        $body = $_POST;
        $errors = [];

        // Validate required fields for sections
        $sectionData = [
            'type' => $body['type'] ?? null,
            'title' => $body['title'] ?? null,
            'image_url' => $body['image_url'] ?? null, // Optional
        ];

        // Check if required fields are present
        if (empty($sectionData['type']) || empty($sectionData['title'])) {
            ErrorController::badRequest(null, 'Type and title are required for the section.');
            return;
        }

        // Validate section_items
        if (!isset($body['items']) || !is_array($body['items']) || count($body['items']) === 0) {
            ErrorController::badRequest(null, 'At least one section item is required.');
            return;
        }

        // Temporary storage for section items
        $sectionItemsData = [];
        foreach ($body['items'] as $item) {
            $itemData = [
                'title' => $item['title'] ?? null,
                // 'image_url' => $item['image_url'] ?? null, // Optional
                'description' => $item['description'] ?? null,
            ];

            // Check if required fields are present for section items
            if (empty($itemData['title']) || empty($itemData['description'])) {
                ErrorController::badRequest(null, 'Title and description are required for each section item.');
                return;
            }

            // Store valid section item data for insertion later
            $sectionItemsData[] = $itemData;
        }


        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
            $imageResult = handleImageUpload($_FILES['image_url'], '/sections');

            if (!empty($imageResult['errors'])) {
                $errors = array_merge($errors, $imageResult['errors']);
            } else {
                $sectionData['image_url'] = $imageResult['path'];
            }
        }

        if (!empty($errors)) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Validation errors', 'errors' => $errors]);
            return;
        }




        // Insert the section into the database
        $this->db->query("INSERT INTO sections (type, title, image_url) VALUES (:type, :title, :image_url)", $sectionData);
        $sectionId = $this->db->conn->lastInsertId(); // Get the last inserted section ID

        // Insert section items into the database
        foreach ($sectionItemsData as $itemData) {
            // Include the section ID in the data to insert
            $itemData['section_id'] = $sectionId;

            // Insert section item into the database
            $this->db->query("INSERT INTO section_items (section_id, title, image_url, description) VALUES (:section_id, :title, :image_url, :description)", $itemData);
        }

        http_response_code(201); // Created
        echo json_encode(['message' => 'Section and section items created successfully.']);
    }


    public function update($params)
    {
        // $body = json_decode(file_get_contents("php://input"), true);
        $body = $_POST;

        $id = $params['id'] ?? '';

        $allowedFields = ['title', 'image_url', 'description'];

        $allowedFieldsItems = ['id', 'title', 'image_url', 'description'];

        $data = []; // For section fields
        $errors = [];
        $sectionItems = $body['items'] ?? []; // For section items

        // Check for allowed fields for sections
        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $data[$field] = $body[$field];
            }
        }

        if (empty($data) && empty($sectionItems) && !isset($_FILES['image_url'])) {
            ErrorController::badRequest(null, 'No data provided to update.');
            return;
        }

        // Check if the section exists
        $sectionExists = $this->db->query('SELECT * FROM sections WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (empty($sectionExists)) {
            ErrorController::notFound('Section not found');
            return;
        }

        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
            $imageResult = handleImageUpload($_FILES['image_url'], '/sections', $sectionExists->image_url);

            if (!empty($imageResult['errors'])) {
                $errors = array_merge($errors, $imageResult['errors']);
            } else {
                $data['image_url'] = $imageResult['path'];
            }
        }


        if (!empty($errors)) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Validation errors', 'errors' => $errors]);
            return;
        }



        // Update the section
        if (!empty($data)) {
            $setPart = [];
            foreach ($data as $key => $value) {
                $setPart[] = "$key = :$key";
            }
            $setPartString = implode(', ', $setPart);

            $this->db->query("UPDATE sections SET $setPartString WHERE id = :id", array_merge($data, ['id' => $id]));
        }


        if (!empty($sectionItems) || isset($_FILES['items'])) {


            $this->db->query("DELETE FROM section_items WHERE section_id = :id", ['id' => $id]);


            foreach ($sectionItems as $index => $item) {
                $itemId = $item['id'] ?? null;

                // Only allow updating certain fields
                $itemData = [];
                foreach ($allowedFieldsItems as $field) {
                    if (isset($item[$field])) {
                        $itemData[$field] = $item[$field];
                    }
                }


                // // Imge item upload


                if (isset($_FILES['items']['name'][$index]['image_url']) && $_FILES['items']['error'][$index]['image_url'] === 0) {

                    $fileName = $_FILES['items']['name'][$index]['image_url'];
                    $fileSize = $_FILES['items']['size'][$index]['image_url'];
                    $tmpName = $_FILES['items']['tmp_name'][$index]['image_url'];
                    // inspect($fileName);

                    // Call the helper function to handle the image upload
                    $imageResult = handleNestedImageUpload($fileName, $fileSize, $tmpName, '/sections');

                    if (!empty($imageResult['errors'])) {
                        // Handle the errors (log them, return a response, etc.)
                        error_log("Image upload error for item at index $index: " . implode(', ', $imageResult['errors']));
                        continue; // Skip this item if there's an error
                    } else {
                        // If the upload is successful, add the image path to itemData or similar
                        $itemData['image_url'] = $imageResult['path'];
                    }

                    // Continue with inserting item data into the database...
                }



                if (!empty($itemData)) {
                    $this->db->query('INSERT INTO section_items (section_id, title, description, image_url) VALUES (:section_id, :title, :description, :image_url)', [
                        'section_id' => $id,
                        'title' => $itemData['title'] ?? null,
                        'description' => $itemData['description'] ?? null,
                        'image_url' => $itemData['image_url'] ?? null
                    ]);

                    // error_log("Inserted item for section ID: $id, Title: " . ($itemData['title'] ?? 'null'));
                } else {
                    // error_log("No valid data to insert for index $index.");
                }
            }
        }

        // Fetch the updated section with its items
        $updatedSection = $this->db->query('
            SELECT s.id AS section_id, type, s.title AS section_title, s.image_url AS section_image_url, 
                   si.id AS item_id, si.title AS item_title, si.description AS item_description, si.image_url AS item_image_url
            FROM sections s
            LEFT JOIN section_items si ON s.id = si.section_id
            WHERE s.id = :id
            ORDER BY si.id ASC', [
            'id' => $id
        ])->fetchAll();

        $formattedSections = [];
        foreach ($updatedSection as $section) {
            if (!isset($formattedSections[$section->section_id])) {
                $formattedSections[$section->section_id] = [
                    'id' => $section->section_id,
                    'type' => $section->type,
                    'title' => $section->section_title,
                    'image_url' => $section->section_image_url,
                    'items' => []
                ];
            }

            if (!empty($section->item_id)) {
                $formattedSections[$section->section_id]['items'][] = [
                    'id' => $section->item_id,
                    'title' => $section->item_title,
                    'description' => $section->item_description,
                    'image_url' => $section->item_image_url
                ];
            }
        }

        // Send the updated section as response
        http_response_code(200);
        echo json_encode([
            'message' => 'Sections and items updated successfully',
            'data' => $formattedSections
        ]);
    }
}
