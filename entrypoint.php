<?php

// Enable error reporting for debugging
///
//  CHECKKKKK
//
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Path to the config file
define("CONFIG_FILE", $_SERVER['DOCUMENT_ROOT'] . "/../private/landing/appdata/server/config/config.json");

// Load the configuration file for the Server app
function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        throw new Exception("Configuration file not found");
    }
    $configContent = file_get_contents(CONFIG_FILE);
    return json_decode($configContent, true); // Decoding as associative array
}

// Function to send a request to the Backend app
function requestFirebaseConfigsFromBackend() {
    // Get backend entrypoint URL from the Server's config
    $backendEntryPoint = getBackendEntryPointURL();
    $url = "https://$backendEntryPoint?action=getFirebaseConfigs";

    // Initialize cURL
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Execute the request
    $response = curl_exec($curl);

    // Check for errors in the request
    if (curl_errno($curl)) {
        echo 'Error:' . curl_error($curl);
        return null;
    }

    curl_close($curl);

    // Return decoded JSON response from Backend
    return json_decode($response, true);
}

// Function to get the Backend entrypoint URL from the config
function getBackendEntryPointURL() {
    $config = loadConfig();
    return $config['communications']['backend']['entrypoint']['url'];
}

// Handle the request and send response back to Frontend
function processRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Only GET requests are allowed']);
        exit;
    }

    $action = $_GET['action'] ?? null;
    if ($action === 'getFirebaseConfigs') {
        $backendResponse = requestFirebaseConfigsFromBackend();
        if ($backendResponse && isset($backendResponse['firebaseConfigs'])) {
            http_response_code(200); // OK
            echo json_encode([
                'status' => 'success',
                'firebaseConfigs' => $backendResponse['firebaseConfigs']
            ]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Failed to fetch Firebase configs from Backend']);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid action']);
    }
}

processRequest();

?>
