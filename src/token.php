<?php

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