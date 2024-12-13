<?php

// check for username
if ($argc !== 2) {
    echo "Usage: php src/main.php <username>\n";
    exit(1);
}

$username = $argv[1];
$apiUrl = "https://api.github.com/users/$username/events";

// Initialize a cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub-Activity-PHP'); // Required by GitHub API

// EAfter API request
$response = curl_exec($ch);
// print_r($response);
$error = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close the cURL session
curl_close($ch);

if ($error) {
    echo "Error fetching data: $error\n";
    exit(1);
}

if ($statusCode !== 200) {
    echo "Error: Received HTTP status code $statusCode.\n";
    if ($statusCode === 404) {
        echo "The username '$username' was not found.\n";
    } else if ($statusCode === 403) {
        echo "Rate limit exceeded. Try again later.\n";
    }
    exit(1);
}

// Decode the JSON response
// $events = json_decode($response);
$events = json_decode($response, true);

if (!is_array($events) || empty($events)) {
    echo "No recent activity found for user '$username'.\n";
    exit(0);
}

// Display the recent activity
foreach ($events as $event) {
    $type = $event['type'] ?? 'Unknown event';
    $repo = $event['repo']['name'] ?? 'Unknown repository';

    switch ($type) {
        case 'PushEvent':
            $commitCount = count($event['payload']['commits'] ?? []);
            echo "Pushed $commitCount commits to $repo\n";
            break;

        case 'IssuesEvent':
            $action = $event['payload']['action'] ?? 'performed';
            echo ucfirst($action) . " an issue in $repo\n";
            break;

        case 'WatchEvent':
            echo "Starred $repo\n";
            break;

        case 'ForkEvent':
            echo "Forked $repo\n";
            break;

        case 'CreateEvent':
            $refType = $event['payload']['ref_type'] ?? 'repository';
            echo "Created a new $refType in $repo\n";
            break;

        default:
            echo "Performed $type in $repo\n";
            break;
    }
}
?>
