<?php

include_once ('core.php');

$output = ['succeeded' => true];
$err = '';

try {

    $id_bt_job = null;
    $call_data = get_api_call_data();

//ab_log(print_r($call_data, true));


    if (!$call_data)
        throw new Exception("Data sent is incomplete or incorrect.");

    $user_id = chk_api_token($call_data['token']);

    if (!$user_id)
        throw new Exception("Wrong token.");

    if (empty($call_data['key'])) {
/*******************************************************************************
START JOB
*******************************************************************************/
        $id_bt_job = start_job($user_id, $call_data);
        if (empty($id_bt_job) || empty($call_data['job']['job_id']))
            throw new Exception("Failed creating job.");
        $output['key'] = $call_data['job']['job_id'];
        $output['file_size'] = $call_data['file_size'];
    } else {
        if (empty($call_data['job_id'])) {
/*******************************************************************************
JOB STATUS REQUEST
*******************************************************************************/









        } else {
/*******************************************************************************
INTERNAL CALL            
*******************************************************************************/
            if (empty($call_data['cmd']))
                throw new Exception("Missing cmd in internal API call data.");

            $cmd =  trim(strtolower($call_data['cmd']));

            switch ($cmd) {
                case 'add_download':
                    if (!create_download($call_data))
                        throw new Exception("Failed creating download #{$call_data['index']}.");
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
}

if ($err) {
    if ($id_bt_job)
        update_job_last_err($id_bt_job, $err);
    $output['succeeded'] = false;
    $output['err'] = $err;
}

header('Content-Type: application/json');
echo json_encode($output);

?>