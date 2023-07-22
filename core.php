<?php

include_once ('commons.php');

function update_job_last_err(int $id_bt_job, string $err) {

    $db = db_down_connect();

    if ($db) {
        try {
            $err = normalize_sql_str($err, $db);
            $sql = "UPDATE bt_job SET last_err = '$err' WHERE id_bt_job = $id_bt_job";
            if ($db->query($sql) === false)
                throw new Exception("Failed updating job err");
        } finally {
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }
    
}
    
function create_job(int $id_user, array $data): ?int {

    $db = db_down_connect();

    if ($db) {
        try {
            $file_url = null;
            if (!empty($data['url'])) {
                $res = trim($data['url']);
                if (filter_var($res, FILTER_VALIDATE_URL)) {
                    $file_url = '"' . normalize_sql_str($res, $db) . '"';
                }
            }
            if (!$file_url)
                throw new Exception("Missing file URL");
            $path = parse_url($res, PHP_URL_PATH);
            $file_info = pathinfo($path);
            $file_name = '"' . normalize_sql_str($file_info['basename'], $db) . '"';
            $file_ext = '"' . normalize_sql_str($file_info['extension'], $db) . '"';
            $job_id = '"' . get_uuid() . '"';
            $callback_url = 'NULL';
            $callback_extra_data = 'NULL';
            $callback_type = 'NULL';
            if (!empty($data['callback'])) {
                $callback_url = trim($data['callback']);
                if (filter_var($callback_url, FILTER_VALIDATE_URL)) {
                    $callback_url = '"' . normalize_sql_str($callback_url, $db) . '"';
                    if (!empty($data['callbackData'])) {
                        $callback_extra_data = '"' . normalize_sql_str(trim($data['callbackData']), $db) . '"';
                    }
                    $callback_type = "'POST'";
                    if (!empty($data['callbackType'])) {
                        $res = strtoupper(trim($data['callbackType']));
                        if ($res === 'GET')
                            $callback_type = "'GET'";
                    }
                }
            }
            $sql =  "
                        INSERT INTO bt_job (
                            id_user,
                            job_id,
                            callback_extra_data,
                            file_url,
                            callback_url,
                            callback_type,
                            file_name,
                            file_ext                            
                        ) VALUES (
                            $id_user,
                            $job_id,
                            $callback_extra_data,
                            $file_url,
                            $callback_url,
                            $callback_type,
                            $file_name,
                            $file_ext                            
                        )
                    ";
            if ($db->query($sql) === false)
                throw new Exception("Failed creating job record");
            return (int)$db->insert_id;
        } finally {
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return null;

}

function start_job(int $id_user, array &$data): ?int {

    if (empty($data['file_size']))
        $data['file_size'] = get_file_size($data['url']);

    $id_bt_job = create_job($id_user, $data);

    if ($id_bt_job) {
        $job_data = get_job_data($id_bt_job);
        if (!empty($job_data)) {
            $data['job'] = $job_data;
            if (!start_download(1, $data)) {
                $id_bt_job = null;
                throw new Exception("Failed starting download #1");
            }
        }
    }

    return $id_bt_job;

}

function start_download(int $index, array $data): bool {

    $res = false;
    $internal_token = get_internal_token($data['token']);
    $data['internal_token'] = $internal_token;
    $data['index'] = $index;
    $data['key'] = $data['job']['job_id'];
    $data['job_id'] = $data['job']['id_bt_job'];
    $data['cmd'] = 'add_download';
    unset($data['url']);
    $err = '';    

//ab_log("key = {$data['key']}; job_id = {$data['job_id']}"); // debug

    $res = curl_post(get_curr_url(), $data, [CURLOPT_RETURNTRANSFER => 0, CURLOPT_TIMEOUT => 5], $err);

/*   
    if (!$res)
        throw new Exception("Failed posting data to start download #{$index}: $res");

    return $res;
*/

    return true;

}

function create_download(array $data): bool { 

    $id_download = null;
    $index = (int)$data['index'];

    if (!chk_internal_token($data['token'], $data['internal_token']))
        throw new Exception("Wrong internal token sent to download #{$index}.");

    $id_bt_job = (int)$data['job']['id_bt_job'];

    if (!get_job_data($id_bt_job))
        throw new Exception("Job not found for download #{$index}.");

    try {
        $db = db_down_connect();
        if ($db) {
            try {
                $sql =  "
                            INSERT INTO bt_job_downloads (
                                id_bt_job,
                                part_index                            
                            ) VALUES (
                                $id_bt_job,
                                $index                            
                            )
                        ";
                if ($db->query($sql) === false)
                    throw new Exception("Failed creating job record");
                $id_download = (int)$db->insert_id;
                $next_index = $index + 1;
            /* [START] CALC $max_downloads  */
                $max_downloads = MAX_JOB_DOWNLOADS;
                if (!empty($data['max_threads']))
                    $max_downloads = (int)$data['max_threads'];
                if ($max_downloads > MAX_JOB_DOWNLOADS)
                    $max_downloads = MAX_JOB_DOWNLOADS;
                if (!empty($data['file_size']))
                    $file_size = (int)$data['file_size'];
                if ($file_size) {
                    if ($file_size <= DFT_CHUNK_SIZE) {
                        $max_downloads = 1;
                    } else {
                        $tot_downloads = intdiv($file_size, DFT_CHUNK_SIZE);
                        $module = $file_size % DFT_CHUNK_SIZE;
                        if ($module !== 0)
                            $tot_downloads += 1;    
                        if ($tot_downloads < $max_downloads)
                            $max_downloads = $tot_downloads;
                    }
                }
            /* [END] CALC $max_downloads  */
                if ($next_index <= $max_downloads) {
                    start_download($next_index, $data);
                } else {
                    update_download($id_download, ['last_one' => 1]);
                }
                do_download($id_download, $data);
            } finally {
                $db->close();
            }
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    } catch (Exception $e) {
        if ($id_download) {
            $err = $e->getMessage();
            update_download($id_download, ['last_err' => $err, 'download_status' => 'FAILED']);
            update_job($id_bt_job, ['last_err' => $err]);
        }
    }

    return true;

}

function do_download(int $id_download, array $data) {

    $id_bt_job = (int)$data['job']['id_bt_job'];

    try {







    } catch (Exception $e) {
        $err = $e->getMessage();
        update_download($id_download, ['last_err' => $err, 'download_status' => 'FAILED']);
        update_job($id_bt_job, ['last_err' => $err]);
    }

}
