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

function get_max_execution_time(): int {

    $max_execution_time = (int)ini_get('max_execution_time');

    if (!$max_execution_time)
        $max_execution_time = DFT_MAX_EXECUTION_TIME;

    return $max_execution_time;

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

//    $url = "https://www.anonymz.com/?$url";

	$defaults = array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_URL => $url,
		CURLOPT_FRESH_CONNECT => 1,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FORBID_REUSE => 1,
		CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 20,
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
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 20,
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

function normalize_sql_str($value, $db): string {

    return mysqli_real_escape_string($db, stripslashes($value));

}

function get_file_size(string $url, ?string &$ext = null): ?int {

    try {
        $headers = get_headers($url, 1);
        if ($headers !== false) {
            if (isset($headers['Content-Type'])) {
                $res = explode('/', $headers['Content-Type']);
                if (is_array($res) && (count($res) > 1) && trim($res[1]))
                    $ext = trim($res[1]);
            }
            if (isset($headers['Content-Length']))
                return (int)$headers['Content-Length'];
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
        
}

function digits_count(int $val, bool $abs = true): int {

    if ($abs)
        $val = abs($val);

    $str = strval($val);
    return strlen($str);

}

function download_file(string $url, string $file_pathname, ?string &$err = null, ?int $start_pos = null, ?int $lenght = null): ?int {

    $file = null;

    try {
        if ($start_pos !== null)
            $start_pos = abs($start_pos);
        if ($lenght !== null)
            $lenght = abs($lenght);
        if (($lenght === null) || ($lenght > 0)) {
            $context = null;

//sleep(5);

            if ($start_pos || $lenght) {
                $h = get_headers($url, 1);
                if ($h === false)
                    throw new Exception("Failed getting headers from \"$url\".");                
                $head = array_change_key_case($h);
                if (!(isset($head['accept-ranges']) && $head['accept-ranges'] == 'bytes'))
                    throw new Exception("Failed downloading from \"$url\": server does not support requests with the Range header.");                
                if (!$start_pos)
                    $start_pos = 0;
                $end_pos = '';
                if ($lenght) {
                    $end_pos = $start_pos + $lenght - 1;
                }
                $opts = [
                    'http' => [
                        'method' => 'GET',
                        'header' => "Range: bytes=$start_pos-$end_pos"
                    ]
                ];
                $context = stream_context_create($opts);
            }
            $file = fopen($url, 'rb', false, $context);
            if ($file) {
                $contents = stream_get_contents($file);
                file_put_contents($file_pathname, $contents);
                $data_size = strlen($contents);
                if ($lenght && ($data_size !== $lenght))
                    throw new Exception("Required data size $lenght but $data_size downloaded.");                
                return $data_size;
            } else {
                throw new Exception("Failed downloading from \"$url\": unable to open the file.");                
            }
        } else {
            file_put_contents($file_pathname, '');
        }
    } catch (Exception $e) {
        $err = $e->getMessage();
        return null;
    } finally {
        if ($file) {
            fclose($file);
        }
    }

    return null;

}

function delete_files(string $folder, string $pattern = '*', string &$err = ''): ?int {

    try {
        $folder = rtrim($folder, '/');
        $folder = rtrim($folder, '\\');
        if (!is_dir($folder))
            throw new Exception("Folder \"$folder\" not found.");                        
        $pattern = $folder . '/' . $pattern; 
        $count = 0;       
        foreach (glob($pattern) as $file) {
            if (is_file($file)) {
                if (!unlink($file))
                    throw new Exception("Fails deleting file \"$file\". Stop deleting files.");                        
                $count += 1;       
            }
        }    
        return $count;
    } catch (Exception $e) {
        $err = $e->getMessage();
        return null;
    }

    return null;
    
}

function append_file(string $source1, string $source2, string &$err = '', bool $del_after_join = true, int $chunk_size = 1024): bool {

    $file2 = $target_file = null;

    try {
        $file2 = fopen($source2, 'rb');
        if ($file2) {
            $target_file = fopen($source1, 'ab');
            if ($target_file) {
                while (!feof($file2)) {
                    $buffer = fread($file2, $chunk_size);
                    if ($buffer === false)
                        throw new Exception("Fails reading from file \"$source2\".");                
                    if (fwrite($target_file, $buffer) === false)
                        throw new Exception("Fails writing to file \"$source1\".");
                }
            } else {
                throw new Exception("Failed opening file \"$source1\".");                
            }
        } else {
            throw new Exception("Failed opening file \"$source2\".");                
        }
        if ($del_after_join) {
            if (!unlink($source2)) {
                throw new Exception("Failed to delete file \"$source2\".");
            }
        }
        return true;
    } catch (Exception $e) {
        $err = $e->getMessage();
        return false;
    } finally {
        if ($file2) {
            fclose($file2);
        }
        if ($target_file) {
            fclose($target_file);
        }
    }

}

function get_remote_ip(): string {

    $ip = '';

    if (!empty($_SERVER['REMOTE_ADDR']))
        $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        $ip = $_SERVER['HTTP_CLIENT_IP'];

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

    return $ip;

}

function get_mysql_datetime(?int $seconds_diff = null): string {

    $ora = new DateTime();
    
    if ($seconds_diff) {
        if ($seconds_diff < 0) {
            $seconds_diff = (int)abs($seconds_diff);
            $ora->modify("-{$seconds_diff} seconds");
        } else {
            $ora->modify("+{$seconds_diff} seconds");
        }
    }
    
    return $ora->format('Y-m-d H:i:s');
            
}
