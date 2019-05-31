<?php

require __DIR__ . '/vendor/autoload.php';

$client = new \GuzzleHttp\Client([
    'base_uri' => 'http://127.0.0.1:8000',
    'defaults' => [
        'http_errors' => false
    ]
]);

$nickname = 'ObjectOrienter' . rand(0, 999);

$data = array(
    'nickname'     => $nickname,
    'avatarNumber' => 5,
    'tagLine'      => 'a test dev!'
);

// 1) POST - create a programmer resource
$response = $client->post('/api/programmers', [
    'body' => json_encode($data)
]);
$programmerUrl = $response->getHeaderLine('Location');
//echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
//echo 'Location: ' . $response->getHeaderLine('location') . PHP_EOL;
//echo 'Content type: ' . $response->getHeaderLine('content-type') . PHP_EOL;
//echo 'Content: ' . $response->getBody() . PHP_EOL;
//die;

// 2) GET a programmer resource
$response = $client->get($programmerUrl);

// 3) GET the programmer list
$response = $client->get('/api/programmers');

echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
echo 'Location: ' . $response->getHeaderLine('location') . PHP_EOL;
echo 'Content type: ' . $response->getHeaderLine('content-type') . PHP_EOL;
echo 'Content: ' . $response->getBody() . PHP_EOL;