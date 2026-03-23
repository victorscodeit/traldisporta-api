<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Sep-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

//Respostes sense error
function responseTokenValid()
{
    return array(
        "error" => false,
        "message" => TXT_TOKEN_VALID
    );
}

function responseTokenCreated()
{
    return array(
        "error" => false,
        "message" => TXT_TOKEN_CREATED
    );
}

function responseUserCreated($data = false)
{
    return array(
        "error" => false,
        "message" => TXT_USER_CREATED,
        "data" => $data
    );
}

function responseUserPassChanged($data = false)
{
    return array(
        "error" => false,
        "message" => TXT_USER_PASS_CHANGED,
        "data" => $data
    );
}


//Respostes amb error

function responseGeneralError()
{
    return array(
        "error" => true,
        "message" => TXT_GENERAL_ERROR
    );
}

function responseUserPassInvalid()
{
    return array(
        "error" => true,
        "message" => TXT_USER_PASS_INVALID
    );
}

function responseAPIKeyNotValid()
{
    return array(
        "error" => true,
        "message" => TXT_AUTHORIZATION_KEY_NOT_VALID
    );
}

function responseAPIKeyNotFoundRequest()
{
    return array(
        "error" => true,
        "message" => TXT_AUTHORIZATION_KEY_NOT_FOUND_REQUEST
    );
}


function responseTokenExpired()
{
    return array(
        "error" => true,
        "message" => TXT_TOKEN_EXPIRED
    );
}

function responseTokenNotExist()
{
    return array(
        "error" => true,
        "message" => TXT_TOKEN_NOT_EXIST
    );
}

function responseTokenNotFoundRequest()
{
    return array(
        "error" => true,
        "message" => TXT_TOKEN_NOT_FOUND_REQUEST
    );
}

function responseRequiredFieldsNotFound($fields)
{
    $txt = str_replace('%s', $fields, TXT_REQUIRED_FIELDS_NOT_FOUND);
    return array(
        "error" => true,
        "message" => $txt
    );
}

function responseUserNotFound()
{
    return array(
        "error" => true,
        "message" => TXT_USER_NOT_FOUND
    );
}

function responseUserAlreadyExist()
{
    return array(
        "error" => true,
        "message" => TXT_USER_ALREADY_EXIST
    );
}

function responseUserNotCreated()
{
    return array(
        "error" => true,
        "message" => TXT_USER_NOT_CREATED
    );
}

function responseUserNotRequest()
{
    return array(
        "error" => true,
        "message" => TXT_USER_NOT_REQUEST
    );
}

function responseCURLRequestFailed()
{
    return array(
        "error" => true,
        "message" => TXT_CURL_REQUEST_FAILED
    );
}
function responseJSONDecodeFailed()
{
    return array(
        "error" => true,
        "message" => TXT_JSON_DECODE_FAILED
    );
}

function responseChecksumInvalid()
{
    return array(
        "error" => true,
        "message" => TXT_CHECKSUM_INVALID
    );
}

function responseChecksumNotFound()
{
    return array(
        "error" => true,
        "message" => TXT_CHECKSUM_NOT_FOUND
    );
}

function responseTrucks($data = false)
{
    $error = false;
    $message = "";
    if (isset($data["error"])) {
        if ($data["error"] == true) {
            $error = true;
        }
    }
    if (isset($data["message"])) {
        $message = $data["message"];
    } else {
        if ($error) {
            $message = TXT_GENERAL_ERROR;
        } else {
            $message = TXT_ALL_OK;
        }
    }

    //Preparem l'array de resposta
    $response =  array(
        "error" => $error,
        "message" => $message
    );

    //Si no hi ha cap error
    if(!$error){
        //Eliminem els tags errors i message que inclou el camp data per tal que no surtin repetits
        unset($data["error"]);
        unset($data["message"]);

        //afegim el parametre data dins de la resposta
        $response["data"] = $data["data"];
    }

    return $response;
}


?>