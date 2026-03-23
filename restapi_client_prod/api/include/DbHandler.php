<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Sep-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

require '../class/internal/User.php';
require '../class/internal/Token.php';
require '../class/internal/Logging.php';
require '../class/internal/Authorization.php';

date_default_timezone_set("Europe/Madrid");

class DbHandler
{

    private $conn;

    function __construct()
    {
        //require_once dirname(__FILE__) . './DbConnect.php';
        require_once 'DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    public function checkApiKey($apiKey)
    {
        $sql = "SELECT 'x' 
        FROM " . Authorization::TABLE . "  
        WHERE " . Authorization::API_KEY . " = '" . $apiKey . "' 
        AND " . Authorization::ACTIVE . " = 1";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        if (count($rows) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Funció privada que permet guardar a la base de dades el token generat per un usuari concret
     */
    private function saveToken($userId, $token)
    {

        $fechaCreacion = date('Y-m-d H:i:s');
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $result = array();

        $sentence = "INSERT INTO " . Token::TABLE . " 
        (" . Token::USER_ID . "," . Token::TOKEN . "," . Token::CREATE_DATE . "," . Token::EXPIRE_DATE . ") 
        VALUES 
        (" . $userId . ",'" . $token . "','" . $fechaCreacion . "','" . $fechaExpiracion . "')";

        $this->conn->query($sentence);

        $result[Token::TOKEN] = $token;
        $result[Token::CREATE_DATE] = $fechaCreacion;
        $result[Token::EXPIRE_DATE] = $fechaExpiracion;

        return $result;
    }

    /** 
     * Funció pública que permet recuperar el token vàlid. Si no existeix o està caducat, en crea un de nou i el retorna. 
     * Però si segueix vàlid, és a dir, que no està expirat, retorna l'existent. 
     */
    public function getToken($params, $authCode = false)
    {
        $sql = "
        SELECT u." . User::ID . ", u." . User::PASSWORD . ", t." . Token::TOKEN . ", t." . Token::EXPIRE_DATE . "  
        FROM " . User::TABLE . " u LEFT JOIN " . Token::TABLE . " t on (u." . User::ID . " = t." . Token::USER_ID . ") 
        WHERE u." . User::USERNAME . " = '" . $params['username'] . "' 
        ORDER BY " . Token::CREATE_DATE . " DESC 
        LIMIT 1";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        //Si s'ha trobat l'usuari
        if (count($rows) > 0) {
            //Recuperem la contrasenya que te guardada a la db
            $passSaved = $rows[0][User::PASSWORD];

            //Comprovem si la contrasenya que ens han enviat, correspon amb la que hi ha guardada
            if (checkPassword($params['password'], $passSaved)) {
                $toCreate = false;
                $userId = $rows[0][User::ID];
                $expire = false;

                // Comprovem si ja te un token creat
                if ($rows[0][Token::TOKEN] != null && $rows[0][Token::TOKEN] != false) {
                    $expire = $rows[0][Token::EXPIRE_DATE];
                    $today = date('Y-m-d H:i:s');

                    //Si el token esta caducat
                    if ($today > $expire) {
                        //Establim que s'haura de crear
                        $toCreate = true;

                    }
                    //Si encara no esta caducat
                    else {
                        //Retornem el token existent
                        //el parametre newToken = 0 ens diu que estem agafant el token vell que ja existia
                        return array(
                            "newToken" => 0,
                            "userId" => $userId,
                            "token" => $rows[0][Token::TOKEN],
                            "expire" => $rows[0][Token::EXPIRE_DATE],
                        );
                    }
                }
                //Si encara no s'ha creat mai 
                else {
                    //Establim que s'haura de crear
                    $toCreate = true;
                }

                //Si cal crear-ne un de nou
                if ($toCreate) {
                    //Generem el nou token
                    $newToken = generarToken();

                    //El guardem a la base de dades
                    $data = $this->saveToken($userId, $newToken);

                    $url = createURLAPI();
                    $d = array(
                        "token" => $newToken,
                        "user_id" => $userId,
                        "create_date" => $data[Token::CREATE_DATE],
                        "expire_date" => $data[Token::EXPIRE_DATE]
                    );
                    $res = CallAPI("POST", $url . "/token", $authCode, $d);

                    //Si la resposta és un numero, vol dir que hi hagut algun tipus d'error
                    if (is_numeric($res)) {
                        return $res;
                    }

                    $externalToken = $res['data']['token'];

                    $this->saveExternalToken($newToken, $externalToken);

                    //el parametre newToken = 1 ens diu que hem creat un de nou
                    return array(
                        "newToken" => 1,
                        "userId" => $userId,
                        "token" => $data[Token::TOKEN],
                        "expire" => $data[Token::EXPIRE_DATE],
                    );
                }
            } else {
                //La contrasenya facilitada no es correcte
                return ERR_PASSWORD_INVALID;
            }
        }
        //No s'ha trobat aquest usuari
        return ERR_USER_NOT_FOUND;
    }

    /**
     * Funció que donat un token (el token public que utilitza una aplicació de tercers),
     * va a buscar el token corresponent per poder fer la crida a la capa 2
     */
    public function getExternalToken($token)
    {
        $sql = "
        SELECT " . Token::TOKEN_EXTERNAL . " 
        FROM " . Token::TABLE . " 
        WHERE " . Token::TOKEN . " = '" . $token . "'";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        //Si s'ha trobat el token
        if (count($rows) > 0) {
            $externalToken = $rows[0][Token::TOKEN_EXTERNAL];

            return array("token" => $externalToken);
        } else {
            //Token no existeix
            return ERR_TOKEN_NOT_EXIST;
        }
    }

    /**
     * Funció privada que permet guardar la el token extern juntament amb el token intern
     */
    private function saveExternalToken($internalToken, $externalToken)
    {
        $update_sentence = "
        UPDATE " . Token::TABLE . " 
        SET " . Token::TOKEN_EXTERNAL . " = '" . $externalToken . "' 
        WHERE " . Token::TOKEN . " = '" . $internalToken . "' ";

        $this->conn->query($update_sentence);

        return true;
    }

    /**
     * Funció publica que donat un token, comprova que existeixi, i si és el cas, comprova que no estigui expirat.
     */
    public function checkToken($token)
    {
        $sql = "SELECT * 
        FROM " . Token::TABLE . "  
        WHERE " . Token::TOKEN . " = '" . $token . "'";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        //Si s'ha trobat el token
        if (count($rows) > 0) {
            $expire = $rows[0][Token::EXPIRE_DATE];
            $today = date('Y-m-d H:i:s');

            //Si el token esta caducat
            if ($today > $expire) {
                //Token caducat
                return ERR_TOKEN_EXPIRED;
            }
            //Si el token es valid
            else {
                return true;
            }
        } else {
            //Token no existeix
            return ERR_TOKEN_NOT_EXIST;
        }
    }

    /**
     * Funció pública que ens guarda el log de la petició a la base de dades. Ens guarda qui ha fet la petició, quant i des de quina IP
     */
    public function saveLog($token = false, $page, $data = false, $authCode = false)
    {
        $userId = 'null';
        /*if ($token != false) {
            $sql = "
        SELECT " . Token::USER_ID . " 
        FROM " . Token::TABLE . "  
        WHERE " . Token::TOKEN . " = '" . $token . "'";

            $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
            $row = $stm->fetchAll();
            $userId = false;
            if (count($row) > 0) {
                $userId = $row[0][Token::USER_ID];
            }
        }*/
		if ($authCode != false) {
			$sql = "
				SELECT " . Authorization::USER_ID . " 
				FROM " . Authorization::TABLE . "  
				WHERE " . Authorization::API_KEY . " = '" . $authCode . "' AND " . Authorization::ACTIVE . " = 1";
				
			$stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
			$row = $stm->fetchAll();
			$userId = false;
			if (count($row) > 0) {
				$userId = $row[0][Token::USER_ID];
			}
		}	
		

        $request_ip = getRealIP();

        $d = 'null';
        if ($data != false) {
            $d = $data;
        }

        $sentence = "
        INSERT INTO " . Logging::TABLE . " 
        (" . Logging::USER_ID . "," . Logging::REQUEST . "," . Logging::REQUEST_IP . ", " . Logging::DATA . " ) 
        VALUES 
        (" . $userId . ",'" . $page . "','" . $request_ip . "', '" . $d . "')";
		

        $this->conn->query($sentence);

        return true;
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

    /**
     * Funció que permet crear un nou usuari a la base de dades
     */
    public function createNewUser($params, $authCode = false)
    {
        //Comprovem que aquest nom d'usuari NO existeixi
        if (!$this->checkUsername($params['username'])) {
            $password = '';
            //Comprovem que el parametre password existeixi a l'array de paràmetres
            if (isset($params['password'])) {
                //Mirem si la variable esta buida
                if (trim($params['password']) === "") {
                    //En aquest cas, generem una contrasenya aleatoria
                    $password = generatePassword();
                } else {
                    //Si conté informació, és la contrasenya que farem servir
                    $password = $params['password'];
                }
            } else {
                //En cas que no existeixi a l'array de paràmetres, crearem una contrasenya aleatoria
                $password = generatePassword();
            }


            $encryptedPass = encryptPassword($password);

            $sentence = "
            INSERT INTO " . User::TABLE . " 
            (" . User::NAME . "," . User::USERNAME . "," . User::PASSWORD . ")
            VALUES 
            ('" . $params['name'] . "','" . $params['username'] . "','" . $encryptedPass . "')";

            try {
                $stmt = $this->conn->prepare($sentence);
                $stmt->execute();
                $userId = $this->conn->lastInsertId();

                $newApiKey = generarToken();

                $sentence1 = "
                    INSERT INTO " . Authorization::TABLE . " 
                    (" . Authorization::USER_ID . "," . Authorization::API_KEY . "," . Authorization::ACTIVE . ")
                    VALUES 
                    (" . $userId . ",'" . $newApiKey . "',1)";

                $stmt1 = $this->conn->prepare($sentence1);
                $stmt1->execute();

                $url = createURLAPI();
                $d = array(
                    "user_id" => $userId,
                    "username" => $params['username'],
                    "name" => $params['name'],
                    "api_key" => $newApiKey
                );

                //Cridem a la API de la segona capa per generar aquest usuari alla
                $res = CallAPI('POST', $url . '/generateUser', $authCode, $d);

                return array(
                    "username" => $params['username'],
                    "password" => $password,
                    "apikey" => $newApiKey,
                );
            } catch (Exception $e) {
                return ERR_USER_NOT_CREATED;
            }
        } else {
            //Nom d'usuari ja existeix
            return ERR_USER_ALREADY_EXIST;
        }
    }

    /**
     * Funció que permet canviar la contrasenya d'un usuari
     */
    public function saveNewPasswordUser($params)
    {
        //Comprovem quin identificador ens ha arribat
        $uID = false;
        if (isset($params['user_id'])) {
            $uID = $params['user_id'];
        }
        $uName = false;
        if (isset($params['username'])) {
            $uName = $params['username'];
        }
        $uToken = false;
        if (isset($params['token'])) {
            $uToken = $params['token'];
        }

        $newPass = false;
        //Si no existeix el parametre new_password a l'array
        if (!isset($params['new_password'])) {
            //Generem una contrasenya aleatoria
            $newPass = generatePassword();
        }
        //En cas que si que existeixi
        else {
            //Comprovem si el camp ve buit
            if (trim($params['new_password']) === "") {
                //Generem una contrasenya aleatoria
                $newPass = generatePassword();
            }
            //Si no ve buit
            else {
                //Utilitzarem la contrasenya facilitada
                $newPass = $params['new_password'];
            }
        }

        //Encriptem la contrasenya
        $encryptedPass = encryptPassword($newPass);

        //Si els 3 camps son false, vol dir que no s'ha establert cap d'aquests camps identificatius a la petició
        if ($uID === false && $uName === false && $uToken === false) {
            //Com que no podem identificar quin usuari volem canviar-li la contrasenya, mostrem error
            return ERR_USER_NOT_REQUEST;
        }
        //Si tenim alguns dels camps identificatius de l'usuari
        else {
            //Preparem el bloc del where segons quin parametre ens hagin facilitat
            $condition = "";
            if ($uID != false) {
                $condition .= " AND u." . User::ID . " = " . $uID . " ";
            }
            if ($uName != false) {
                $condition .= " AND u." . User::USERNAME . " = '" . $uName . "' ";
            }
            if ($uToken != false) {
                $condition .= " AND t." . Token::TOKEN . " = '" . $uToken . "' ";
            }

            //Preparem el select per fer la consulta
            $sql = "SELECT u." . User::ID . " 
            FROM " . User::TABLE . " u 
            LEFT JOIN " . Token::TABLE . " t
            ON u." . User::ID . " = t." . Token::USER_ID . "  
            WHERE 1=1 " . $condition . "  
            LIMIT 1";

            $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
            $rows = $stm->fetchAll();

            //Si no hem obtingut cap resultat, vol dir que aquest usuari no existeix
            if (count($rows) == 0) {
                //Retornem codi d'error
                return ERR_TOKEN_NOT_EXIST;
            }
            //Si hem obtingut resposta
            else {
                //Recuperem l'id de l'usuari (si ens han facilitat directament per user id, no faria falta realment)
                $userID = $rows[0][User::ID];

                //Preparem la sentencia update 
                $whereClause = ' WHERE ' . User::ID . " = " . $userID;

                $sentence = "
                UPDATE " . User::TABLE . " 
                SET " . User::PASSWORD . " = '" . $encryptedPass . "' " . $whereClause;

                $this->conn->query($sentence);

                return array(
                    "newPass" => $newPass
                );
            }
        }
    }
}

?>