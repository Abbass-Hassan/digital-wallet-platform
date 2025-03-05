<?php
// verify_jwt.php
function verify_jwt(string $jwt, string $secret)
{
    // Split the token
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3) {
        return false;
    }

    list($base64Header, $base64Payload, $base64Signature) = $tokenParts;

    // Decode
    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Header)), true);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Signature));

    // Check algorithm
    if ($header['alg'] !== 'HS256') {
        return false;
    }

    // Build signature
    $validSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);

    if (!hash_equals($validSignature, $signature)) {
        return false;
    }

    // Check if token is expired
    $now = time();
    if ($payload['exp'] < $now) {
        return false; // Token has expired
    }

    // If all checks pass, return the payload
    return $payload;
}
