<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postData = file_get_contents("php://input");
    if ($postData) {
        $logFilePath = "log.json";
        $currentData = file_exists($logFilePath) ? json_decode(file_get_contents($logFilePath), true) : array();
        $newData = json_decode($postData, true);
        $currentData[] = $newData;

        if (file_put_contents($logFilePath, json_encode($currentData))) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "error" => "Failed to write to file"));
        }
    } else {
        echo json_encode(array("success" => false, "error" => "No data received"));
    }
} else {
    echo json_encode(array("success" => false, "error" => "Invalid request method"));
}
