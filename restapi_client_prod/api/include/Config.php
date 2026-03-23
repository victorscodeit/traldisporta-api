<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Sep-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

/**
 * Database configuration
 */
define('DB_USERNAME', 'api_traldisporta');
define('DB_PASSWORD', '684e4gfH?');
define('DB_HOST', 'localhost');
define('DB_NAME', 'api');

/**
 * External API configuration
 */
define("API_PROTOCOL", 'http');
define('API_HOST','91.187.69.73');
define('API_PORT','8080');
define('API_PATH','restapi_prod/v1');

//API KEY per les peticions de funcionalitats només executables pel super admin
define('API_KEY_ADMIN','d8746d4f4cf1b9a1634b19990d7ab6d1'); 



?>