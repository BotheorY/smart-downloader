<?php

include_once ('constants.php');
include_once ('utilities.php');

$output = ['succeded' => true];
$err = '';

try {

    $call_data = get_api_call_data();

    if (!$call_data)
        throw new Exception("Data sent is incomplete or incorrect.");

    $user_id = chk_api_token($call_data['token']);

    if (!$user_id)
        throw new Exception("Wrong token.");

if (isset($call_data['ok'])) {
    $output['input'] = $call_data['ok'];    // debug
} else {
    $data = ['token' => $call_data['token'], 'ok' => get_curr_url()];
    $res = curl_post(get_curr_url(), $data);
    $output['back'] = $res;
    $output['url'] = json_decode($res, true)['input'];
    $dft = [];
    $dft[17] = 'sfiga';
    $dft[90] = 'paura';
    $new = [];
    $new[18] = 'digiottoooooooooo';
    $new[90] = 'coraggio';
    $new[100] = 'cento';
    $output['array'] = $new + $dft;
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