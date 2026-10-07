<?php

namespace App\Controllers;

use App\Controllers\MilestoneController;
use App\Controllers\BannerController;

class HomepageController
{
    public function index($params, $user)
    {
        // Start output buffering
        ob_start();

        // Call MilestoneController and capture output
        $milestonesController = new MilestoneController();
        $milestonesController->index($params, $user);
        $milestonesOutput = ob_get_clean(); // Get and clear the buffer

        // Decode the JSON output
        $milestones = json_decode($milestonesOutput, true);

        // Start output buffering again for the next controller
        ob_start();

        // Call BannerController and capture output
        $bannersController = new BannerController();
        $bannersController->index($params, $user);
        $bannersOutput = ob_get_clean(); // Get and clear the buffer

        // Decode the JSON output
        $banners = json_decode($bannersOutput, true);

        // Assemble the combined response
        $data = [
            'milestones' => $milestones,
            'banners' => $banners,
        ];

        // Set the response code and output the combined JSON response
        http_response_code(200);
        echo json_encode($data);
    }
}
