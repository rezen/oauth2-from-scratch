<?php


date_default_timezone_set("UTC");
session_start();

require '../lib/shared.php';

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext  = pathinfo($path, PATHINFO_EXTENSION);



switch ($path) {
    case '/client/start':
        if (!empty($_SESSION['access_token'])) {
            header("Location: /client/dashboard");
            exit;
        }

        $iat =  time();
        $key   = "id1";

        $_SESSION['state']         = md5(random_bytes(24));
        $_SESSION['code_verifier'] = base64UrlEncode(random_bytes(24));

        $query = http_build_query([
            'client_id'      => $clients[$key]['id'],
            'response_type'  => 'code', // code|token|id_token token
            'response_mode'  => 'query', // query,fragment
            'state'          => $_SESSION['state'],
            'scope'          => 'photos',
            'redirect_uri'   => $clients[$key]['redirect'],
            'code_challenge' => hash("sha256", base64UrlEncode($_SESSION['code_verifier'])),
            'code_challenge_method' => 'S256',
        ]);
        
        view("login", [
            'title' => 'Start',
            'query' => $query,
        ]);
        break;
    case '/client/cb':
        if (empty($_GET['state'])) {
            return view("error", [
                'error' => "Missing state",
            ]);
        }


        if (!hash_equals($_SESSION['state'], $_GET['state'])) {
            echo "State does not match";
            return;
        }
        $key = "id1";
        // 
        $data = [
            'grant_type'    =>'authorization_code',
            'client_id'     => $clients[$key]['id'],
            'client_secret' => $clients[$key]['secret'],
            'redirect_uri'  => 'TODO',
            'code'          => $_GET['code'],
            "code_verifier" => $_SESSION['code_verifier'],
        ];

        $data_string = http_build_query($data);
        $ch = curl_init();

        set_time_limit (60);
        sleep(11);
        $server_ip = gethostbyname('server');
        curl_setopt($ch,CURLOPT_URL, "http://$server_ip:4444/server/oauth/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // ...
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch);
            break;
        }

        // Then, after your curl_exec call:
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        echo $body;

        $data = json_decode($body);
        $_SESSION['access_token'] = $data->access_token;
        $_SESSION['id_data']      = parseJwt($data->id_token);
        // header("Location: /client/dashboard");
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
            header("Location: /client/start");
            exit;
        }
        view('dashboard', [
            'title'  => 'dashboard',
            'id_data' => $_SESSION['id_data'],
        ]);
        break;
    default:
        header("Location: /client/dashboard");
}
