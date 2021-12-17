<?php

require_once __DIR__ . '/vendor/autoload.php';


$client = new \Schwartzcode\Rest\Client('commd', 'Fudg349fdsl?ksdfgpo3_');
$dataArray = [
    'text'=>'Some text',
    'port'=>[15],
    'param'=>[['number'=>'+447782824684']]
];
$response = $client->request(
    'POST',
    'https://86.148.4.212:8443/api/send_sms',
    null,
    json_encode($dataArray)
);



echo '<pre>';
var_dump($response);
exit;
