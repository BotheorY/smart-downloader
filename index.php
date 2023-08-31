<?php

include_once ('core.php');

error_reporting(E_ALL & ~E_WARNING);
//error_reporting(E_ALL);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

date_default_timezone_set('Europe/Rome');

$id_bt_job = null;
$output = ['succeeded' => true];
$err = '';
$do_echo = true;
$call_data = null;
$internal_call = false;

try {

    if ($conn === null) {
        $db_name = get_env_var('BT_API_DOWN_DB_NAME');
        $db_password = get_env_var('BT_API_DOWN_DB_PWD');
        $db_host = get_env_var('BT_API_DOWN_DB_HOST');
        $db_user = get_env_var('BT_API_DOWN_DB_USER');
        $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Connection to database failed: " . $conn->connect_error);
        }    
    }
        
    $call_data = get_api_call_data();

    if (!$call_data)
        throw new Exception("Data sent is incomplete or incorrect.");

    $user_id = chk_api_token($call_data['token']);

    if (!$user_id) {
        if (empty($call_data['job_id'])) {
            if (!empty($call_data['key'])) {
                $key = $call_data['key'];
                $job_data = get_job_data(null, $key);
                if ($job_data)
                    $id_bt_job = $job_data['id_bt_job'];
            }
        } else {
            $id_bt_job = $call_data['job_id'];
        }        
        throw new Exception("Wrong token.");
    }

    if (empty($call_data['key'])) {
/*******************************************************************************
START JOB
*******************************************************************************/
        $ext = '';
        $file_size = get_file_size($call_data['url'], $ext);
        if ($ext && empty($call_data['ext']))
            $call_data['ext'] = $ext;
        if (empty($call_data['file_size']))
            $call_data['file_size'] = $file_size;
        $output['file_size'] = $call_data['file_size'];
        if ($output['file_size'] && ($output['file_size'] > (DFT_CHUNK_SIZE * MAX_JOB_DOWNLOADS)))
            throw new Exception("File size exceeded limit of " . DFT_CHUNK_SIZE * MAX_JOB_DOWNLOADS . ' bytes.');
        $id_bt_job = start_job($user_id, $call_data);
        if (empty($id_bt_job) || empty($call_data['job']['job_id']))
            throw new Exception("Failed creating job.");
        $output['key'] = $call_data['job']['job_id'];
    } else {
        if (empty($call_data['job_id'])) {
/*******************************************************************************
JOB STATUS REQUEST
*******************************************************************************/
            $job_data = get_job_data(null, $call_data['key']);
            if (!$job_data)
                throw new Exception("Job not found.");
            $ext = '';
            $file_size = get_file_size($job_data['file_url'], $ext);
            if (!empty($job_data['file_ext']))
                $ext = $job_data['file_ext'];
            $output['ext'] = $ext;
            $output['file_size'] = $file_size;
            $output['key'] = $call_data['key'];
            $output['url'] = $job_data['file_url'];
            $output['file_name'] = $job_data['file_name'];
            $status = $job_data['job_status'];
            $output['status'] = $status;
            if ($status === 'DOWNLOADING') {
                $output['downloaded'] = get_downloaded_size($job_data['id_bt_job']);
            } else {
                $output['downloaded'] = $job_data['downloaded_size'];
            }
            $download_url = '';
            if ($status === 'COMPLETED') {
                $download_url = get_download_fld_url() . "/{$job_data['job_id']}";
                if ($job_data['file_ext'])
                    $download_url .= '.' . $job_data['file_ext'];
            }
            $output['download_url'] = $download_url;
            $output['err'] = $job_data['last_err'];
        } else {
/*******************************************************************************
INTERNAL CALL            
*******************************************************************************/
            $internal_call = true;
            $do_echo = false;

            if (empty($call_data['cmd']))
                throw new Exception("Missing cmd in internal API call data.");

            $cmd =  trim(strtolower($call_data['cmd']));

            switch ($cmd) {
                case 'add_download':
                    if (!create_download($call_data))
                        throw new Exception("Failed creating download #{$call_data['index']}.");
                    break;  
                case 'join':
                    do_parts_join($call_data);              
                    break;  
                case 'remove_expired':
                    do_remove_expired_jobs();              
                    break;  
                default:
                    throw new Exception("Parameter cmd non recognized.");
                    break;
            }

        }
    }

} catch (Exception $e) {
    if ($err)
        $err .= " \n";
    $err .= $e->getMessage();
} finally {
    if ($call_data && $internal_call)
        start_remove_expired_jobs($call_data);
}

if ($err) {
    if ($id_bt_job)
        update_job($id_bt_job, ['job_status' => 'FAILED', 'last_err' => $err]);
    $output['succeeded'] = false;
    $output['err'] = $err;
}

if ($do_echo) {
    header('Content-Type: application/json');
    echo json_encode($output);
}

?>