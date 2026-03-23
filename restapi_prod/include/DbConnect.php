<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Jun-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/
class DbConnect
{

    private $conn;

    function __construct()
    {
    }
    /**
     * Establishing database connection
     * @return database connection handler
     */
    function connect()
    {
        include_once dirname(__FILE__) . './Config.php';

        try {
            $this->conn = new PDO(
                'mysql:host=' .
                DB_HOST . ';dbname=' .
                DB_NAME . ';charset=utf8',
                DB_USERNAME,
                DB_PASSWORD
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // returing connection resource
            return $this->conn;

        } catch (PDOException $ex) {

            // if the environment is development, show error details, otherwise show generic message
            if ((defined('ENVIRONMENT')) && (ENVIRONMENT == 'development')) {
                echo 'An error occured connecting to the database! Details: ' . $ex->getMessage();
            } else {
                echo 'An error occured connecting to the database!';
            }
            exit;
        }

    }

}
?>