<?php

define("HASH_ALGO", "sha256");

$secret   = getenv("APP_SECRET");
$here     = dirname(__FILE__);
$user     = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$dbhost   = getenv("DB_HOST");
$dsn      = "mysql:host=$dbhost;port=3306;dbname=oauth2";


$db = new PDO($dsn, $user, $password);

$db->exec('CREATE TABLE IF NOT EXISTS users (
    id   INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    password_hash TEXT NOT NULL,
    password_unsafe_never_do TEXT NOT NULL,
    UNIQUE(email)
  );');

$db->exec('CREATE TABLE IF NOT EXISTS user_consents (
    id   INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INTEGER NOT NULL,
    client_id TEXT NOT NULL,
    scope TEXT NOT NULL,
    revoked_at DATETIME DEFAULT NULL
    UNIQUE(user_id, client_id)
  );');

$db->exec('CREATE TABLE IF NOT EXISTS access_tokens (
    id   INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INTEGER NOT NULL,
    client_id TEXT NOT NULL,
    code_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL,
    scope TEXT NOT NULL,
    expiration INTEGER NOT NULL,
    UNIQUE(code_id)
  );');

// @todo include device/ip?
// @todo hash code
$db->exec('CREATE TABLE IF NOT EXISTS auth_access_codes (
    id   INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_id INTEGER NOT NULL,
    client_id TEXT NOT NULL,
    scope TEXT NOT NULL,
    code TEXT NOT NULL,
    code_challenge TEXT NOT NULL,
    expiration INTEGER NOT NULL,
    used_at DATETIME DEFAULT NULL
  );');

$db->exec('CREATE TABLE IF NOT EXISTS clients (
    id   INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    client_id VARCHAR(32) NOT NULL,
    client_secret TEXT NOT NULL,
    client_secret_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    redirect_uri TEXT NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    UNIQUE(client_id)
  );');


class DB
{
    static function insert($db, $table, $data) 
    {
        $keys = array_keys($data);
        $bindings = array_map(function($k) {
            return "?";
            return ":". $k;
        }, $keys);
        $sql = "INSERT INTO $table (" . implode(", ", $keys) . ') VALUES ('. implode(", ", $bindings) .')';
        $stmt = $db->prepare($sql);
    
        
        if (!$stmt) {
            throw new Exception("Prepare - " .  $db->errorInfo() . " $sql");
        }
    
        $success =  $stmt->execute(array_values($data));
        if (!$success) {
            throw new Exception("Execute - " .$stmt->errorInfo()[2] . " $sql");
        }
    }


static function rows($db, $table) {
    $table = preg_replace('[^a-z0-9_]', "", $table);
    $stmt = $db->prepare("SELECT * FROM `{$table}` WHERE 1=1");
    if (!$stmt) {
        return [null, $db->errorInfo()];
    }
    $stmt->execute();
    $rows =[];
    while ($row = $stmt->fetch()) {
        $rows[] = $row;
    }

    return [$rows, null];
}

    static function select($db, $sql, $data=[])
    {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare - " .  $db->errorInfo() . " $sql");
        }

        $success = $stmt->execute($data);
        if (!$success) {
            throw new Exception("Execute - " .$stmt->errorInfo()[2] . " $sql");
        }
        $rows =[];
        while ($row = $stmt->fetch()) {
            $rows[] = $row;
        }
        return $rows;   
    }
}

function getRecentCodes($db) {
    return DB::select($db, "SELECT * FROM auth_access_codes WHERE 1=1 AND expiration >= :past ORDER BY expiration DESC LIMIT 100", [
        'past' => time() - 7200,
    ]);   
}

function getRecentTokens($db) {
    return DB::select($db, "SELECT * FROM access_tokens WHERE 1=1 AND expiration >= :past ORDER BY expiration DESC LIMIT 100", [
        'past' => time() - 3600,
    ]);  
}

function getTokensForUser($db, $user_id) {
    return DB::select($db, "SELECT * FROM access_tokens WHERE 1=1 AND user_id=:user_id", [
        'user_id' => (int) $user_id,
    ]);  
}

function getClientById($db, $client_id) {
    $found = DB::select($db, "SELECT * FROM clients WHERE 1=1 AND client_id=:client_id", [
        'client_id' => $client_id,
    ]);

    if (empty($found)) {
        return false;
    }

    return (object) $found[0];
}

function getUserByEmail($db, $email) {
    $found = DB::select($db, "SELECT * FROM users WHERE 1=1 AND email=:email", [
        'email' => $email,
    ]);

    if (empty($found)) {
        return false;
    }

    return (object) $found[0];
}

function defaultHash($value) {
    return hash("sha256", $value);
}

function view($name, $data=[]) {
    $here = dirname(__FILE__);
    $__filename = "$here/views/$name.php";
    (function($vars) use ($__filename) {
        extract($vars);
        require $__filename;;
    })($data);
}


function partial($name, $data) {
    $here = dirname(__FILE__);
    $__filename = "$here/views/partials/$name.php";
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

class JWT
{
    static function atHash($token) 
    {
        $hash = hash(HASH_ALGO, $token, true);
        return base64UrlEncode(substr($hash, 0, strlen($hash) / 2));
    }

    static function parse($data)
    {
        $parts = explode(".", $data);
        $signature = hash_hmac(HASH_ALGO, $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    
        // @todo what if not three parts?
        $parts = array_map('base64_decode', $parts);
        [$header, $data, $signature] = $parts;
    
        // @todo what if not valid json
        return [json_decode($header), json_decode($data)];
    }

    static function generate($secret, $data) 
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
        $signature = hash_hmac(HASH_ALGO, $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = base64UrlEncode($signature);

        // Create JWT
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}

function logger($msg) {
    $now = date("Y-m-d H:i:s");
    file_put_contents(dirname(__FILE__) . "/log.txt", "[$now] $msg \n", FILE_APPEND);
}

function rebuildUrl($url, $separator="?", $data=[])
{
    $target    = strtok($url, '?');
    $qs = explode("?", pathinfo($url, PHP_URL_QUERY))[1] ?? "";
    $sans_query    = strtok($redirect_url, '?');
    $params = [];
    // Get params from redirect uri
    parse_str($qs, $params);
    $params = array_merge($params, $data);
    $query =  http_build_query($params);
    return $target . $separator . $query; 
}

$scopes = [
    'openid'
];

$terms = [
    'access_token'  => 'From server, Used by client to access resources on API OAuth server (required)',
    'id_token'      => 'From Server, has identity is JWT',
    'token_type'    => 'From server ... type of token',
    'refresh_token' => 'From server ... token that can be used to acquire a new access token when the original expires. Do not provide for response_type=token (optional)',
    'expires_in'    => 'From server, How long access_token is good for TTL integer (recommended)',
    'redirect_uri'  => 'What url on the client should the server redirect to. Should be match redirect_uri on server config',
    'client_id'     => 'Randomly generated id server uses to recognize client',
    'client_secret' => 'Random generated secret served checks against hash in storage',
    'grant_type'    => 'options=[refresh_token,client_credentials,authorization_code]',
    'code'          => 'Generated on the server for the client to redeem for an access token',
    'response_mode' => "options=[fragment,query,form_data]",
    'response_type' => "You can use options=[code,token,id_token token]",
    'state'          => 'For CSRF protection, generated on the client and the server sends it back to client',
    'scope'          => 'A space-delimited list of permissions that the application requires. (optional)',
    'code_verifier'  => 'Client generated is a cryptographically random string using the characters A-Z, a-z, 0-9, and the punctuation characters -._~ (hyphen, period, underscore, and tilde), between 43 and 128 characters long. Used in first request by client for sending <code>code_challenge</code>',
    'code_challenge' => 'Client sends this to server, a code challenge is a BASE64-URL-encoded string of the SHA256 hash of the code verifier',
    'code_challenge_method' => 'Can be none or S256 ... please implement S256 options=[S256,plain]',
    'nonce' =>	'A value that is returned in the ID token. It is used to mitigate replay attacks.',
    'prompt' => 'options=[none,consent,login]',
    'login_hint' => 'A username to prepopulate if prompting for authentication.',
];

foreach($terms as $attr => $value) {
    $terms[$attr] = trim($value);
}


function get_terms() {
    global $terms;
    return $terms;
}