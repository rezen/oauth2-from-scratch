<?php


date_default_timezone_set("UTC");
session_start();

require '../shared/helpers.php';

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext  = pathinfo($path, PATHINFO_EXTENSION);

$user_id   = 12; // @todo
$client_ip = gethostbyname('client');
$server_ip = gethostbyname('server');

$paths = [
    '/server/oauth/token' => 'token.php',
    '/server/oauth/authorize' => 'authorize.php',
];

switch ($path) {
    case '/server/oauth/token':
        header("HTTP/1.1 200 OK");
        $grant_type    = $_POST['grant_type'] ?? null; // refresh_token,password,client_credentials,authorization_code,implicit
        $client_id     = $_POST['client_id'] ?? null;
        $client_secret = $_POST['client_secret'] ?? null;
        $code          = $_POST['code'] ?? null;
        $code_verifier = $_POST['code_verifier'] ?? null;
        $now           = time();
        
        if (!array_key_exists($client_id, $clients)) {
            return json_response([                
                'error_code' => 'invalid_request',
                'error' => "This is not a valid :client_id"
            ]);
        }

        $stmt = $db->prepare("SELECT * FROM auth_access_codes WHERE client_id=:client_id AND code=:code");
        if (!$stmt) {
            return json_response([                
                'error_code' => 'server_error',
                'error'      => $db->errorInfo(), // @todo don't ever do this fo' real
            ]);
        }
        $stmt->execute([
            'client_id' => $client_id, 
            'code'      => $code,
        ]); 
        $row = $stmt->fetch();
        if (!$row) {
            return json_response([                 
                'error_code' => 'invalid_request',
                "error" => 'The provided :code is not valid',
            ]);
        }

        if ($now >= $row['expiration']) {
            logger("Code expired now=$now expiry={$row['expiration']}");
            return json_response([                 
                'error_code' => 'invalid_request',
                "error" => 'The provided :code has expired',
            ]);
            exit;
        }

        $proof = hash("sha256", base64UrlEncode($code_verifier));
        if (!hash_equals($proof, $row['code_challenge'])) {
            return json_response([  
                'error_code' => 'invalid_request',
                "error" => 'The :code_verifier parameter was not verified',
            ]);
            exit;
        }

        $key  = md5(random_bytes(24));
        $iat  =  time();
        $expires_in = 3600;
        
        try {
            dbTableInsert($db, 'access_tokens', [
                'user_id'    => $user_id,
                'key'        => $key,
                'scope'      => $row['scope'],
                'expiration' => $iat + $expires_in,
                'code_id'    => (int) $row['id'], // @todo make code_id unique column
            ]);
        } catch (Exception $err) {
            $message =  $err->getMessage();
            // code_id is unique to prevent code reuse
            if ($message === "UNIQUE constraint failed: access_tokens.code_id") {
                logger("Attempted code reuse :code_id={$row['id']} ua={$_SERVER['HTTP_USER_AGENT']}");
                return json_response([
                    'error_code' => 'invalid_request',
                    "error"      => $message,
                ]);            
            }
            return json_response([
                'error_code' => 'server_error',
                "error"      => $message,
            ]);
        }

        header("Cache-Control: no-store");
        header("Pragma: no-cache");
        return json_response([
            "access_token" => $key,
            "id_token" => generateJwt($secret, [
                // https://www.iana.org/assignments/jwt/jwt.xhtml#claims
                'iss' => 'http://todo',
                'sub' => "user:$user_id",
                'aud' => 'TODO',
                'iat' => $iat,
                'exp' => $iat + $expires_in,
                "data" => [
                    'user_id' => $user_id,
                ],
            ]),
            "scope"      => "openid",
            "expires_in" => $expires_in,
            "token_type" => "Bearer"
        ]);
        break;

    case '/server/oauth/authorize':
        $client_id     = $_GET['client_id'] ?? null;
        $response_mode = $_GET['response_mode'] ?? "query";

        if (!array_key_exists($client_id, $clients)) {
            return view('authorize', [
                'error_code' => 'invalid_request',
                'error' => "This is not a valid :client_id"
            ]);
        }

        if (!isset($_GET['state']) || empty($_GET['state'])) {
            return view('authorize', [
                'error_code' => 'invalid_request',
                'error'      => "The :state parameter is not set"
            ]);
        }

        $code_challenge = $_GET['code_challenge'] ?? null;
        if (is_null($code_challenge) || empty($code_challenge)) {
            // @todo config for PKSE
            return view('authorize', [
                'error_code' => 'invalid_request',
                'error'      => "The :code_challenge parameter is not set"
            ]);
        }

        $state = preg_replace('[^a-z0-9]', "", $_GET['state'] ?? "");

        $client = $clients[$client_id];
        $scope  =  preg_replace('[^a-z0-9_-]', "", $_GET['scope'] ?? "");
       
        $redirect_url    = $client['redirect'];
        $redirect_params = explode("?", pathinfo($redirect_url, PHP_URL_QUERY))[1] ?? "";
        $redirect_url    = strtok($redirect_url, '?');
        
        $params = [];
        parse_str($redirect_params, $params);
        $params['code']   = $code = md5(random_bytes(24));
        $params['state']  = $state;
        $query =  http_build_query($params);
        $expiration = time() + 60; // Expires in 60s
        
        // For demoing expiration ... make short expiration
        if (isset($_GET['sleep'])) {
            echo "Fix that expire";
            $expiration = $expiration - 58;
        }

        dbTableInsert($db, 'auth_access_codes', [
            'client_id'      => $client_id,
            'scope'          => $scope,
            'code'           => $code,
            'expiration'     => $expiration,
            'user_id'        => $user_id,
            'code_challenge' => $code_challenge,
        ]);
        # For cancel ...
        # error=access_denied
        # error_description=Forbidden
        $separator = $response_mode === "fragment" ? "#" : "?";
        return view('authorize', [
            'name'         => $client['name'],
            'scope'        => $scope,
            'redirect_url' => "{$redirect_url}{$separator}{$query}",
        ]);
        break;
    default:
        echo 'NOT FOUND\n<br />';
        echo "$client_ip";
        die;
}
