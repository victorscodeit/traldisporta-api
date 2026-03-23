<?php
function connectionDbInternal()
{

    $servername = "localhost";
    $database = "porta";
    $username = "root";
    $password = "";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $database);
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}


function closeDbInternal($conn)
{
    mysqli_close($conn);
}

?>