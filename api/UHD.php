<?php
// Suppress errors (use with caution in production)
error_reporting(0);

// Set timezone
date_default_timezone_set('Asia/Shanghai');

// Fetch Bing API Data for today
$apiUrl = 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1';
$json_string = @file_get_contents($apiUrl); // Use @ to suppress warnings on failure

// Process Data
if ($json_string === false) {
    header("HTTP/1.1 503 Service Unavailable");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Failed to fetch data from Bing API.']);
    exit;
}

$data = json_decode($json_string);
if ($data === null || !isset($data->images[0]->urlbase)) {
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid data received from Bing API.']);
    exit;
}

// Extract image base URL and construct final URL
$imageInfo = $data->{"images"}[0];
$imgurlbase = "https://cn.bing.com" . $imageInfo->{"urlbase"};
$imgurl = $imgurlbase . "_UHD.jpg";

// Redirect to the image URL
header("Location: " . $imgurl);
exit; // Stop script execution after sending redirect header
?>