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
        session_regenerate_id(true);
        header("HTTP/1.1 200 OK");
        $grant_type    = $_POST['grant_type'] ?? null; // refresh_token,client_credentials,authorization_code
        $client_id     = $_POST['client_id'] ?? null;
        $client_secret = $_POST['client_secret'] ?? null;
        $code          = $_POST['code'] ?? null;
        $nonce         = $_POST['nonce'] ?? null;
        $code_verifier = $_POST['code_verifier'] ?? null;
        $now           = time();
        
        // Verify this is a read client_id
        if (!array_key_exists($client_id, $clients)) {
            return json_response([                
                'error_code' => 'invalid_client',
                'error' => "This is not a valid :client_id"
            ]);
        }

        // Verify valid secret
        if (!hash_equals($clients[$client_id]['secret']  ?? "", $client_secret)) {
            return json_response([                
                'error_code' => 'invalid_client_client',
                'error'      => "This is not a valid :client_secret"
            ]);
        }

        $stmt = $db->prepare("SELECT * FROM auth_access_codes WHERE client_id=:client_id AND code=:code");
        
        // For debugging purposes ... don't do this in a real project
        if (!$stmt) {
            return json_response([                
                'error_code' => 'server_error',
                'error'      => $db->errorInfo(), 
            ]);
        }
        $stmt->execute([
            'client_id' => $client_id, 
            'code'      => $code,
        ]); 
        $row = $stmt->fetch();

        // Invalid code provided
        if (!$row) {
            return json_response([                 
                'error_code' => 'invalid_request',
                "error" => 'The provided :code is not valid',
            ]);
        }

        // Attempting to use use expired code
        if ($now >= $row['expiration']) {
            logger("Code expired now=$now expiry={$row['expiration']}");
            return json_response([                 
                'error_code' => 'invalid_request',
                "error" => 'The provided :code has expired',
            ]);
            exit;
        }

        $stmt = $db->prepare("UPDATE auth_access_codes SET used_at=:now WHERE id=:id");
   
        if (!$stmt) {
            return json_response([                
                'error_code' => 'server_error',
                'error'      => $db->errorInfo(), 
            ]);
        }

        $success = $stmt->execute([
            'id' => $row['id'], 
            'now' => date("Y-m-d H:i:s"),
        ]);

        if (!$success) {
            return json_response([                
                'error_code' => 'server_error',
                'error'      => $stmt->errorInfo()[2], 
            ]);        
        }

        // Verify code_verifier for PKSE
        $proof = hash(HASH_ALGO, base64UrlEncode($code_verifier));
        if (!hash_equals($proof, $row['code_challenge'])) {
            return json_response([  
                'error_code' => 'invalid_request',
                "error" => 'The :code_verifier parameter was not verified',
            ]);
            exit;
        }

        // Opaque key used by applications
        $token  = base64UrlEncode(random_bytes(128));
        $iat    = time();
        $expires_in = 3600;
        
        try {
            DB::insert($db, 'access_tokens', [
                'user_id'    => (int) $user_id,
                'client_id'  => $client_id,
                'token_hash' => defaultHash($token),
                'scope'      => $row['scope'],
                'expiration' => $iat + $expires_in,
                'code_id'    => (int) $row['id'],
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

        header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
        header("Pragma: no-cache");
        return json_response([
            "access_token" => $token,
            "id_token" => JWT::generate($secret, [
                // https://www.iana.org/assignments/jwt/jwt.xhtml#claims
                'iss'       => "http://$server_ip",
                'uid'       => "$user_id",
                'sub'       => "user:$user_id",
                'aud'       => $client_id,
                'iat'       => $iat,
                'exp'       => $iat + $expires_in,
                'nonce'     => $nonce,
                'at_hash'   => JWT::atHash($token),
                "data" => [
                    'user_id' => $user_id,
                ],
            ]),
            "scope"      => $row['scope'],
            "expires_in" => $expires_in,
            "token_type" => "Bearer"
        ]);
        break;

    case '/server/oauth/authorize':
        $client_id     = $_GET['client_id'] ?? null;
        $response_mode = $_GET['response_mode'] ?? "query";

        if (!array_key_exists($client_id, $clients)) {
            return view('authorize', [
                'error_code' => 'invalid_client',
                'error'      => "This is not a valid :client_id"
            ]);
        }

        // For strictness ... require the client to sent state
        if (!isset($_GET['state']) || empty($_GET['state'])) {
            return view('authorize', [
                'error_code' => 'invalid_request',
                'error'      => "The :state parameter is not set"
            ]);
        }

        // Needed for PKSE
        $code_challenge = $_GET['code_challenge'] ?? null;
        if (is_null($code_challenge) || empty($code_challenge)) {
            return view('authorize', [
                'error_code' => 'invalid_request',
                'error'      => "The :code_challenge parameter is not set"
            ]);
        }

        $state = preg_replace('[^A-Za-z0-9_-]', "", $_GET['state'] ?? "");

        $client = $clients[$client_id];
        $scope  = preg_replace('[^A-Za-z0-9_-: ]', "", $_GET['scope'] ?? "");
       
        $redirect_url    = $client['redirect'];
        $redirect_params = explode("?", pathinfo($redirect_url, PHP_URL_QUERY))[1] ?? "";
        $redirect_url    = strtok($redirect_url, '?');
        
        $params = [];
        // Get params from redirect uri
        parse_str($redirect_params, $params);
        $params['code']   = $code = md5(random_bytes(24));
        $params['state']  = $state;
        $query =  http_build_query($params);
        $expiration = time() + 60; // Expires in 60s
        
        // For demoing expiration ... make short expiration
        if (isset($_GET['sleep'])) {
            $expiration = $expiration - 58;
        }

        $separator = $response_mode === "fragment" ? "#" : "?";

        $_SESSION['access_code'] = [
            'client_id'      => $client_id,
            'scope'          => $scope,
            'code'           => $code,
            'expiration'     => $expiration,
            'user_id'        => $user_id,
            'code_challenge' => $code_challenge,
        ];

        $_SESSION['redirect_url'] = "{$redirect_url}{$separator}{$query}";

   
        # For cancel ...
        # error=access_denied
        # error_description=Forbidden
        $separator = $response_mode === "fragment" ? "#" : "?";
        return view('authorize', [
            'client'       => (object) $client,
            'scope'        => $scope,
            'redirect_url' => "{$redirect_url}{$separator}{$query}",
        ]);
        break;
    case '/server/oauth/authorize/consent':
        if (empty($_SESSION)) {
            echo 'nothing here';
            return;
        }

        DB::insert($db, 'auth_access_codes', $_SESSION['access_code']);
        $redirect_url = $_SESSION['redirect_url'];
        unset($_SESSION);
        session_destroy();

        header("Pragma: no-cache");
        header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
        header("Location: $redirect_url", true, 302);
        return view('redirect', [
            'redirect_url' => $redirect_url . '&step=1',
        ]);
    default:
        echo 'NOT FOUND\n<br />';
        echo "$client_ip";
        die;
}
