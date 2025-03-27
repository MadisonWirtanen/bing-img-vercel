<?php
// Suppress errors (use with caution in production, might hide issues)
error_reporting(0); 
// Set timezone
date_default_timezone_set('Asia/Shanghai');

// --- Determine Day Offset ---
$gettime = 0; // Default to today
if (isset($_GET['rand']) && $_GET['rand'] === 'true') {
    // Random day between yesterday (-1) and 7 days ago (Bing API limit)
    $gettime = rand(0, 7); 
} elseif (isset($_GET['day'])) {
    // Use specified day offset, ensuring it's an integer
    $gettime = (int)$_GET['day'];
    // Basic validation for Bing's typical range (optional but good practice)
    if ($gettime < -1 || $gettime > 7) { 
        $gettime = 0; // Fallback to today if out of typical range
    }
}

// --- Determine Format ---
$getformat = 'jpg'; // Default format
if (!empty($_GET['format'])) {
    // Basic sanitization (allow only alphanumeric)
    $getformat = preg_replace("/[^a-zA-Z0-9]/", "", $_GET['format']);
    if (empty($getformat)) {
        $getformat = 'jpg'; // Fallback if format is invalid after sanitization
    }
}

// --- Determine Size ---
$imgsize = '1920x1080'; // Default size
if (!empty($_GET['size'])) {
     // Basic sanitization (allow alphanumeric, x, _)
    $imgsize = preg_replace("/[^a-zA-Z0-9x_]/", "", $_GET['size']);
     if (empty($imgsize)) {
        $imgsize = '1920x1080'; // Fallback if size is invalid after sanitization
    }
}

// --- Fetch Bing API Data ---
$apiUrl = 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=' . $gettime . '&n=1';
$json_string = @file_get_contents($apiUrl); // Use @ to suppress warnings on failure

// --- Process Data ---
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

// Extract information
$imageInfo = $data->{"images"}[0];
$imgurlbase = "https://cn.bing.com" . $imageInfo->{"urlbase"};
$imgurl = $imgurlbase . "_" . $imgsize . "." . $getformat;
$imgtime = $imageInfo->{"startdate"}; // YYYYMMDD format
$imgtitle = $imageInfo->{"copyright"};
$imglink = $imageInfo->{"copyrightlink"};

// --- Output ---
if (isset($_GET['info']) && $_GET['info'] === 'true') {
    // Return JSON information
    header('Content-Type: application/json; charset=utf-8');
    $output = [
        'title' => $imgtitle,
        'url' => $imgurl,
        'link' => $imglink,
        'time' => $imgtime,
        'query' => [ // Include query params for context
            'day_offset' => $gettime,
            'size' => $imgsize,
            'format' => $getformat
        ]
    ];
    // Use JSON_UNESCAPED_UNICODE for proper character encoding, JSON_PRETTY_PRINT for readability
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    // Redirect to the image URL
    header("Location: " . $imgurl);
    exit; // Important: Stop script execution after sending redirect header
}

?>
