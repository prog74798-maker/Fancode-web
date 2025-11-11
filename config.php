<?php
// Vercel-specific directory setup
$directories = [
    "data" => "/tmp/data",
    "filter" => "/tmp/data/filter", 
    "playlist" => "/tmp/data/playlist"
];

foreach ($directories as $dir_path) {
    if (!is_dir($dir_path)) {
        mkdir($dir_path, 0777, true);
    }
}

$tokenFile = $directories["data"] . "/token.txt";
$jsonFile = $directories["data"] . "/data.json";

date_default_timezone_set("UTC");

// Load configuration
$url = $mac = $sn = $device_id_1 = $device_id_2 = $sig = "";
$host = "";

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    if ($data !== null) {
        $url = $data["url"] ?? "";
        $mac = $data["mac"] ?? "";
        $sn = $data["serial_number"] ?? "";
        $device_id_1 = $data["device_id_1"] ?? "";
        $device_id_2 = $data["device_id_2"] ?? "";
        $sig = $data["signature"] ?? "";
        $host = parse_url($url, PHP_URL_HOST) ?? "";
    }
}

$api = "328";

// Handshake function
function handshake() { 
    global $host;
    if (empty($host)) return ["Info_arr" => ["token" => "", "random" => "", "Status Code" => 0]];
    
    $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=&JsHttpRequest=1-xml";
    $HED = [
        'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
        'Connection: Keep-Alive',
        'Accept-Encoding: gzip',
        'X-User-Agent: Model: MAG250; Link: WiFi',
        "Referer: http://$host/stalker_portal/c/",
        "Host: $host",
        "Connection: Keep-Alive",
    ];
    
    $Info_Data = Info($Xurl, $HED);
    $Info_Status = $Info_Data["Info_arr"]["http_code"] ?? 0;
    $Info_Data = $Info_Data["Info_arr"]["data"] ?? '';
    
    if (empty($Info_Data)) {
        return ["Info_arr" => ["token" => "", "random" => "", "Status Code" => $Info_Status]];
    }
    
    $Info_Data_Json = json_decode($Info_Data, true);
    $token = $Info_Data_Json["js"]["token"] ?? "";
    $random = $Info_Data_Json["js"]["random"] ?? "";
    
    return [
        "Info_arr" => [
            "token" => $token,
            "random" => $random,
            "Status Code" => $Info_Status
        ]
    ];
}

// Generate Token function
function generate_token() {
    global $tokenFile, $host, $mac;
    if (empty($host)) return "";
    
    $Info_Decode = handshake();
    $Bearer_token = $Info_Decode["Info_arr"]["token"] ?? "";
    
    if (!empty($Bearer_token)) {
        $Bearer_token = re_generate_token($Bearer_token);
        $Bearer_token = $Bearer_token["Info_arr"]["token"] ?? "";
        
        if (!empty($Bearer_token)) {
            get_profile($Bearer_token);
            file_put_contents($tokenFile, $Bearer_token);
        }
    }
    
    return $Bearer_token;
}

// Re Generate Token function
function re_generate_token($Bearer_token) {
    global $host;
    if (empty($host)) return ["Info_arr" => ["token" => "", "random" => ""]];
    
    $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=$Bearer_token&JsHttpRequest=1-xml";
    $HED = [
        'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
        'Connection: Keep-Alive',
        'Accept-Encoding: gzip',
        'X-User-Agent: Model: MAG250; Link: WiFi',
        "Referer: http://$host/stalker_portal/c/",
        "Host: $host",
        "Connection: Keep-Alive",
    ];
    
    $Info_Data = Info($Xurl, $HED);
    $Info_Data = $Info_Data["Info_arr"]["data"] ?? '';
    
    if (empty($Info_Data)) {
        return ["Info_arr" => ["token" => "", "random" => ""]];
    }
    
    $Info_Data_Json = json_decode($Info_Data, true);
    
    return [
        "Info_arr" => [
            "token" => $Info_Data_Json["js"]["token"] ?? "",
            "random" => $Info_Data_Json["js"]["random"] ?? "",
        ]
    ];
}

// Get Profile function
function get_profile($Bearer_token) {
    global $host, $mac, $sn, $device_id_1, $device_id_2, $sig, $api;
    if (empty($host) || empty($Bearer_token)) return;
    
    $timestamp = time();
    $Info_Decode = handshake();
    $Info_Decode_Random = $Info_Decode["Info_arr"]["random"] ?? "";
    
    $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=get_profile&hd=1&ver=ImageDescription%3A+0.2.18-r14-pub-250%3B+ImageDate%3A+Fri+Jan+15+15%3A20%3A44+EET+2016%3B+PORTAL+version%3A+5.1.0%3B+API+Version%3A+JS+API+version%3A+328%3B+STB+API+version%3A+134%3B+Player+Engine+version%3A+0x566&num_banks=2&sn=$sn&stb_type=MAG250&image_version=218&video_out=hdmi&device_id=$device_id_1&device_id2=$device_id_2&signature=$sig&auth_second_step=1&hw_version=1.7-BD-00&not_valid_token=0&client_type=STB&hw_version_2=08e10744513ba2b4847402b6718c0eae&timestamp=$timestamp&api_signature=$api&metrics=%7B%22mac%22%3A%22$mac%22%2C%22sn%22%3A%22$sn%22%2C%22model%22%3A%22MAG250%22%2C%22type%22%3A%22STB%22%2C%22uid%22%3A%22%22%2C%22random%22%3A%22$Info_Decode_Random%22%7D&JsHttpRequest=1-xml";
    $HED = [
        'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
        'Connection: Keep-Alive',
        'Accept-Encoding: gzip',
        'X-User-Agent: Model: MAG250; Link: WiFi',
        "Referer: http://$host/stalker_portal/c/",
        "Authorization: Bearer " . $Bearer_token,
        "Host: $host",
        "Connection: Keep-Alive",
    ];
    
    Info($Xurl, $HED);
}

// INFO function
function Info($Xurl, $HED) {
    global $mac;
    
    $cURL_Info = curl_init();
    curl_setopt_array($cURL_Info, [
        CURLOPT_URL => $Xurl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_COOKIE => "mac=$mac; stb_lang=en; timezone=GMT",
        CURLOPT_HTTPHEADER => $HED,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $Info_Data = curl_exec($cURL_Info);
    $http_code = curl_getinfo($cURL_Info, CURLINFO_HTTP_CODE);
    curl_close($cURL_Info);
    
    return [
        "Info_arr" => [
            "data" => $Info_Data,
            "http_code" => $http_code,
        ]
    ];
}

// Get Groups function
function group_title($all = false) {
    global $host;
    global $directories;

    $dir_path = $directories["filter"];
    $filter_file = "$dir_path/$host.json";

    if (file_exists($filter_file)) {
        $json_data = json_decode(file_get_contents($filter_file), true);
        if (!empty($json_data)) {
            unset($json_data["*"]);
            
            if ($all) {
                return array_column($json_data, 'title', 'id');
            }
            
            return array_column(array_filter($json_data, function ($item) {
                return $item['filter'] === true;
            }), 'title', 'id');
        }
    }

    $group_title_url = "http://$host/stalker_portal/server/load.php?type=itv&action=get_genres&JsHttpRequest=1-xml";
    $headers = [
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
        "Authorization: Bearer " . generate_token(),
        "X-User-Agent: Model: MAG250; Link: WiFi",
        "Referer: http://$host/stalker_portal/c/",
        "Accept: */*",
        "Host: $host",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip",
    ];

    $response = Info($group_title_url, $headers);
    if (empty($response["Info_arr"]["data"])) {
        return [];
    }

    $json_api_data = json_decode($response["Info_arr"]["data"], true);
    if (!isset($json_api_data["js"]) || !is_array($json_api_data["js"])) {
        return [];
    }

    $filtered_data = [];
    foreach ($json_api_data["js"] as $genre) {
        if ($genre['id'] === "*") {
            continue;
        }
        $filtered_data[$genre['id']] = [
            'id' => $genre['id'],
            'title' => $genre['title'],
            'filter' => true,
        ];
    }

    file_put_contents($filter_file, json_encode($filtered_data));

    return array_column($filtered_data, 'title', 'id');
}
?>
