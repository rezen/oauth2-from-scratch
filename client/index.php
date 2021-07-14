<?php


date_default_timezone_set("UTC");
session_start();

require '../shared/helpers.php';

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext  = pathinfo($path, PATHINFO_EXTENSION);

$server_ip  = gethostbyname('server');
$server_url = "http://$server_ip:4444";
$token_url  = "$server_url/server/oauth/token";


switch ($path) {
    case '/client/start':
        if (!empty($_SESSION['access_token'])) {
            header("Location: /client/dashboard");
            exit;
        }

        [$clients, $error] = DB::rows($db, 'clients');

        $client = $clients[0] ?? null;
        $iat =  time();
        $query = null;

        if (!is_null($client)) {
            $_SESSION['client_id'] = $client['client_id'];

            if (!isset($_SESSION['state'])) {
                $_SESSION['nonce']         = md5(random_bytes(24));
                $_SESSION['state']         = md5(random_bytes(24));
                $_SESSION['code_verifier'] = Base64::urlEncode(random_bytes(24));
            }

            $query = http_build_query([
                'client_id'      => $client['client_id'],
                'response_type'  => 'code', // code|token|id_token token
                'response_mode'  => 'query', // query,fragment
                'state'          => $_SESSION['state'],
                'nonce'          => $_SESSION['nonce'],
                'scope'          => 'openid',
                'redirect_uri'   => $client['redirect_uri'],
                'code_challenge' => hash(HASH_ALGO, Base64::urlEncode($_SESSION['code_verifier'])),
                'code_challenge_method' => 'S256',
            ]);

            if (isset($_GET['sleep'])) {
                $query .= "&sleep=1";
            }
        } else {
            unset($_SESSION['state']);
            unset($_SESSION['client_id']);
        }
        
        return view("login", [
            'title'  => 'Start',
            'query'  => $query,
            'client' => $client,
        ]);
        break;
    case '/client/callback':
        if (empty($_GET['state']) || empty($_SESSION['state'])) {
            return view("error", [
                'error' => "Missing the :state parameter",
            ]);
        }

        if (empty($_SESSION)) {
            return view("error", [
                'error' => "Restart flow ...",
            ]);
        }

        if (!hash_equals($_SESSION['state'], $_GET['state'])) {
            return view("error", [
                'error' => "The :state parameter does not match",
            ]);
        }
        
        $client = getClientById($db, $_SESSION['client_id']);

        if (!$client) {
            return view("error", [
                'error' => "The :client_id does not match any clients {$_SESSION['client_id']}",
            ]);
        }

        if (isset($_GET['error'])) {
            return view("error", [
                'error' => $_GET['error_description'],
            ]);
        }

        $data = [
            'grant_type'    =>'authorization_code',
            'client_id'     => $client->client_id,
            'client_secret' => $client->client_secret,
            'redirect_uri'  => $client->redirect_uri,
            'code'          => $_GET['code'],
            "code_verifier" => $_SESSION['code_verifier'],
            'nonce'         => $_SESSION['nonce'],
        ];

        $data_string = http_build_query($data);
        $codes = getRecentCodes($db);
        /*
        if (isset($_GET['step']) && $_GET['step'] === "1") {
            return view('step_1', [
                'title'       => 'Step 1',
                'codes'       => $codes,
                'token_url'   => $token_url,
                'client_post' => $data_string,
                'server_response' => $_SESSION['server_response'],    
            ]);        
        }
        */

        $ch = curl_init();

        // For demo of expiring tokens
        if (isset($_GET['sleep'])) {
            set_time_limit(10);
            sleep(4);
        }

        curl_setopt($ch,CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // ...
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            return view("error", [
                'error' => curl_error($ch),
            ]);
        }

        // Then, after your curl_exec call:
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        $data        = json_decode($body);

        if (json_last_error()) {
            return view("error", [
                'error' => $body,
            ]);
        }
        // @todo check for json parse errors
        if (isset($data->error_code)) {
            return view("error", [
                'error' => $data->error ?? $data->error_code,
            ]);
        }
        [$header, $payload] = JWT::parse($data->id_token);
        $_SESSION['client_post']  =  $data_string;
        $_SESSION['server_response']  =  $data;
        $_SESSION['access_token'] = $data->access_token;
        $_SESSION['jwt_payload'] = $payload;
        $_SESSION['jwt_header'] = $header;
    
        unset($_SESSION['state']);
        unset($_SESSION['code_verifier']);
        header("Location: /client/dashboard");
        exit;        
        break;
    case '/client/logout':
        $_SESSION = [];
        session_destroy();
        header("Location: /client/start");
        exit;

        break;

    case '/client/dashboard':
        if (empty($_SESSION['access_token'])) {
            header("Location: /client/start?error=access_denied");
            exit;
        }
        $error = null;
        $codes  = getRecentCodes($db);
        $tokens = getRecentTokens($db);
        
        try {
            JWT::verify($_SESSION['server_response']->id_token);
        } catch(Exception $err) {
            $error = $err->getMessage();
        }

        return view('dashboard', [
            'error'           => $error, 
            'codes'           => $codes,
            'tokens'          => $tokens,
            'title'           => 'dashboard',
            'token_url'       => $token_url,
            'access_token'    => $_SESSION['access_token'],
            'client_post'     => $_SESSION['client_post'],
            'server_response' => $_SESSION['server_response'],
            'jwt_payload'     => $_SESSION['jwt_payload'],
            'jwt_header'      => $_SESSION['jwt_header'],
        ]);
        break;
    default:
        echo "no-match";
        // header("Location: /client/dashboard");
}
