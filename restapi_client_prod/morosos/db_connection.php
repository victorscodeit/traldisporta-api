<?php

function connectionDb(){
    $connectionInfo = array("Database"=>"trans","UID"=>"coffi.guy","PWD"=>"Tyorpan4");
    //TEST
    $conn = sqlsrv_connect("vhostsql2\TEST", $connectionInfo);
    //PRODUCCIO
    //$conn = sqlsrv_connect("vhostsql2", $connectionInfo);
    
    if(!$conn){
        die(print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

function closeDb($conn){
    sqlsrv_close($conn);
}

?>