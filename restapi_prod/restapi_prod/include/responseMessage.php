<?php

//Respostes sense error
function responseTokenValid()
{
    return array(
        "error" => false,
        "message" => TXT_TOKEN_VALID
    );
}

function responseTokenCreated($data=false)
{
    return array(
        "error" => false,
        "message" => TXT_TOKEN_CREATED,
        "data" => $data
    );
}

function responseTokenFound($data=false)
{
    return array(
        "error" => false,
        "message" => TXT_TOKEN_FOUND,
        "data" => $data
    );
}

function responseUserCreated($data=false)
{
    return array(
        "error" => false,
        "message" => TXT_USER_CREATED,
        "data" => $data
    );
}

function responseUserPassChanged()
{
    return array(
        "error" => false,
        "message" => TXT_USER_PASS_CHANGED
    );
}

function responseTrucks($data=false){
    return array(
        "error" => false,
        "message" => TXT_GET_TRUCKS, 
        "data" => $data
    );
}


//Respostes amb error

function responseGeneralError(){
    return array(
        "error" => true,
        "message" => TXT_GENERAL_ERROR
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

function responseTokenNotCreated()
{
    return array(
        "error" => true, 
        "message" => TXT_TOKEN_NOT_CREATED
    );
}


function responseChecksumInvalid(){
    return array(
        "error" => true,
        "message" => TXT_CHECKSUM_INVALID
    );
}

function responseChecksumNotFound(){
    return array(
        "error" => true,
        "message" => TXT_CHECKSUM_NOT_FOUND 
    );
}


?>