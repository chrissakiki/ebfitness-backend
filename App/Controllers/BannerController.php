<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;

class BannerController
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

        $totalBanners = $this->db->query('SELECT COUNT(*) AS count FROM banners')->fetch()->count;
        $totalPages = ceil($totalBanners / $limit);

        $banners = $this->db->query('SELECT * FROM banners LIMIT :limit OFFSET :offset', [
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ])->fetchAll();

        $response = [
            'data' => $banners,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalBanners,
                'items_per_page' => $limit,
            ],
        ];

        http_response_code(200);
        echo json_encode($response);
    }

    public function show($params)
    {
        $id = $params['id'] ?? '';

        $banner = $this->db->query('SELECT * FROM banners WHERE id = :id', [
            'id' => $id
        ])->fetch();

        if (!$banner) {
            ErrorController::notFound('Banner not found');
            return;
        }

        http_response_code(200);
        echo json_encode($banner);
    }


    public function update($params)
    {
        $id = $params['id'] ?? '';

        // Log the request method and headers
        // error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
        // error_log('Request Headers: ' . print_r(getallheaders(), true));

        // // Log $_POST and $_FILES arrays
        // error_log('POST Data: ' . print_r($_POST, true));
        // error_log('FILES Data: ' . print_r($_FILES, true));
        // $input = json_decode(file_get_contents('php://input'));

        $bannerExists = $this->db->query('SELECT * FROM banners WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$bannerExists) {
            ErrorController::notFound('Banner not found');
            return;
        }

        $allowedFields = ['title', 'sub_title'];

        $data = [];
        $errors = [];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }



        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
            $imageResult = handleImageUpload($_FILES['image_url'], '/banners', $bannerExists->image_url);

            if (!empty($imageResult['errors'])) {
                $errors = array_merge($errors, $imageResult['errors']);
            } else {
                $data['image_url'] = $imageResult['path'];
            }
        }



        if (!empty($errors)) {
            return ErrorController::badRequest($errors);
        }

        // If no valid fields are provided, return an error
        if (empty($data)) {
            return ErrorController::badRequest('No valid fields to update.');
        }



        // Prepare SQL SET clause dynamically
        $setPart = [];
        foreach ($data as $key => $value) {
            $setPart[] = "$key = :$key";
        }
        $setPartString = implode(', ', $setPart);

        // Update the banner in the database
        $this->db->query("UPDATE banners SET $setPartString WHERE id = :id", array_merge($data, ['id' => $id]));

        // Fetch the updated banner
        $updatedBanner = $this->db->query('SELECT * FROM banners WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        // Send success response
        http_response_code(200);
        echo json_encode([
            'message' => 'Banner updated successfully',
            'data' => $updatedBanner
        ]);
    }
}
