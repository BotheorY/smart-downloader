<?php

include_once ('utilities.php');

$conn = null;

function get_api_call_data() {
    
    $data = file_get_contents('php://input');

    if ($data) {
        $data = json_decode($data, true);
        if ((empty($data)) || (!isset($data['token'])))
            $data = null;
    }
    
    if ((!$data) && (!empty($_POST)) && isset($_POST['token']))
        $data = $_POST;
    
    if ($data && !((isset($data['key']) || isset($data['url']))))
        $data = null;

    if ($data && isset($data['url'])) {
        if (isset($data['key']))
            unset($data['key']);
    }

    if ((!$data) && (!empty($_GET)) && isset($_GET['token'])) {
        $data = $_GET;
        if (isset($data['job_id']))
            unset($data['job_id']);
    }    

    return $data;

}

function update_download(int $id, array $data) { 

    global $conn;

    if ($id < 0)
        throw new Exception("Wrong download ID to update.");

    if (empty($data))
        throw new Exception("Missing data updating download.");

    $sql_datetime = date("Y-m-d H:i:s");

    if (array_key_exists('download_status', $data))
        $data['state_change_datetime'] = $sql_datetime;

    if (array_key_exists('downloaded_size', $data))
        $data['last_progress_datetime'] = $sql_datetime;

    if ($conn) {
		$upt_flds = '';
		foreach ($data as $key => $value) {
			if (is_string($value))
				$value = "'" . normalize_sql_str($value, $conn) . "'";
			if ($upt_flds)
				$upt_flds .= ', ';
			$upt_flds .= "$key = $value";
		}
		$sql =  "UPDATE bt_job_downloads SET $upt_flds WHERE id_bt_job_downloads = $id";
		return $conn->query($sql);
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function update_job(int $id, array $data) { 

    global $conn;
    
    if ($id <= 0)
        throw new Exception("Wrong job ID to update.");

    if (empty($data))
        throw new Exception("Missing data updating job.");

    $sql_datetime = date("Y-m-d H:i:s");

    if (array_key_exists('job_status', $data)) {
        $data['state_change_datetime'] = $sql_datetime;
        switch ($data['job_status']) {
            case 'FAILED':
                $job_data = get_job_data($id);
                if ($job_data) {
                    $cbck_data =     [
                        'msg' => "Download failed at " . get_mysql_datetime(), 
                        'key' => $job_data['job_id'], 
                        'url' => $job_data['file_url'], 
                        'creation_datetime' => $job_data['creation_datetime'], 
                        'err' => $job_data['last_err'], 
                        'ext' => $job_data['file_ext'], 
                        'file_name' => $job_data['file_name']
                    ];
                    send_callback($id, $cbck_data);
                }            
                break;
            case 'COMPLETED':
                $job_data = get_job_data($id);
                if ($job_data) {
                    $download_url = get_download_fld_url() . "/{$job_data['job_id']}";
                    if ($job_data['file_ext'])
                        $download_url .= '.' . $job_data['file_ext'];
                    $cbck_data =     [
                        'msg' => "Download completed at " . get_mysql_datetime(), 
                        'key' => $job_data['job_id'], 
                        'url' => $job_data['file_url'], 
                        'download_url' => $download_url, 
                        'creation_datetime' => $job_data['creation_datetime'], 
                        'err' => $job_data['last_err'], 
                        'ext' => $job_data['file_ext'], 
                        'file_name' => $job_data['file_name']
                    ];
                    send_callback($id, $cbck_data);
                }            
                break;
        }
    }
    
    if ($conn) {
		$upt_flds = '';
		foreach ($data as $key => $value) {
			if (is_string($value))
				$value = "'" . normalize_sql_str($value, $conn) . "'";
			if ($upt_flds)
				$upt_flds .= ', ';
			$upt_flds .= "$key = $value";
		}
		$sql =  "UPDATE bt_job SET $upt_flds WHERE id_bt_job = $id";
		return $conn->query($sql);
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function get_job_data(?int $id_bt_job = null, ?string $job_id = null): ?array {

    global $conn;

    if ($job_id)
        $job_id = trim($job_id);

    if (($id_bt_job && ($id_bt_job > 0)) || $job_id ) {
        if ($conn) {
			$sql = "SELECT * FROM bt_job WHERE TRUE";
			if ($id_bt_job)
				$sql .= " AND (id_bt_job = $id_bt_job) ";
			if ($job_id)
				$sql .= " AND (job_id = '$job_id') ";
			$queryres = $conn->query($sql);
			if ($queryres->num_rows > 0) {
				$row = $queryres->fetch_assoc();
				if (empty($row)) {
					return null;
				} else {
					return $row;
				}
			}
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    }

    return null;
    
}

function get_download_data(int $id): ?array {

    global $conn;

    if ($id <= 0)
        throw new Exception("Wrong download ID.");

    if ($conn) {
		$sql = "SELECT * FROM bt_job_downloads WHERE id_bt_job_downloads = $id";
		$queryres = $conn->query($sql);
		if ($queryres->num_rows > 0) {
			$row = $queryres->fetch_assoc();
			if (empty($row)) {
				return null;
			} else {
				return $row;
			}
		}
    } else {
        throw new Exception("Downloads DB connection failed");
    }    

    return null;
    
}

function chk_internal_token($api_token, $internal_token) {
    
    return get_internal_token($api_token) === $internal_token;

}

function get_internal_token($token) {

	$db_password = get_env_var('BT_API_DOWN_DB_PWD');
    $token .= $db_password;
    return hash("sha256", $token);

}

function get_part_file_name(int $id_download, bool $include_fld = true): string {

    $fld = '';

    if ($include_fld) {
        if (!is_dir(FILES_FOLDER_NAME)) {
            mkdir(FILES_FOLDER_NAME, 0755, true);
        }        
        $fld = FILES_FOLDER_NAME . '/';
    }

    $download_data = get_download_data($id_download);
    $job_data = get_job_data($download_data['id_bt_job']);
    return "$fld{$job_data['job_id']}." . str_pad($download_data['part_index'], digits_count(MAX_JOB_DOWNLOADS), "0", STR_PAD_LEFT);

}

function delete_job(int $id): bool { 

    global $conn;

    if ($id <= 0)
        throw new Exception("Wrong job ID to delete.");

    $job_data = get_job_data($id);

    if (empty($job_data))
        throw new Exception("Failed reading data from job having ID $id.");

    $key = $job_data['job_id'];

    if ($conn) {
        try {
            $conn->autocommit(false);
            $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
            $conn->autocommit(false);
            if ($conn->query("DELETE FROM bt_job_downloads WHERE id_bt_job = $id")) {
                if ($conn->query("DELETE FROM bt_job WHERE id_bt_job = $id")) {
                    delete_files(FILES_FOLDER_NAME, "{$key}*");         
                    $conn->commit();   
                    return true;
                }
            }
            $conn->rollback();
        } finally {
            $conn->autocommit(true);
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function delete_download(int $id) { 

    global $conn;

    if ($id <= 0)
        throw new Exception("Wrong job ID to delete.");

    if ($conn) {
		$sql =  "DELETE FROM bt_job_downloads WHERE id_bt_job_downloads = $id";
		return $conn->query($sql);
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function chk_job_download_complete(int $id_job): bool {

    global $conn;

    try {
        if ($id_job <= 0)
            throw new Exception("Wrong job ID.");
        if ($conn) {
			$sql = "SELECT * FROM bt_job_downloads WHERE (id_bt_job = $id_job) AND (download_status <> 'COMPLETED')";
			$queryres = $conn->query($sql);
			if ($queryres->num_rows === 0) {
				update_job($id_job, ['job_status' => 'DOWNLOADED', 'downloaded_datetime' => date("Y-m-d H:i:s")]);
				$sql = "SELECT SUM(downloaded_size) AS totsize FROM bt_job_downloads WHERE (id_bt_job = $id_job) AND (download_status = 'COMPLETED')";
				$queryres = $conn->query($sql);
				if ($queryres->num_rows === 1) {
					$row = $queryres->fetch_assoc();
					if (!empty($row)) {
						$totsize = (int)$row['totsize'];
						update_job($id_job, ['downloaded_size' => $totsize]);
					}
				}
				return true;
			}
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    } catch (Exception $e) {
        if ($id_job) {
            update_job($id_job, ['last_err' => $e->getMessage()]);
        }
    }

    return false;

}

function send_callback(int $id_job, ?array $data = null) {

    $job_data = get_job_data($id_job);

    if ($job_data) {
        $url = $job_data['callback_url'];
        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            $callback_type = $job_data['callback_type'];
            if ($callback_type === 'PUT')   // PUT method not yet supported
                return false;
            if (!$callback_type)
                $callback_type = 'POST';
            if (!$data)
                $data = [];
            $extra_data = $job_data['callback_extra_data'];
            if ($extra_data) {
                $extra_data = json_decode($extra_data, true);
                if (empty($extra_data))
                    $extra_data = [];
            } else {
                $extra_data = [];
            }
            $data = $extra_data + $data;
            if ($callback_type === 'GET')
                curl_get($url, $data, [[CURLOPT_RETURNTRANSFER => 0, CURLOPT_TIMEOUT => 3]]);
            if ($callback_type === 'POST')
                curl_post($url, $data, [[CURLOPT_RETURNTRANSFER => 0, CURLOPT_TIMEOUT => 3]]);
        }
    }

}

function get_download_fld_url(): string {

    return dirname(get_curr_url()) . '/' . FILES_FOLDER_NAME;

}

function get_downloaded_size(int $id_job): int {

    global $conn;

    try {
        if ($id_job <= 0)
            throw new Exception("Wrong job ID.");
        if ($conn) {
            $sql = "SELECT SUM(downloaded_size) AS totsize FROM bt_job_downloads WHERE (id_bt_job = $id_job) AND (download_status = 'COMPLETED')";
            $queryres = $conn->query($sql);
            if ($queryres->num_rows === 1) {
                $row = $queryres->fetch_assoc();
                if (!empty($row)) {
                    return (int)$row['totsize'];
                }
            }
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    } catch (Exception $e) {
        if ($id_job) {
            update_job($id_job, ['last_err' => $e->getMessage()]);
        }
        return 0;
    }

    return 0;
    
}
