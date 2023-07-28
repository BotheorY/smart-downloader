<?php

include_once ('core.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

date_default_timezone_set('Europe/Rome');

$output = ['succeeded' => true];
$err = '';
$do_echo = true;
$call_data = null;
$internal_call = false;

try {

    $id_bt_job = null;
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

db_log("[index] 100 START JOB");  // debug

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

db_log("[index] 200 STATUS REQUEST");  // debug










        } else {
/*******************************************************************************
INTERNAL CALL            
*******************************************************************************/

db_log("[index] 300 INTERNAL");  // debug

            $internal_call = true;
            $do_echo = false;

            if (empty($call_data['cmd']))
                throw new Exception("Missing cmd in internal API call data.");

            $cmd =  trim(strtolower($call_data['cmd']));

db_log("[index] 400 cmd = $cmd; " . print_r($call_data, true));  // debug


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
//    $output['client IP'] = get_remote_ip();
    header('Content-Type: application/json');
    echo json_encode($output);
}

?>