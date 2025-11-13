<?php
$site_id = 2; // Use the site_id for which you created the token
$api_url = "http://localhost/backoffice_cms/api/articles?site_id=" . $site_id;
$api_token = "e4b33c5b2b2b6c6b8d3b0c8d1e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f"; // Use the exact token you inserted into the database

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $api_token
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo "API Response:\n";
    echo $response;
}

curl_close($ch);
