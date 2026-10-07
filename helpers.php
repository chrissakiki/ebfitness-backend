<?php

/**
 * Inspect a values(s)
 * @param mixed $value
 * @return void
 */

function inspect($value)
{
    echo "<pre>";
    var_dump($value);
    echo "</pre>";
}

/**
 * Inspect a values(s) and die
 * @param mixed $value
 * @return void
 */

function inspectAndDie($value)
{
    echo "<pre>";
    die(var_dump($value));
    echo "</pre>";
}

/**
 * Get the base path
 * @param string $path
 * @return string
 */

function basePath($path = '')
{
    return __DIR__ . "/" . $path;
}

/**
 * Sanitize Data
 * 
 * @param string $dirty
 * @return string 
 */

function sanitize($dirty)
{
    return filter_var(trim($dirty), FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Handle Image Upload
 *
 * @param file $file
 * @param string $folderPath
 * @param string $oldImagePath
 * @return void
 */
function handleImageUpload($file, $folderPath, $oldImagePath = null)
{
    $errors = [];

    if (!isset($file) || $file['error'] !== 0) {
        $errors['image_url'] = 'Image upload error.';
    } else {
        // Validate file type
        $fileType = mime_content_type($file['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp']; // Add other allowed types as needed

        if (!in_array($fileType, $allowedTypes)) {
            $errors['image_url'] = 'Invalid file type. Only JPEG, PNG, and WEBP are allowed.';
        }

        // Validate file size (max 1MB)
        $maxFileSize = 1 * 1024 * 1024; // 1MB
        if ($file['size'] > $maxFileSize) {
            $errors['image_url'] = 'File size exceeds 1MB.';
        }

        // If no errors, move the uploaded file
        if (empty($errors)) {
            $imageFileName = basename($file['name']);
            $fileExtension = pathinfo($imageFileName, PATHINFO_EXTENSION);


            // Create a unique filename by appending a unique ID
            $uniqueFileName = pathinfo($imageFileName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $fileExtension;

            // Change the destination to the public folder
            $imageDestination = PROJECT_ROOT . '/public/uploads' . "$folderPath/" . $uniqueFileName;

            // Ensure the folder exists
            if (!file_exists(PROJECT_ROOT . '/public/uploads' . $folderPath)) {
                mkdir(PROJECT_ROOT . '/public/uploads' . $folderPath, 0777, true);
            }

            // Delete old image if present
            if ($oldImagePath && file_exists(PROJECT_ROOT . '/public/' . $oldImagePath)) {
                unlink(PROJECT_ROOT . '/public/' . $oldImagePath);
            }

            if (!move_uploaded_file($file['tmp_name'], $imageDestination)) {
                $errors['image_url'] = 'Failed to move uploaded file.';
            } else {
                return ['path' => '/uploads' . "$folderPath/" . $uniqueFileName, 'errors' => $errors];
            }
        }
    }

    return ['path' => null, 'errors' => $errors];
}



function handleNestedImageUpload($fileName, $fileSize, $tmpName, $folderPath)
{
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    // Validate file type
    $fileType = mime_content_type($tmpName);
    if (!in_array($fileType, $allowedTypes)) {
        $errors['image_url'] = 'Invalid file type. Only JPEG, PNG, and WEBP are allowed.';
    }

    // Validate file size (max 1MB)
    $maxFileSize = 1 * 1024 * 1024; // 1MB
    if ($fileSize > $maxFileSize) {
        $errors['image_url'] = 'File size exceeds 1MB.';
    }

    // If no errors, move the uploaded file
    if (empty($errors)) {
        $imageFileName = basename($fileName);
        $fileExtension = pathinfo($imageFileName, PATHINFO_EXTENSION);

        // Create a unique filename by appending a unique ID
        $uniqueFileName = pathinfo($imageFileName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $fileExtension;

        // Change the destination to the public folder
        $imageDestination = PROJECT_ROOT . '/public/uploads' . "$folderPath/" . $uniqueFileName;

        // Ensure the folder exists
        if (!file_exists(PROJECT_ROOT . '/public/uploads' . $folderPath)) {
            mkdir(PROJECT_ROOT . '/public/uploads' . $folderPath, 0777, true);
        }

        // Move the uploaded file
        if (!move_uploaded_file($tmpName, $imageDestination)) {
            $errors['image_url'] = 'Failed to move uploaded file.';
        } else {
            return ['path' => '/uploads' . "$folderPath/" . $uniqueFileName, 'errors' => $errors];
        }
    }

    return ['path' => null, 'errors' => $errors];
}
