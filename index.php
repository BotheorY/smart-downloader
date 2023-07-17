<?php

include_once ('core.php');

$output = ['succeded' => true];
$err = '';

try {

    $call_data = get_api_call_data();

    if (!$call_data)
        throw new Exception("Data sent is incomplete or incorrect.");

    $user_id = chk_api_token($call_data['token']);

    if (!$user_id)
        throw new Exception("Wrong token.");

    if (empty($call_data['job'])) {
/*******************************************************************************
START JOB
*******************************************************************************/










    } else {
        if (empty($call_data['job_id'])) {
/*******************************************************************************
JOB STATUS REQUEST
*******************************************************************************/









        } else {
/*******************************************************************************
INTERNAL CALL            
*******************************************************************************/







        }
    }

} catch (Exception $e) {
    if ($err)
        $err .= " \n";
    $err .= $e->getMessage();
}

if ($err) {
    $output['succeded'] = false;
    $output['err'] = $err;
}

header('Content-Type: application/json');
echo json_encode($output);

?>