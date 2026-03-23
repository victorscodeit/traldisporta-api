<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://91.187.69.73:8080/restapi_prod/v1/generateExpeditionPdfs',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{"expeditionCode": "652082181"}',
  CURLOPT_HTTPHEADER => array(
    'Authorization: '.$_REQUEST['key'].'',
    'expeditionCode: 652082181',
    'Content-Type: application/json'
  ),
		  CURLOPT_VERBOSE => true,
		  CURLOPT_FAILONERROR => true,
		  CURLOPT_HEADER => false,
		  CURLOPT_SSL_VERIFYPEER => false,
		  CURLOPT_SSL_VERIFYHOST => false,
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;