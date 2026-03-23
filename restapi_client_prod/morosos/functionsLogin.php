<?php

require_once("db_connectionInternal.php");


function encryptPassword($password)
{
    //Encriptem de manera segura la contrasenya que ens arriba
    $pass = hash('sha256', $password);

    return $pass;
}

function login($username, $password){
    $conn = connectionDbInternal();
    $encryPass = encryptPassword($password);

    $select_user = "
    SELECT 'x' 
    FROM user 
    WHERE username = '".$username."' AND password = '".$encryPass."'";

    $consulta_nm=mysqli_query($conn,$select_user);
    
    $cantidad_nm=mysqli_num_rows($consulta_nm);

    if ($cantidad_nm > 0){
        return true;
    }
    else{
        return false;
    }
}

?>