<?php

include_once ('constants.php');


function ab_log($log) {
	
	$now = date('[d/m/Y H:i:s] ');
	$log = $now . $log;
	$log .= "\n\n";
	
	file_put_contents('ab_log.txt', $log, FILE_APPEND);
	
}

function get_uuid() {
    if (function_exists('com_create_guid')) {
        $result = com_create_guid();
    } else {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
        $result = $uuid;
    }
    $result = str_replace('{', '', $result);
    $result = str_replace('}', '', $result);
    $result = str_replace('-', '', $result);
    return $result;
}

function get_token($key, $token, $time_stamp = null) {

	if (!$time_stamp) {
		$time_stamp = time();
	}

	if ($time_stamp % 2 == 0) {
		$token = $token . $time_stamp . $token;
	} else {
		$token = $time_stamp . $token;
	}

	$token = hash("sha256", $token);
	return $time_stamp . '_' . $key . '_' . $token;

}

function get_env_var($var_name, $raise_err = true) {

	$result = getenv($var_name);

	if ($result === false) {
		$result = ini_get($var_name);		
		if ($result === false) {
			if (file_exists('env-vars.php')) {
				require_once('env-vars.php');
				if (function_exists('get_var'))
					$result = get_var($var_name);		
			}
		}
	}

	if ($raise_err && (!$result))
		throw new Exception("Enviroment variable \"$var_name\" not found.");

	return $result;

}

function db_down_connect() {

	$db_name = get_env_var('BT_API_DOWN_DB_NAME');
	$db_password = get_env_var('BT_API_DOWN_DB_PWD');
	$db_host = get_env_var('BT_API_DOWN_DB_HOST');
	$db_user = get_env_var('BT_API_DOWN_DB_USER');

    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);

    if ($conn->connect_error) {
		throw new Exception("Connection to database failed: " . $conn->connect_error);
    } else {
        return $conn;
    }

    return null;
    
}

function db_admin_connect() {

	$db_name = get_env_var('BT_API_DB_NAME');
	$db_password = get_env_var('BT_API_DB_PWD');
	$db_host = get_env_var('BT_API_DB_HOST');
	$db_user = get_env_var('BT_API_DB_USER');

    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);

    if ($conn->connect_error) {
		throw new Exception("Connection to database failed: " . $conn->connect_error);
    } else {
        return $conn;
    }

    return null;
    
}

function get_max_execution_time() {

    $maxExecutionTime = ini_get('max_execution_time');

    if ($maxExecutionTime !== false)
        $maxExecutionTime = (int)$maxExecutionTime;
    
    return $maxExecutionTime;
  
}

function get_api_call_data() {
    
    $data = file_get_contents('php://input');

    if ($data) {
        $data = json_decode($data, true);
        if ((!empty($data)) && isset($data['token']))
            return $data;
        $data = null;
    }
    
    if (!$data) {
        if ((!empty($_POST)) && isset($_POST['token']))
            return $_POST;
        return null;
    }

    return null;

}

function chk_api_token($api_token) {
    
    $result = false;
    $curr_time_stamp = time();
    $token_parts = explode('_', $api_token);
    $time_stamp = intval($token_parts[0]);
    
    if (($time_stamp >= ($curr_time_stamp - API_TOKEN_TIMESTAMP_SPAN)) && ($time_stamp <= ($curr_time_stamp + API_TOKEN_TIMESTAMP_SPAN))) {
        $user_key = $token_parts[1];
        $sql = "SELECT id_sys_user, api_token FROM sys_user WHERE user_key = '$user_key'";
        $db = db_admin_connect();
        if ($db) {
            try {
                $user_token = null;
                $queryres = $db->query($sql);
                if ($queryres->num_rows > 0) {
                    while($row = $queryres->fetch_assoc()) {
                        $result = (int)$row["id_sys_user"];
                        $user_token = $row["api_token"];
                    }
                    $test_token = get_token($user_key, $user_token, $time_stamp);
                    if ($test_token !== $api_token)
                        $result = false;
                }
            } finally {
                $db->close();
            }
        } else {
            throw new Exception("API DB connection failed");
        }
    } else {
		throw new Exception("Token timestamp is wrong or expired");
    }

    return $result;

}

function curl_post($url, $data = NULL, $options = array(), &$err = '') {
	
	$defaults = array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_URL => $url,
		CURLOPT_FRESH_CONNECT => 1,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FORBID_REUSE => 1,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_POSTFIELDS => http_build_query($data)
	);

    $ch = curl_init();
	curl_setopt_array($ch, ($options + $defaults));
	
    if( ! $result = curl_exec($ch)) {
		$err .= curl_error($ch);
		$result = null;
    }
	
    curl_close($ch);
    return $result;
	
} 

function curl_get($url, $data = NULL, array $options = array(), &$err = '') {

    if ((!empty($data)) && is_array($data)) {
        $url .= '?' . http_build_query($data);
    }
        
	$defaults = array(
		CURLOPT_HEADER => 0,
		CURLOPT_URL => $url,
		CURLOPT_FRESH_CONNECT => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FORBID_REUSE => 1,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'
	);

    $ch = curl_init();
	curl_setopt_array($ch, ($options + $defaults));
	
    if( ! $result = curl_exec($ch)) {
		$err .= curl_error($ch);
		$result = null;
    }
	
    curl_close($ch);
    return $result;
	
} 

function get_curr_url() {

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    return $protocol . $domainName . $scriptPath;

}
