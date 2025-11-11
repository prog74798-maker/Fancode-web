<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("HTTP/1.1 403 Forbidden");
    die("Error: Access denied. Please log in first.");
}

include "config.php";

// Check if portal is configured
if (empty($host) || empty($mac)) {
    header("HTTP/1.1 500 Internal Server Error");
    die("Error: Portal not configured. Please set up your portal first.");
}

// Validate and sanitize channel ID
if (empty($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Error: Channel ID is missing.");
}

$channelId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($channelId === false || $channelId <= 0) {
    header("HTTP/1.1 400 Bad Request");
    die("Error: Invalid channel ID.");
}

// Get or generate bearer token
$Bearer_token = (file_exists($tokenFile) && filesize($tokenFile) > 0) ? 
    trim(file_get_contents($tokenFile)) : generate_token();

// Validate token was generated
if (empty($Bearer_token)) {
    header("HTTP/1.1 500 Internal Server Error");
    die("Error: Unable to generate authentication token.");
}

$config = [
    'stalkerUrl' => "http://$host/stalker_portal/",
    'macAddress' => $mac,
    'authorizationToken' => "Bearer $Bearer_token",
];

function fetchStreamUrl($config, $channelId) {
    $headers = getHeaders($config);
    $streamUrlEndpoint = "{$config['stalkerUrl']}server/load.php?type=itv&action=create_link&cmd=ffrt%20http://localhost/ch/{$channelId}&JsHttpRequest=1-xml";
    
    $data = executeCurl($streamUrlEndpoint, $headers);
    
    // If first attempt fails, regenerate token and try again
    if (!isset($data['js']['cmd'])) {
        $new_token = generate_token();
        if (!empty($new_token)) {
            global $tokenFile;
            file_put_contents($tokenFile, $new_token);
            $config['authorizationToken'] = "Bearer " . $new_token;
            $headers = getHeaders($config);
            $data = executeCurl($streamUrlEndpoint, $headers);
        }
    }
    
    if (!isset($data['js']['cmd'])) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Failed to retrieve stream URL for channel ID: {$channelId}. Please check your portal configuration.");
    }
    
    return $data['js']['cmd'];
}

function getHeaders($config) {
    $parsedUrl = parse_url($config['stalkerUrl']);
    $hostHeader = $parsedUrl['host'] ?? '';
    
    if (empty($hostHeader)) {
        throw new Exception("Invalid stalker URL");
    }
    
    return [
        "Cookie: timezone=GMT; stb_lang=en; mac={$config['macAddress']}",
        "Referer: {$config['stalkerUrl']}",
        "Accept: */*",
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
        "X-User-Agent: Model: MAG250; Link: WiFi",
        "Authorization: {$config['authorizationToken']}",
        "Host: $hostHeader",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip"
    ];
}

function executeCurl($url, $headers) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: " . $error);
    }
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: {$httpCode}");
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }
    
    return $data;
}

try {
    $streamUrl = fetchStreamUrl($config, $channelId);
    
    // Validate the stream URL before redirecting
    if (filter_var($streamUrl, FILTER_VALIDATE_URL) === false) {
        throw new Exception("Invalid stream URL generated");
    }
    
    // Redirect to the actual stream URL
    header("Location: " . $streamUrl);
    exit;
    
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
