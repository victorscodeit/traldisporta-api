<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Jun-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

require '../class/internal/User.php';
require '../class/internal/Token.php';
require '../class/internal/Logging.php';

date_default_timezone_set("Europe/Madrid");

class DbHandler
{

    private $conn;

    function __construct()
    {
        require_once dirname(__FILE__) . './DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /**
     * Funció privada que comprova si el nom d'usuari facilitat existeix a la base de dades
     */
    private function checkUsername($username)
    {
        $sql = "
        SELECT 'x' 
        FROM " . User::TABLE . " 
        WHERE " . User::USERNAME . " = '" . $username . "'";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        if (count($rows) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function generateUser($params)
    {
        //Comprovem que aquest nom d'usuari NO existeixi
        if (!$this->checkUsername($params['username'])) {

            $sentence = "
            INSERT INTO " . User::TABLE . " 
            (" . User::NAME . "," .
                User::USERNAME . "," .
                User::EXTERNAL_USER_ID . "," .
                User::API_KEY . "," .
                User::API_KEY_ACTIVE . ")
            VALUES 
            ('" . $params['name'] . "','"
                . $params['username'] . "','"
                . $params['user_id'] . "','"
                . $params['api_key'] . "', 1)";

            try {
                $stmt = $this->conn->prepare($sentence);
                $stmt->execute();

                //Insertat correctament
                return OK_TRUE;
            } catch (Exception $e) {
                return ERR_USER_NOT_CREATED;
            }
        } else {
            //Nom d'usuari ja existeix
            return ERR_USER_ALREADY_EXIST;
        }
    }

    public function getToken($params)
    {
        //Primer mirarem si token de la petició ja existeix en el sistema
        $sql = "SELECT " . Token::INTERNAL_TOKEN . " 
        FROM " . Token::TABLE . " 
        WHERE " . Token::EXTERNAL_TOKEN . " = '" . $params['token'] . "' ";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        //Si no s'ha trobat aquest token en el sistema, vol dir que cal crear-ne un de nou
        if (count($rows) == 0) {
            $newToken = crearToken();

            $sentence = "
            INSERT INTO " . Token::TABLE . " 
            (" . Token::EXTERNAL_USER_ID . "," .
                Token::EXTERNAL_TOKEN . "," .
                Token::INTERNAL_TOKEN . "," .
                Token::CREATE_DATE . "," .
                Token::EXPIRE_DATE . ")
            VALUES 
            ('" . $params['user_id'] . "','"
                . $params['token'] . "','"
                . $newToken . "','"
                . $params['create_date'] . "', '"
                . $params['expire_date'] . "')";

            try {
                $stmt = $this->conn->prepare($sentence);
                $stmt->execute();

                return array(
                    "isNew" => true,
                    "token" => $newToken
                );

            } catch (Exception $e) {
                return ERR_TOKEN_NOT_CREATED;
            }
        } 
        //Si l'hem trobat, retornarem el token intern que te vinculat
        else {
            return array(
                "isNew" => false,
                "token" => $rows[0][Token::INTERNAL_TOKEN]
            );
        }


    }

    public function checkApiKey($apiKey)
    {
        $sql = "SELECT 'x' 
        FROM " . User::TABLE . "  
        WHERE " . User::API_KEY . " = '" . $apiKey . "' 
        AND " . User::API_KEY_ACTIVE . " = 1";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        if (count($rows) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Funció publica que donat un token, comprova que existeixi, i si és el cas, comprova que no estigui expirat.
     */
    public function checkToken($token)
    {
        $sql = "SELECT * 
        FROM " . Token::TABLE . "  
        WHERE " . Token::INTERNAL_TOKEN . " = '" . $token . "'";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        //Si s'ha trobat el token
        if (count($rows) > 0) {
            return true;
        } else {
            //Token no existeix
            return ERR_TOKEN_NOT_EXIST;
        }
    }


    /**
     * Funció pública que ens guarda el log de la petició a la base de dades. Ens guarda qui ha fet la petició, quant i des de quina IP
     */
    public function saveLog($token, $page, $data = false)
    {
        $userId = 'null';
        if ($token != false) {
            $sql = "
            SELECT " . Token::EXTERNAL_USER_ID . " 
            FROM " . Token::TABLE . "  
            WHERE " . Token::INTERNAL_TOKEN . " = '" . $token . "' OR " . Token::EXTERNAL_TOKEN . " = '" . $token . "'";

            $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
            $row = $stm->fetchAll();

            if (count($row) > 0) {
                $userId = $row[0][Token::EXTERNAL_USER_ID];
            }
        }

        $d = 'null';
        if ($data != false) {
            $d = $data;
        }

        $sentence = "
        INSERT INTO " . Logging::TABLE . " 
        (" . Logging::EXTERNAL_USER_ID . "," . Logging::REQUEST . "," . Logging::REQUEST_DATE . ", " . Logging::DATA . " ) 
        VALUES 
        (" . $userId . ",'" . $page . "','" . date('Y-m-d H:i:s') . "', '" . $d . "')";

        $this->conn->query($sentence);

        return true;
    }
}

?>