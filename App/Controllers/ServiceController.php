<?php

namespace App\Controllers;

use Framework\Database;

class ServiceController
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


        $totalServices = $this->db->query('SELECT COUNT(*) AS count FROM services')->fetch()->count;
        $totalPages = ceil($totalServices / $limit);


        // $services = $this->db->query('SELECT s.id AS service_id, s.category AS service_category, s.name AS service_name,
        //  s.pricing AS service_pricing, s.image_url AS service_image_url, s.duration AS service_duration, s.number_of_sessions AS service_number_of_sessions,
        //   s.short_description AS service_short_description, s.detailed_description AS service_detailed_description,
        //   si.id AS item_id, si.service_id AS item_service_id, si.feature_description AS item_feature_description
        //   FROM services s
        //   LEFT JOIN service_items si ON s.id = si.service_id
        //   ORDER BY s.id ASC, si.id ASC
        //   LIMIT :limit OFFSET :offset', [
        //     'limit' => (int)$limit,
        //     'offset' => (int)$offset
        // ])->fetchAll();

        $services = $this->db->query('
    SELECT s.id AS service_id, s.category AS service_category, s.name AS service_name, s.sub_name AS service_sub_name,
           s.pricing AS service_pricing, s.image_url AS service_image_url, s.thumbnail_url AS service_thumbnail_url, s.duration AS service_duration,
           s.number_of_sessions AS service_number_of_sessions, s.short_description AS service_short_description,
           s.detailed_description AS service_detailed_description, s.sort_order AS service_sort_order, si.id AS item_id, si.service_id AS item_service_id,
           si.feature_description AS item_feature_description
    FROM (SELECT * FROM services ORDER BY id ASC LIMIT :limit OFFSET :offset) s
    LEFT JOIN service_items si ON s.id = si.service_id
    ORDER BY s.sort_order ASC, s.id ASC, si.id ASC', [
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ])->fetchAll();


        // inspectAndDie($services);

        $formattedData = [];
        foreach ($services as $service) {
            if (!isset($formattedData[$service->service_id])) {
                $formattedData[$service->service_id] = [
                    'id' => $service->service_id,
                    'category' => $service->service_category,
                    'name' => $service->service_name,
                    'sub_name' => $service->service_sub_name,
                    'pricing' => $service->service_pricing,
                    'image_url' => $service->service_image_url,
                    'thumbnail_url' => $service->service_thumbnail_url,
                    'duration' => $service->service_duration,
                    'number_of_sessions' => $service->service_number_of_sessions,
                    'short_description' => $service->service_short_description,
                    'detailed_description' => $service->service_detailed_description,
                    'sort_order' => $service->service_sort_order,
                    'items' => []
                ];
            }

            if (!empty($service->item_id)) {
                $formattedData[$service->service_id]['items'][] = [
                    'id' => $service->item_id,
                    'service_id' => $service->item_service_id,
                    'feature_description' => $service->item_feature_description
                ];
            }
        }

        $response = [
            'data' => array_values($formattedData),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalServices,
                'items_per_page' => $limit,
            ],
        ];

        http_response_code(200);
        echo json_encode($response);
    }

    public function show($params)
    {
        $id = $params['id'] ?? null;

        $rows = $this->db->query('SELECT s.id AS service_id, s.category AS service_category, s.name AS service_name, s.sub_name AS service_sub_name,
         s.pricing AS service_pricing, s.image_url AS service_image_url, s.thumbnail_url AS service_thumbnail_url, s.duration AS service_duration, s.number_of_sessions AS service_number_of_sessions,
          s.short_description AS service_short_description, s.detailed_description AS service_detailed_description,
          si.id AS item_id, si.service_id AS item_service_id, si.feature_description AS item_feature_description
          FROM services s
          LEFT JOIN service_items si ON s.id = si.service_id
          WHERE s.id = :id
          ORDER BY si.id ASC
          ', [
            'id' => $id
        ])->fetchAll();

        if (!$rows) {
            ErrorController::notFound('Service not found');
            return;
        }



        $service = [
            'id' => $rows[0]->service_id,
            'category' => $rows[0]->service_category,
            'name' => $rows[0]->service_name,
            'sub_name' => $rows[0]->service_sub_name,
            'pricing' => $rows[0]->service_pricing,
            'image_url' => $rows[0]->service_image_url,
            'thumbnail_url' => $rows[0]->service_thumbnail_url,
            'duration' => $rows[0]->service_duration,
            'number_of_sessions' => $rows[0]->service_number_of_sessions,
            'short_description' => $rows[0]->service_short_description,
            'detailed_description' => $rows[0]->service_detailed_description,

        ];

        // foreach ($rows as $row) {
        //     if ($row->item_id) {
        //         $service['items'][] = [
        //             'id' => $row->item_id,
        //             'service_id' => $row->item_service_id,
        //             'feature_description' => $row->item_feature_description
        //         ];
        //     }
        // }

        // Loop through the results to build the items
        foreach ($rows as $row) {
            if ($row->item_id) {
                $service['items'][] = [
                    'label' => $row->item_feature_description, // Use feature_description as the label
                    'value' => $row->item_id // Use item_id as the value
                ];
            }
        }


        http_response_code(200);
        echo json_encode($service);
    }

    // public function store(){

    //     $body = json_decode(file_get_contents('php://input'), true);


    // }


    public function update($params)
    {

        $id = $params['id'] ?? null;

        // $body = json_decode(file_get_contents('php://input'), true);
        $body = $_POST;

        $allowedFields = ['category', 'name', 'sub_name', 'pricing',  'duration', 'number_of_sessions', 'short_description', 'detailed_description'];

        $allowedFieldsItems = ['feature_description'];

        $services = [];
        $serviceItems = isset($body['items']) ? json_decode($body['items'], true) : [];
        $errors = [];


        $serviceExists = $this->db->query('SELECT * FROM services where id = :id', [
            'id' => $id
        ])->fetch();

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $services[$field] = $body[$field];
            }
        }


        // TODO Update error handler

        if (empty($services) && empty($serviceItems) && !isset($_FILES['image_url'])) {
            ErrorController::badRequest(null, 'No data provided to update.');
            return;
        }

        if (empty($serviceExists)) {
            ErrorController::notFound('Service not found');
            return;
        }


        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
            $imageResult = handleImageUpload($_FILES['image_url'], '/services', $serviceExists->image_url);

            if (!empty($imageResult['errors'])) {
                $errors = array_merge($errors, $imageResult['errors']);
            } else {
                $services['image_url'] = $imageResult['path'];
            }
        }

        if (!empty($errors)) {
            http_response_code(400); // Bad Request
            echo json_encode(['message' => 'Validation errors', 'errors' => $errors]);
            return;
        }


        if (!empty($services)) {
            $setPart = [];
            foreach ($services as $key => $value) {
                $setPart[] = "$key = :$key";
            }
            $setPartString = implode(', ', $setPart);

            $this->db->query("UPDATE services SET $setPartString WHERE id = :id", array_merge($services, ['id' => $id]));
        }


        if (!empty($serviceItems)) {
            // Clear existing service items before inserting new ones
            $this->db->query("DELETE FROM service_items WHERE service_id = :id", [
                'id' => $id
            ]);

            foreach ($serviceItems as $item) {
                if (isset($item['feature_description'])) {
                    $this->db->query("INSERT INTO service_items (service_id, feature_description) VALUES (:service_id, :feature_description)", [
                        'service_id' => $id,
                        'feature_description' => $item['feature_description']
                    ]);
                }
            }
        }


        http_response_code(200);
        echo json_encode(['message' => 'Service updated successfully.']);
    }

    public function destroy($params)
    {
        $id = $params['id'] ?? '';

        $serviceExists = $this->db->query('SELECT * FROM services WHERE id = :id', [
            'id' => $id,
        ])->fetch();

        if (!$serviceExists) {
            ErrorController::notFound('Service not found');
            return;
        }

        $this->db->query('DELETE FROM service_items WHERE service_id = :id', [
            'id' => $id,
        ]);

        $this->db->query('DELETE FROM services WHERE id = :id', [
            'id' => $id,
        ]);

        http_response_code(200);
        echo json_encode([
            'message' => 'Service and all related items deleted successfully',
        ]);
    }
}
