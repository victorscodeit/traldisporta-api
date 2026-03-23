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
 * Ens permet mostrar un json de resposta
 * @param int $status_code Http response code
 * @param mixed $response Json response
 */
function echoResponse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    if (isset($response['data'])) {
        $c = hash('sha256', json_encode($response['data']));
        $app->checksum($c);
    }

    echo json_encode($response);
}

/**
 * Ens permet verificar si s'han proporcionat, dins del body, els camps obligatoris
 */
function verifyRequiredParams($required_fields)
{
    $error = false;
    $error_fields = "";

    $app = \Slim\Slim::getInstance();
    $request_params = json_decode($app->request()->getBody(), true);

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response = responseRequiredFieldsNotFound(substr($error_fields, 0, -2));
        echoResponse(HTTP_BAD_REQUEST, $response);

        $app->stop();
    }
}

/**
 * Permet comprovar si el token es vàlid
 */
function validateToken()
{
    $app = \Slim\Slim::getInstance();
    $params = json_decode($app->request->getBody(), true);

    //Comprovem que existi aixi parametre token
    if (isset($params["token"]) || strlen(trim($params["token"])) >= 0) {
        $db = new DbHandler();

        //Comprovem a la base de dades aquest token
        $res = $db->checkToken($params["token"]);

        //Si el resultat que tenim no es de tipus boolean, vol dir que ens ha tornat un error
        //Si es boolean, nomes pot ser que ens hagi tornat un true i aixo vol dir que esta correcte
        if (!is_bool($res)) {
            //Segons el resultat que haguem obtingut mostrem una resposta
            switch ($res) {
                case -1:
                    $response = responseTokenExpired();
                    echoResponse(HTTP_BAD_REQUEST, $response);

                    $app->stop();
                    break;
                case -2:
                    $response = responseTokenNotExist();
                    echoResponse(HTTP_BAD_REQUEST, $response);

                    $app->stop();
                    break;
            }
        }
        //En cas que el res sigui 0, vol dir que el token es valid
    } else {
        $response = responseTokenNotFoundRequest();
        echoResponse(HTTP_BAD_REQUEST, $response);

        $app->stop();
    }
}

/**
 * Aquesta funció ens permet recuperar la IP del client que envia la petició
 */
function getRealIP()
{
    if (isset($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_FORWARDED_FOR"];
    } elseif (isset($_SERVER["HTTP_FORWARDED"])) {
        return $_SERVER["HTTP_FORWARDED"];
    } else {
        return $_SERVER["REMOTE_ADDR"];
    }
}

/**
 * Aquesta funció ens permetrà gestionar la creacio del logging a la base de dades
 */
function logging($request, $token = false, $data = false, $authCode = false)
{
    $app = \Slim\Slim::getInstance();
    $t = false;
    if ($token == false) {
        $params = json_decode($app->request->getBody(), true);
        if (isset($params["token"])) {
            if (strlen(trim($params["token"])) >= 0) {
                $t = $params["token"];
            }
        }
    } else {
        $t = $token;
    }

    if (is_array($data)) {
        $data = json_encode($data);
    }

    $db = new DbHandler();
    $db->saveLog($t, $request, $data, $authCode);
}


/**
 * Funció que ens permet recuperar el camp checksum de la petició i comprovar que no hagi estat manipulada
 */
function checkChecksumFromRequest()
{
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    //Comprovem si l'atribut checksum esta en el header de la petició
    if (isset($headers['Checksum'])) {
        //Agafem les dades del body
        $received_data = $app->request->getBody();
        //Agafem el checksum del header
        $received_checksum = $headers['Checksum'];

        //Cridem a la funció que ens comprovarà el checksum
        if (checkChecksum($received_checksum, $received_data)) {
            //Correcte
            return true;
        } else {
            //Si no son iguals, vol dir que s'han manipulat i no és correcte
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            $app->stop();
        }
    } else {
        //Si no apareix el checksum en el header de la peticio, llençarem l'error
        $response = responseChecksumNotFound();
        echoResponse(HTTP_BAD_REQUEST, $response);

        $app->stop();
    }
}

function getAuthorizationFromRequest()
{
    //Agafem els paràmetres del header de la petició 
    /*$headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        return $headers['Authorization'];
    } else {
        return ERR_AUTHCODE_NOT_FOUND;
    }*/

    $headers = getallheaders();
    $found = false;
    $token = "";

    foreach ($headers as $clave => $valor) {
        if ($clave === 'Authorization' || $clave === 'Apikey'){
            $found = true;
            //Agafem l'api key que esta en el header de la petició
            $token = $valor;
            break;
        }
    }

    if ($found){
        return $token;
    }
    else{
        return ERR_AUTHCODE_NOT_FOUND;
    }


}

/**
 * Funció que ens permet revisar el header per comprovar si hi ha l'api key d'autorització
 */
function authenticate(\Slim\Route $route)
{
    $response = array();
    $app = \Slim\Slim::getInstance();
    $authCode = getAuthorizationFromRequest();

    if (!is_numeric($authCode)) {
        $db = new DbHandler();

        $res = $db->checkApiKey($authCode);

        //Si no s'ha trobat aquesta api key en el sistema
        if ($res == false) {
            //Es una api key invalida. Per tant mostrarem missatge d'error
            $response = responseAPIKeyNotValid();
            echoResponse(HTTP_CREATED, $response);

            //Parem l'aplicacio
            $app->stop();
        }
    } else {
        // api key is missing in header
        $response = responseAPIKeyNotFoundRequest();
        echoResponse(HTTP_BAD_REQUEST, $response);

        $app->stop();
    }
}


/**
 * Funció que ens permet revisar el header per comprovar si hi ha l'api key d'autorització del super admin
 */
function authenticate2(\Slim\Route $route)
{
    // Getting request headers
    
    $response = array();
    $app = \Slim\Slim::getInstance();

    $authCode = getAuthorizationFromRequest();

    //Comprovem si existeix el bloc Authorization en el header de la petició
    if (!is_numeric($authCode)) {
        //Comprovem que la api key facilitada és la mateixa que tenim declarada en l'arxiu fisic Config.php
        if (!($authCode == API_KEY_ADMIN)) {
            //En cas que no sigui la mateixa, mostrarem resposta d'error
            $response = responseAPIKeyNotValid();
            echoResponse(HTTP_BAD_REQUEST, $response);

            $app->stop();
        }
    } else {
        //Si no hi ha l'api key en el header de la petició, mostrem resposta d'error
        $response = responseAPIKeyNotFoundRequest();
        echoResponse(HTTP_BAD_REQUEST, $response);

        $app->stop();
    }
}

/**
 * Aquesta funció ens permet generar un token
 */
function generarToken()
{
    $length = 32; // Longitut del token
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; //Caracters disponibles
    $token = '';
    $max = mb_strlen($keyspace, '8bit') - 1;

    for ($i = 0; $i < $length; ++$i) {
        $token .= $keyspace[random_int(0, $max)];
    }

    return $token;
}

/**
 * Aquesta funció permet generar una contrasenya segura
 */
function generatePassword($length = 12)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+';
    $password = '';

    // Obtenir la longitud de la cadena de caràcters
    $characterLength = strlen($characters);

    for ($i = 0; $i < $length; $i++) {
        // Generar un índex aleatori dins del rang de caràcters
        $randomIndex = mt_rand(0, $characterLength - 1);

        // Concatenar el caràcter aleatori a la contrasenya
        $password .= $characters[$randomIndex];
    }

    return $password;
}

/**
 * Aquesta funció ens permet generar un hash
 */
function encryptPassword($password)
{
    //Encriptem de manera segura la contrasenya que ens arriba
    $pass = password_hash($password, PASSWORD_BCRYPT);

    return $pass;
}

/**
 * Aquesta funció ens comprova si la contrasenya facilitada correspon amb el hash que tenim
 */
function checkPassword($password, $hashPassword)
{

    if (password_verify($password, $hashPassword)) {
        return true;
    } else {
        return false;
    }
}

function createURLAPI()
{
    $url = API_PROTOCOL . '://' . API_HOST;

    if (API_PORT != '') {
        $url .= ':' . API_PORT;
    }

    $url .= '/' . API_PATH;

    return $url;
}

/**
 * A partir d'un array ens calcula la cadena checksum
 */
function generateChecksum($data)
{
    $data = json_encode($data, true);
    return hash('sha256', $data);
}

/**
 * Funció que ens permet comprovar si la cadena checksum és vàlida
 */
function checkChecksum($checksum, $data)
{
    //Si ens arriba un array
    if (is_array($data)) {
        //La convertim en un json
        $data = json_encode($data);
    }

    //Convertim les dades en un hash
    $calculated_checksum = hash('sha256', $data);

    //print($calculated_checksum);

    //Si les dues cadenes son iguals
    if ($checksum === $calculated_checksum) {
        //És correcte
        return true;
    }
    //Si no són iguals
    else {
        //És invalid
        return false;
    }
}

/**
 * Funció que donat un header pla (text) ens permet generar un dict 
 */
function http_parse_headers_custom($header_plain)
{
    // Separar los encabezados en líneas
    $headerLines = explode("\r\n", $header_plain);

    // Inicializar un array para almacenar los encabezados
    $responseHeadersArray = [];

    // Recorrer las líneas de encabezados
    foreach ($headerLines as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $responseHeadersArray[$key] = $value;
        }
    }

    return $responseHeadersArray;
}

/**
 * Funció que ens permet fer una crida a un RESTAPI extern
 */
function CallAPI($method, $url, $authorizationCode = false, $data = false)
{
    //Inicialitzem el cURL
    $ch = curl_init($url);

    //Establem que el contingut serà json
    $header = array('Content-Type: application/json');

    //En el cas que sigui una petició de tipus POST
    if ($method == 'POST') {
        //En cas que haguem proporcionat dades que s'han d'enviar
        if ($data != false) {
            curl_setopt($ch, CURLOPT_POST, true); // Indiquem que estem fent una petició POST on enviarem paràmetres
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, true)); // Afegim els paràmetres

            //Afegim en el header el parametre checksum
            //array_push($header, 'Checksum: ' . generateChecksum($data));
        }
    }

    //Afegim l'API KEY, si l'hem explicitat
    if ($authorizationCode != false) {
        array_push($header, 'Authorization: ' . $authorizationCode);
		//array_push($header, 'ApiKey: ' . $authorizationCode);
    }

    //Configurem les diferents opcions del cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Retornem la resposta en comptes d'imprimir-la
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_HEADER, 1); // Ens permet obtenir el header de resposta
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    //Executem la petició
    $response = curl_exec($ch);

    //Comprovem si hi hagut algun tipus d'error a la petició
    if (curl_errno($ch)) {
        return ERR_CURL_REQUEST_FAILED;
    }

    //Obtenim el header de la resposta
    $responseHeaders = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
    //Com que ens arriba com un text pla, ho convertim a un array
    $responseHeadersArray = http_parse_headers_custom($responseHeaders);

    //Obtenim el body de la resposta
    $responseBody = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

    //Convertim el body, que ens ve com un text pla, a un array
    $res = json_decode($responseBody, true);

    //Comprovem el checksum de la resposta
    /*if (!isset($responseHeadersArray['Checksum'])) {
        return ERR_CHECKSUM_NOT_FOUND;
    } else {
        if (!checkChecksum($responseHeadersArray['Checksum'], $res['data'])) {
            return ERR_CHECKSUM_INVALID;
        }
    }*/

    //Tanquem la connexió cURL
    curl_close($ch);

    //Si la resposta l'hem pogut convertir en un array, vol dir que tot ha anat correctament
    if (is_array($res)) {
        //Retornem la resposta
        return $res;
    }
    //Si no em pogut, vol dir que hi hagut algun tipus d'error que no ens ha permès fer-ho
    else {
        //echo 'No se pudo decodificar la respuesta JSON.';
        return ERR_JSON_DECODE_FAILED;
    }
}

?>