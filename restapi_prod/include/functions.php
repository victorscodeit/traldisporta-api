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
 * Funció que ens permet recuperar el camp checksum de la petició i comprovar que no hagi estat manipulada
 */
function checkChecksum()
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
        //Convertim les dades en un hash
        $calculated_checksum = hash('sha256', $received_data);

        //print($calculated_checksum);

        //Comprovem els 2 hash per veure si son iguals
        if ($received_checksum === $calculated_checksum) {
            //Si son iguals, tot correcte
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

/**
 * Aquesta funció ens permetrà gestionar la creacio del logging a la base de dades
 */
function logging($request, $token = false, $data = false)
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
    $db->saveLog($t, $request, $data);
}

function getAuthorizationFromRequest()
{
    //Agafem els paràmetres del header de la petició 
    $headers = getallheaders();
    $found = false;
    $token = "";

    foreach ($headers as $clave => $valor) {
        if (strtolower($clave) === 'authorization' || $clave === 'Apikey'){
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
function crearToken()
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
 * Ens converteix l'string d'una data al string d'un datetime
 * */
function parseDate($dateOrigin)
{
    if ($dateOrigin != false) {
        return $dateOrigin . "T00:00:00.000Z";
    } else {
        return $dateOrigin;
    }
}

?>