<?php

namespace App\Controllers;


class ErrorController

{


    /**
     * 404 not found error
     *
     * @param string $message
     * @return void
     */
    public static function notFound($message = 'Resource not found')
    {
        http_response_code(404);
        echo $message;
    }

    /**
     * 400 bad request error
     * 
     * @param string $message
     * @return void
     */

    public static function badRequest($errors = null, $message = 'Invalid request.')
    {
        http_response_code(400);

        $response = [
            "errors" => [
                "message" => $message
            ]
        ];

        if ($errors) {
            $response['errors']['fields'] = $errors;
        }

        echo json_encode($response);
    }



    /**
     * 403 unauthorized error
     *
     * @param string $message
     * @return void
     */
    public static function unauthorized($message = 'You are not allowed to access this')
    {
        http_response_code(403);
        echo $message;
    }
}
