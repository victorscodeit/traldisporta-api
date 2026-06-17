<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Jun-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

/**
 * Database configuration
 */
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_NAME', 'restapi');

/*define('DB_EXTERNAL_USERNAME', 'coffi.guy');
define('DB_EXTERNAL_PASSWORD', 'Tyorpan4');
define('DB_EXTERNAL_HOST', 'vhostsql2\TEST');
define('DB_EXTERNAL_NAME', 'trans');*/

define('DB_EXTERNAL_USERNAME', 'coffi.guy');
define('DB_EXTERNAL_PASSWORD', 'Tyorpan4');
define('DB_EXTERNAL_HOST', 'vhostsql1');
define('DB_EXTERNAL_NAME', 'trans');

//API KEY per les peticions d'usuaris que utilitzin el webservice
//define('API_KEY','3d524a53c110e4c22463b10ed32cef9d'); 

//API KEY per les peticions de funcionalitats nomes executables pel super admin
define('API_KEY_ADMIN','d8746d4f4cf1b9a1634b19990d7ab6d1'); 



?>