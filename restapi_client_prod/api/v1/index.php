<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Sep-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');

//INCLUDES
include_once '../include/Config.php';
require '../include/functions.php';
require_once '../include/DbHandler.php';
require_once '../i18n/en_en.php';
require_once '../include/responseMessage.php';
require_once '../include/errorCode.php';
require_once '../include/responseCode.php';
require '../libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

//SERVEIS INTERNS PUBLICS - START
/**
 * Servei que ens permet obtenir un token de comunicació
 */
/*$app->post('/token', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "username",
        "password"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    checkChecksumFromRequest();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('token', false, $params);

    //Recuperem les dades del header de la petició
    $headers = apache_request_headers();

    $db = new DbHandler();
    //Demanem al sistema un token
    $result = $db->getToken($params, $headers['Authorization']);

    //Si hem obtingut dades
    if (is_array($result)) {
        $response = array();
        //Si és 0, voldrà dir que recuperem un token existent encara vàlid
        if ($result["newToken"] == 0) {
            $response = responseTokenValid();
            $response["data"] = $result;
        } else {
            //Si és 1, vol dir que el sistema n'ha creat un de nou
            $response = responseTokenCreated();
            $response["data"] = $result;
        }
        //Retornem la resposta 
        echoResponse(HTTP_OK, $response);
    }
    //Si no hem rebut un array, vol dir que hem rebut un codi d'error
    else {
        switch ($result) {
            case ERR_PASSWORD_INVALID:
                $response = responseUserPassInvalid();
                echoResponse(HTTP_BAD_REQUEST, $response);
                break;
            case ERR_USER_NOT_FOUND:
                $response = responseUserNotFound();
                echoResponse(HTTP_BAD_REQUEST, $response);
                break;
            case ERR_CURL_REQUEST_FAILED:
                $response = responseCURLRequestFailed();
                echoResponse(HTTP_BAD_REQUEST, $response);
                break;
            case ERR_JSON_DECODE_FAILED:
                $response = responseJSONDecodeFailed();
                echoResponse(HTTP_BAD_REQUEST, $response);
                break;
            case ERR_CHECKSUM_INVALID:
                $response = responseChecksumInvalid();
                echoResponse(HTTP_BAD_REQUEST, $response);
                break;
            default:
                $response = responseGeneralError();
                echoResponse(HTTP_INTERNAL_ERROR, $response);
        }
    }
});*/

/**
 * Servei que ens permet comprovar si un token és vàlid
 */
/*$app->post('/checkToken', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "token"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    checkChecksumFromRequest();

    //Validem que el token sigui vàlid
    validateToken();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('checkToken', false, $params);

    //Si hem arribat aqui, vol dir que el token es valid. No fa falta tornar-ho a comprovar
    echoResponse(HTTP_OK, responseTokenValid());
});*/
//SERVEIS INTERNS PUBLICS - END

//SERVEIS MTRANS PUBLICS - START
/**
 * Servei que ens permet obtenir els clients del sistema MTRANS
 */
/*$app->post('/customer', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "token"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    checkChecksumFromRequest();

    //Validem que el token sigui vàlid
    validateToken();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('customer', false, $params);

    //Recuperem el token corresponent de la capa 2
    $db = new DbHandler();
    $data = $db->getExternalToken($params['token']);
    $authCode = getAuthorizationFromRequest();
    $res = false;

    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/customer', $authCode, $data);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response["error"] = false;
            $response["data"] = $res;
            echoResponse(HTTP_OK, $response);
    }
});*/

/**
 * Servei que ens permet obtenir els proveidors del sistema MTRANS
 */
/*$app->post('/supplier', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "token"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    checkChecksumFromRequest();

    //Validem que el token sigui vàlid
    validateToken();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('supplier', false, $params);

    //Recuperem el token corresponent de la capa 2
    $db = new DbHandler();
    $data = $db->getExternalToken($params['token']);
    $authCode = getAuthorizationFromRequest();
    $res = false;

    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/supplier', $authCode, $data);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response["error"] = false;
            $response["data"] = $res;
            echoResponse(HTTP_OK, $response);
    }
});*/
$app->post('/agenda', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();

       /*
            $agendaType == "ALL" OR '' -> TOTA L'AGENDA
            $agendaType == "CUS" -> NOMES CLIENTS
            $agendaType == "SUP" -> NOMES PROVEIDORS
        */    
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        //"token",
        "type"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    //checkChecksumFromRequest();

    //Validem que el token sigui vàlid
    //validateToken();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('agenda', false, $params, $authCode);

    //Recuperem el token corresponent de la capa 2
    //$db = new DbHandler();
    /*$data = $db->getExternalToken($params['token']);
    $authCode = getAuthorizationFromRequest();
    $res = false;

    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/agenda', $authCode, $data);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }*/

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/agenda', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response["error"] = false;
            $response["data"] = $res;
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/companies', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('companies', false, $params, $authCode);  

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/companies', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/centers', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('centers', false, $params, $authCode);  

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/centers', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/categories', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('categories', false, $params,$authCode);  

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/categories', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/sections', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('sections', false, $params, $authCode);    

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/sections', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/sectors', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('sectors', false, $params, $authCode);  

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/sectors', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/total_invoiced', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token",
        "agenda_id", //opt
        "date_from", //opt
        "date_end" //opt
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    /*validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('total_invoiced', false, $params, $authCode);     

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/total_invoiced', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});



$app->post('/getTrucksExpedition', 'authenticate', function () use ($app) {
	$authCode = getAuthorizationFromRequest();
	
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionCode",
        "centerCode"
        //"token"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    //checkChecksumFromRequest();

    //Validem que el token sigui vàlid
    //validateToken();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('getTrucksExpedition', false, $params, $authCode);

    //Recuperem el token corresponent de la capa 2
    $db = new DbHandler();
    $data = array();
    /*$extToken = $db->getExternalToken($params['token']);
    $res = false;

    //Si $extToken es un array, vol dir que hem trobat el token que busquem
    if(is_array($extToken)){
        $data["expeditionCode"] = $params["expeditionCode"];
        $data["token"] = $extToken["token"];
    
        $authCode = getAuthorizationFromRequest();
        
        //Si el valor del authcode no és un número d'error, vol dir que és correcte
        if (!is_numeric($authCode)) {
            //Generem la URL de la API que cridarem
            $url = createURLAPI();
            $res = CallAPI('POST', $url . '/getTrucksExpedition', $authCode, $data);
        } else {
            $res = ERR_AUTHCODE_NOT_FOUND;
        }
    }
    //Sino, ens haura tornat un codi d'error que haurem de gestionar
    else{
        $res = $extToken;
    }*/

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getTrucksExpedition', $authCode, $params);

    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }
	
    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
            //Si no hem tingut cap codi d'error, vol dir que ha anat tot OK
            //i mostrarem la resposta amb les dades obtingudes a la petició
            $response = responseTrucks($res); 
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/getExpeditionsData', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();

    $requiredParams = array(
        "year",
        "month",
        "centerCode"
    );

    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);

    logging('getExpeditionsData', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getExpeditionsData', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    $response = array();
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        default:
            $response = responseTrucks($res);
            echoResponse(HTTP_OK, $response);
    }
});

$app->post('/getSaleInvoices', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $requiredParams = array("dateInit", "dateEnd");
    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);
    logging('getSaleInvoices', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getSaleInvoices', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
            break;
        case ERR_JSON_DECODE_FAILED:
            echoResponse(HTTP_INTERNAL_ERROR, responseJSONDecodeFailed());
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            echoResponse(HTTP_BAD_REQUEST, responseAPIKeyNotFoundRequest());
            break;
        default:
            echoResponse(HTTP_OK, $res);
    }
});

$app->post('/getAllMovements', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $requiredParams = array("dateInit", "dateEnd");
    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);
    logging('getAllMovements', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getAllMovements', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
            break;
        case ERR_JSON_DECODE_FAILED:
            echoResponse(HTTP_INTERNAL_ERROR, responseJSONDecodeFailed());
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            echoResponse(HTTP_BAD_REQUEST, responseAPIKeyNotFoundRequest());
            break;
        default:
            echoResponse(HTTP_OK, $res);
    }
});

$app->post('/getBuyInvoices', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $requiredParams = array("dateInit", "dateEnd");
    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);
    logging('getBuyInvoices', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getBuyInvoices', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
            break;
        case ERR_JSON_DECODE_FAILED:
            echoResponse(HTTP_INTERNAL_ERROR, responseJSONDecodeFailed());
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            echoResponse(HTTP_BAD_REQUEST, responseAPIKeyNotFoundRequest());
            break;
        default:
            echoResponse(HTTP_OK, $res);
    }
});

$app->post('/getDetailInvoiceBuy', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $requiredParams = array("ImpFraNum", "ImpFraCtr", "ImpFraSer");
    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);
    logging('getDetailInvoiceBuy', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getDetailInvoiceBuy', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
            break;
        case ERR_JSON_DECODE_FAILED:
            echoResponse(HTTP_INTERNAL_ERROR, responseJSONDecodeFailed());
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            echoResponse(HTTP_BAD_REQUEST, responseAPIKeyNotFoundRequest());
            break;
        default:
            echoResponse(HTTP_OK, $res);
    }
});

$app->post('/getDetailInvoice', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $requiredParams = array("ImpFraNum", "ImpFraCtr", "ImpFraSer");
    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);
    logging('getDetailInvoice', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getDetailInvoice', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
            break;
        case ERR_JSON_DECODE_FAILED:
            echoResponse(HTTP_INTERNAL_ERROR, responseJSONDecodeFailed());
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            echoResponse(HTTP_BAD_REQUEST, responseAPIKeyNotFoundRequest());
            break;
        default:
            echoResponse(HTTP_OK, $res);
    }
});

$app->post('/getMonthlyMovements', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $requiredParams = array("month", "year");
    verifyRequiredParams($requiredParams);

    $params = json_decode($app->request->getBody(), true);
    logging('getMonthlyMovements', false, $params, $authCode);

    $res = false;
    if (!is_numeric($authCode)) {
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getMonthlyMovements', $authCode, $params);
    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }

    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
            break;
        case ERR_JSON_DECODE_FAILED:
            echoResponse(HTTP_INTERNAL_ERROR, responseJSONDecodeFailed());
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            echoResponse(HTTP_BAD_REQUEST, responseAPIKeyNotFoundRequest());
            break;
        default:
            echoResponse(HTTP_OK, $res);
    }
});
//REDUR
$app->post('/getTemperatureDataREDUR', 'authenticate', function () use ($app) {
	
	$authCode = getAuthorizationFromRequest();
	
	
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionOrder"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('getTemperatureDataREDUR', false, $params, $authCode);

    $db = new DbHandler();
    $data = array();
  
   

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getTemperatureReportRedur', $authCode, $params);

    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }
	
	
    $response = array();
	
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
			header('Content-Type: application/json');
			$response=json_encode($res,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // Convertir el array a JSON
			echo $response;
			//echoResponse(HTTP_OK, $response);
    }
});

//Ramoneda
$app->post('/getTemperatureDataRAMONEDA', 'authenticate', function () use ($app) {
	
	$authCode = getAuthorizationFromRequest();
	
	
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionOrder"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('getTemperatureDataRamoneda', false, $params, $authCode);

    $db = new DbHandler();
    $data = array();
  
   

    $res = false;
    //Si el valor del authcode no és un número d'error, vol dir que és correcte
    if (!is_numeric($authCode)) {
        //Generem la URL de la API que cridarem
        $url = createURLAPI();
        $res = CallAPI('POST', $url . '/getTemperatureReportRamoneda', $authCode, $params);

    } else {
        $res = ERR_AUTHCODE_NOT_FOUND;
    }
	
	
    $response = array();
    //Segons la resposta obtinguda de la API, mostrarem un resultat o un altre
    switch ($res) {
        case ERR_CURL_REQUEST_FAILED:
            $response = responseCURLRequestFailed();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_JSON_DECODE_FAILED:
            $response = responseJSONDecodeFailed();
            echoResponse(HTTP_INTERNAL_ERROR, $response);
            break;
        case ERR_AUTHCODE_NOT_FOUND:
            $response = responseAPIKeyNotFoundRequest();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_TOKEN_NOT_EXIST:
            $response = responseTokenNotExist();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;  
        case ERR_CHECKSUM_NOT_FOUND:
            $response = responseChecksumNotFound();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;
        case ERR_CHECKSUM_INVALID:
            $response = responseChecksumInvalid();
            echoResponse(HTTP_BAD_REQUEST, $response);
            break;                            
        default:
			header('Content-Type: application/json');
			$response=json_encode($res,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // Convertir el array a JSON
			echo $response;
			//echoResponse(HTTP_OK, $response);
    }
});


$app->post('/morosos/send_report_morosos', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $payload = $app->request->getBody();
    $targetUrl = 'http://91.187.69.73:8080/traldisporta-api/restapi_prod/v1/morosos/send_report_morosos';

    // Guardem registre al log
    $params = json_decode($payload, true);
    logging('morosos_send_report_morosos', false, $params, $authCode);

    $headers = array('Content-Type: application/json');
    if (!is_numeric($authCode)) {
        $headers[] = 'Authorization: ' . $authCode;
    }

    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if (!empty($payload)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        $response = responseCURLRequestFailed();
        echoResponse(HTTP_BAD_REQUEST, $response);
        return;
    }

    curl_close($ch);

    if ($statusCode <= 0) {
        $statusCode = HTTP_OK;
    }

    $decoded = json_decode($responseBody, true);
    if (is_array($decoded)) {
        echoResponse($statusCode, $decoded);
    } else {
        $app->response()->setStatus($statusCode);
        echo $responseBody;
    }
});

$app->post('/morosos/facturas_pendientes', 'authenticate', function () use ($app) {
    $authCode = getAuthorizationFromRequest();
    $payload = $app->request->getBody();
    $targetUrl = 'http://91.187.69.73:8080/traldisporta-api/restapi_prod/v1/morosos/facturas_pendientes';

    $params = json_decode($payload, true);
    logging('morosos_facturas_pendientes', false, $params, $authCode);

    $headers = array('Content-Type: application/json');
    if (!is_numeric($authCode)) {
        $headers[] = 'Authorization: ' . $authCode;
    }

    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if (!empty($payload)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        echoResponse(HTTP_BAD_REQUEST, responseCURLRequestFailed());
        return;
    }

    curl_close($ch);

    if ($statusCode <= 0) {
        $statusCode = HTTP_OK;
    }

    $decoded = json_decode($responseBody, true);
    if (is_array($decoded)) {
        echoResponse($statusCode, $decoded);
    } else {
        $app->response()->setStatus($statusCode);
        echo $responseBody;
    }
});



//SERVEIS MTRANS PUBLICS - END


//SERVEIX EXCLUSIUS ADMINISTRADOR - START
/**
 * Funció d'administrador que permet crear un usuari nou al sistema
 */
$app->post('/newUser', 'authenticate2', function () use ($app) {
    //Camps necessitaris per aquesta petició
    //En aquest cas, password és opcional.
    //Si s'inclou, crearà l'usuari amb la contrasenya facilitada, sino en generarà una aleatoria
    $requiredParams = array(
        "name",
        "username",
        //"password"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    //checkChecksumFromRequest();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('newUser', false, $params);
    $db = new DbHandler();

    $authCode = getAuthorizationFromRequest();
    $result = $db->createNewUser($params, $authCode);

    $response = array();
    //Si la resposta de la funcio no es un numero, vol dir que tot ha anat OK
    //i ens esta retornant l'informacio creada
    if (gettype($result) != "integer") {
        $response = responseUserCreated($result);
        echoResponse(HTTP_CREATED, $response);
    } else {
        //Si ens ha tornat un numero, vol dir que hi hagut algun error
        switch ($result) {
            case ERR_USER_ALREADY_EXIST:
                $response = responseUserAlreadyExist();
                break;
            case ERR_USER_NOT_CREATED:
                $response = responseUserNotCreated();
                break;
            default:
                $response = responseGeneralError();
                break;
        }

        echoResponse(HTTP_BAD_REQUEST, $response);
    }
});

/**
 * Funció d'administrador que permet canviar la contrasenya d'accés a un usuari
 */
$app->post('/newPassword', 'authenticate2', function () use ($app) {
    //Camps necessitaris per aquesta petició
    //Els seguents camps son opcionals.
    //user_id, username, token serveix per poder identificar quin usuari del sistema és. Ens serveix qualsevol dels 3 a la petició 
    $requiredParams = array(
        //"user_id",
        //"username",
        //"token",
        //"new_password",
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    //checkChecksumFromRequest();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //Guardem registre al log
    logging('newPassword', false, $params);

    $db = new DbHandler();

    $result = $db->saveNewPasswordUser($params);

    $response = array();
    if (!is_numeric($result)) {
        $response = responseUserPassChanged($result);
        echoResponse(HTTP_OK, $response);
    } else {
        switch ($result) {
            case ERR_TOKEN_NOT_EXIST:
                $response = responseTokenNotExist();
                break;
            case ERR_USER_NOT_FOUND:
                $response = responseUserNotFound();
                break;
            case ERR_USER_NOT_REQUEST:
                $response = responseUserNotRequest();
                break;
            default:
                $response = responseGeneralError();
                break;
        }

        echoResponse(HTTP_BAD_REQUEST, $response);
    }

});
//SERVEIX EXCLUSIUS ADMINISTRADOR - END

/* Executem l'aplicació */
$app->run();

?>