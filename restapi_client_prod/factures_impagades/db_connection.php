<?php

function connectionDb(){
    if (!defined('SQLSRV_HOST')) {
        require_once dirname(__DIR__) . '/api/include/Config.php';
    }
    $info = sqlsrv_connection_params(array(
        'SQLSRV_HOST' => SQLSRV_HOST,
        'SQLSRV_DATABASE' => SQLSRV_DATABASE,
        'SQLSRV_USERNAME' => SQLSRV_USERNAME,
        'SQLSRV_PASSWORD' => SQLSRV_PASSWORD,
    ));
    $conn = sqlsrv_connect($info['Server'], array(
        'Database' => $info['Database'],
        'UID' => $info['UID'],
        'PWD' => $info['PWD'],
    ));

    if(!$conn){
        die(print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

function closeDb($conn){
    sqlsrv_close($conn);
}
