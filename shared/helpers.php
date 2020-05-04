<?php

$secret = getenv("APP_SECRET");
$here   = dirname(__FILE__);

$db = new PDO("sqlite:$here/database.db");
# @todo revoked_at
$db->exec('CREATE TABLE IF NOT EXISTS access_tokens (
    id   INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    code_id INTEGER NOT NULL,
    key TEXT NOT NULL,
    scope TEXT NOT NULL,
    expiration INTEGER NOT NULL,
    UNIQUE(code_id)
  );');


$db->exec('CREATE TABLE IF NOT EXISTS auth_access_codes (
    id   INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    client_id TEXT NOT NULL,
    scope TEXT NOT NULL,
    code TEXT NOT NULL,
    code_challenge TEXT NOT NULL,
    expiration INTEGER NOT NULL,
    used_at DATETIME DEFAULT NULL
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
    $success =  $stmt->execute();

    if (!$success) {
        throw new Exception($stmt->errorInfo()[2]);
    }
}

function view($name, $data=[]) {
    $here = dirname(__FILE__);
    $__filename = "$here/views/$name.php";
    (function($vars) use ($__filename) {
        extract($vars);
        require $__filename;;
    })($data);
}

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
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
    'openid'
];

$terms = [
    'access_token'  => 'From server, Used by client to access resources on API OAuth server (required)',
    'id_token'      => 'From Server, has identity is JWT',
    'token_type'    => 'From server ... type of token',
    'refresh_token' => 'From server ... (optional)',
    'expires_in'    => 'From server, How long access_token is good for (recommended)',
    'redirect_uri'  => 'What url on the client should the server redirect to. Should be match redirect_uri on server config',
    'client_id'     => 'Randomly generated id server uses to recognize client',
    'client_secret' => 'Random generated secret served checks against hash in storage',
    'grant_type'    => 'options=[refresh_token,password,client_credentials,authorization_code,implicit]',
    'code'          => 'Generated on the server for the client to redeem for an access token',
    'response_mode' => "options=[fragment,query]",
    'response_type' => "You can use options=[code,token,id_token token]",
    'state'          => 'For CSRF protection, generated on the client and the server sends it back to client',
    'scope'          => 'A space-delimited list of permissions that the application requires. (optional)',
    'code_verifier'  => 'Client generated is a cryptographically random string using the characters A-Z, a-z, 0-9, and the punctuation characters -._~ (hyphen, period, underscore, and tilde), between 43 and 128 characters long. Used in first request by client for sending <code>code_challenge</code>',
    'code_challenge' => 'Client sends this to server, a code challenge is a BASE64-URL-encoded string of the SHA256 hash of the code verifier',
    'code_challenge_method' => 'Can be none or S256 ... please implement S256 options=[S256,plain]',

];

foreach($terms as $attr => $value) {
    $terms[$attr] = trim($value);
}


function get_terms() {
    global $terms;
    return $terms;
}