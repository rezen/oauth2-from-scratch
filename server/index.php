<?php


date_default_timezone_set("UTC");
session_start();

require '../shared/helpers.php';

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext  = pathinfo($path, PATHINFO_EXTENSION);

$client_ip = gethostbyname('client');
$server_ip = gethostbyname('server');

$paths = [
    '/server/oauth/token' => 'token.php',
    '/server/oauth/authorize' => 'authorize.php',
];

switch ($path) {
    case '/auth/logout':
        $_SESSION = [];
        unset($_SESSION);
        session_destroy();
        header('Location: /auth/login');
    break;
    case '/auth/login':
        $error = false;
        $redirect = "/dashboard";

        if (isset($_GET['return_to'])) {
            $parts = parse_url($_GET['return_to']);
            $path = $parts['path'];
            $query = urldecode($parts['query']);
            if(in_array($path, [
                '/server/oauth/authorize'
            ])) {
                $redirect = "{$path}?{$query}";
            }
        }


        if (!empty($_POST)) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $user = getUserByEmail($db, $email);

            // Error should be could ambigous "authn failed, could not find matching username & password" 
            // however for this educational app it is less relevant
            if (!$user) {
                $error = "That user does not exist";
            } else if (!password_verify($password, $user->password_hash)) {
                $error = 'Password is invalid!';
            } else {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_id'] = $user->id;

                header("Location: $redirect");
                exit;
            }
        }
        return view('login_server', [
            'title' => 'Login',
            'error' => $error,
        ]);
    case '/dashboard':
        $is_logged_in = $_SESSION['logged_in'] ?? false;

        if (!$is_logged_in) {
            header('Location: /auth/login');
            return;
        }
        [$tokens, $error] = getTokensForUser($db, $_SESSION['user_id']);
        return view('dashboard_server', [
            'error'  => $error,
            'tokens' => $tokens,
            'email'  => $_SESSION['user_email'],
        ]);
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
        
        $client = getClientById($db, $client_id);

        // Verify this is a read client_id
        if (!$client) {
            return json_response([                
                'error_code' => 'invalid_client',
                'error' => "This is not a valid :client_id"
            ]);
        }

        // Verify valid secret
        if (!hash_equals($client->client_secret_hash  ?? "", hash('sha256', $client_secret))) {
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
        $user_id = $row['user_id'];

        try {
            DB::insert($db, 'access_tokens', [
                'user_id'    => (int) $user_id, // @todo
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
        // If authorized client_id ... no need for consent
        $client_id     = $_GET['client_id'] ?? null;
        $response_mode = $_GET['response_mode'] ?? "query";

        $client = getClientById($db, $client_id);

        if (!$client) {
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

        $is_logged_in = $_SESSION['logged_in'] ?? false;
        if (!$is_logged_in) {
            $back = urlencode("/server/oauth/authorize?" . http_build_query($_GET));
            header("Location: /auth/login?return_to=$back");
            return;
        }


        $state = preg_replace('[^A-Za-z0-9_-]', "", $_GET['state'] ?? "");
        $scope  = preg_replace('[^A-Za-z0-9_-: ]', "", $_GET['scope'] ?? "");
       
        $separator = $response_mode === "fragment" ? "#" : "?";
        $redirect_url    = $client->redirect_uri;
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


        $_SESSION['access_code'] = [
            'client_id'      => $client_id,
            'scope'          => $scope,
            'code'           => $code,
            'expiration'     => $expiration,
            'user_id'        => $_SESSION['user_id'],
            'code_challenge' => $code_challenge,
        ];

        $_SESSION['client_redirect_uri'] = $client->redirect_uri;
        $_SESSION['redirect_url'] = "{$redirect_url}{$separator}{$query}";

   
        $separator = $response_mode === "fragment" ? "#" : "?";
        return view('authorize', [
            'email'        => $_SESSION['user_email'],
            'client'       => (object) $client,
            'scope'        => $scope,
            'response_mode' => $response_mode,
            'redirect_url' => "{$redirect_url}{$separator}{$query}",
        ]);
        break;
    case '/server/oauth/authorize/consent':
        if (empty($_SESSION)) {
            echo 'nothing here';
            return;
        }

        $redirect_url = $_SESSION['redirect_url'];
        $separator = $response_mode === "fragment" ? "#" : "?";


        if ($_POST['consent'] !== "1") {
            $redirect_url = rebuildUrl($redirect_url, $separator, [
                'code' => 0,
                'error' => 'access_denied',
                'error_description' => 'User did not consent',
            ]);

            header("Pragma: no-cache");
            header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
            header("Location: $redirect_url", true, 302);
            return view('redirect', [
                'redirect_url' => $redirect_url,
            ]);
        }

        DB::insert($db, 'auth_access_codes', $_SESSION['access_code']);
        unset($_SESSION);
        session_destroy();

        header("Pragma: no-cache");
        header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
        header("Location: $redirect_url", true, 302);
        return view('redirect', [
            'redirect_url' => $redirect_url . '&step=1',
        ]);
    case '/server/clients':
        $error = false;
        $client = false;
        if (!empty($_POST)) {
            $name = preg_replace('/[^A-Za-z0-9_-]+/', "", $_POST['name'] ?? "");
            $redirect_uri = $_POST['redirect_uri'] ?? "";
            $protocol = parse_url($redirect_uri, PHP_URL_SCHEME);

            // Don't allow http for real oauth
            if (!in_array($protocol, ['http', 'https'])) {
                $error = "Invalid protocol ($protocol)";
            }

            if (!filter_var($redirect_uri, FILTER_VALIDATE_URL)) {
                $error = "Invalid url";
            }

            if (!$error) {
                $secret = bin2hex(random_bytes(40));
                $client = [
                    'redirect_uri' => $redirect_uri,
                    'name'         => $name,
                    'client_id'    => md5(random_bytes(32)),
                    'client_secret_hash' => hash('sha256', $secret),
                    'client_secret'  => $secret,
                ];
                DB::insert($db, 'clients', $client);
            }
        }

        [$clients, $error2] = DB::rows($db, 'clients');
        return view('manage_clients', [
            'error'   => $error ?? $error2,
            'clients' => $clients,
            'client'  => is_array($client) ? (object) $client : $client,
        ]);
    case '/server/users':
            $error = false;
            if (!empty($_POST)) {
                $email = $_POST['email'] ?? "";
    
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email";
                }
    
                if (!$error) {
                    $password = $_POST['password'] ?? md5(random_bytes(32));
                    $password = empty($password) ? md5(random_bytes(32)) : $password;
                    DB::insert($db, 'users', [
                        'email'         => $email,
                        'password_hash' => password_hash($password, PASSWORD_BCRYPT, [
                            'cost' => 12,
                        ]),
                        'password_unsafe_never_do' => $password,
                    ]);
                }
            }
    
            [$users, $error2] = DB::rows($db, 'users');
            return view('manage_users', [
                'error'   => $error ?? $error2,
                'users' => $users,
            ]); 
     
    default:
        echo 'NOT FOUND\n<br />';
        echo "$client_ip";
        die;
}
