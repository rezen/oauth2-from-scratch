<?php

$secret = getenv("APP_SECRET");;

$here = dirname(__FILE__);

$db = new PDO("sqlite:$here/database.db");
$db->exec('CREATE TABLE IF NOT EXISTS access_tokens (
    id   INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    key TEXT NOT NULL,
    scope TEXT NOT NULL,
    expiration INTEGER NOT NULL
  );');


$db->exec('CREATE TABLE IF NOT EXISTS auth_access_codes (
    id   INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    client_id TEXT NOT NULL,
    scope TEXT NOT NULL,
    code TEXT NOT NULL,
    code_challenge TEXT NOT NULL,
    expiration INTEGER NOT NULL
  );');

$db->exec('CREATE TABLE IF NOT EXISTS clients (
    id   INTEGER PRIMARY KEY,
    client_id TEXT NOT NULL,
    client_secret_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    redirect_uri TEXT NOT NULL
  );');

function dbTableInsert($db, $table, $data) 
{
    $keys = array_keys($data);
    $bindings = array_map(function($k) {
        return ":". $k;
    }, $keys);
    $sql = "INSERT INTO $table(" . implode(", ", $keys) . ') VALUES('. implode(", ", $bindings) .')';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "\nPDO::errorInfo():\n";
        print_r($db->errorInfo());
    }

    foreach($data as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    return $stmt->execute();
}

function view($name, $data=[]) {
    $__filename = "views/$name.php";
    (function($vars) use ($__filename) {
        extract($vars);
        require $__filename;;
    })($data);
}

function base64UrlEncode($text)
{
    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($text)
    );
}

function parseJwt($data) 
{
    $parts = explode(".", $data);

    // @todo what if not three parts?
    $parts = array_map('base64_decode', $parts);
    [$header, $data, $signature] = $parts;

    // @todo what if not valid json
    return json_decode($data);
}


function generateJwt($secret, $data) 
{
    // Create the token header
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]);

    // Create the token payload
    $payload = json_encode($data);

    // Encode Header
    $base64UrlHeader = base64UrlEncode($header);

    // Encode Payload
    $base64UrlPayload = base64UrlEncode($payload);

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

    // Encode Signature to Base64Url String
    $base64UrlSignature = base64UrlEncode($signature);

    // Create JWT
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function logger($msg) {
    $now = date("Y-m-d H:i:s");
    file_put_contents(dirname(__FILE__) . "/log.txt", "[$now] $msg \n", FILE_APPEND);
}

$clients = [
    'id1' => [
        'name'     => "Doe's Cats",
        'id'       => 'id1',
        'secret'   => 'secret',
        'redirect' => "http://localhost:4443/client/cb?server=test",
    ],
];

$scopes = [
    'photos'
];


