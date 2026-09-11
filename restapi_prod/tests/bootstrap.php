<?php

$base = getenv('API_BASE_URL');
if ($base === false || $base === '') {
    putenv('API_BASE_URL=http://127.0.0.1/restapi/v1');
}

$apiKey = getenv('API_KEY');
if ($apiKey === false || $apiKey === '') {
    putenv('API_KEY=VBGxoAvLEeTDgevPZMmCgrg4g0iT1gmI');
}
