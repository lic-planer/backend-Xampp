<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

use \Firebase\JWT\JWT;

class token
{
    public function getToken ($request)
    {
        $authHeader = $request->getHeader('authorization');
        list($jwt) = sscanf( implode ($authHeader), 'Bearer %s');
        $token = JWT::decode($jwt, SECRET,[ALGORITHM]);

        return $token;
    }
}