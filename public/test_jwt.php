<?php

function token()
{
    $secret = 'olikjulekarek';

    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $header = json_encode($header);
    $header = base64_encode($header);

    $payload = [
        'username' => 'admin',
        'emial' => 'admin@gmail.com'
    ];
    $payload = json_encode($payload);
    $payload = base64_encode($payload);

    $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
    $signature = base64_encode($signature);

    $token = "$header.$payload.$signature";

    return $token;

}

$received_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VybmFtZSI6ImFkbWluIiwiZW1pYWwiOiJhZG1pbkBnbWFpbC5jb20ifQ==.uHD3g36iLYt0PfrEqvnSLl4S8/XcakI5NtbOo/panwQ=';

if($received_token === token())
{
    echo 'Token poprawny';
} else {
    echo 'Token niepoprawny';
}
