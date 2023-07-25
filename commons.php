<?php

include_once ('utilities.php');

function get_api_call_data() {
    
    $data = file_get_contents('php://input');

    if ($data) {
        $data = json_decode($data, true);
        if ((empty($data)) || (!isset($data['token'])))
            $data = null;
    }
    
    if ((!$data) && (!empty($_POST)) && isset($_POST['token']))
        $data = $_POST;
    
    if ($data && !((isset($data['job_id']) || isset($data['url']))))
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


db_log("[get_api_call_data] 100 " . print_r($data, true));  // debug

    return $data;

}

function update_download(int $id, array $data) { 

    if ($id < 0)
        throw new Exception("Wrong download ID to update.");

    if (empty($data))
        throw new Exception("Missing data updating download.");

    $sql_datetime = date("Y-m-d H:i:s");

    if (array_key_exists('download_status', $data))
        $data['state_change_datetime'] = $sql_datetime;

    if (array_key_exists('downloaded_size', $data))
        $data['last_progress_datetime'] = $sql_datetime;

    $db = db_down_connect();

    if ($db) {
        try {
            $upt_flds = '';
            foreach ($data as $key => $value) {
                if (is_string($value))
                    $value = "'" . normalize_sql_str($value, $db) . "'";
                if ($upt_flds)
                    $upt_flds .= ', ';
                $upt_flds .= "$key = $value";
            }
            $sql =  "UPDATE bt_job_downloads SET $upt_flds WHERE id_bt_job_downloads = $id";

db_log("[update_download] 100 sql = $sql");  // debug

            return $db->query($sql);
        } finally {
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function update_job(int $id, array $data) { 

    if ($id <= 0)
        throw new Exception("Wrong job ID to update.");

    if (empty($data))
        throw new Exception("Missing data updating job.");

    $sql_datetime = date("Y-m-d H:i:s");

    if (array_key_exists('job_status', $data))
        $data['state_change_datetime'] = $sql_datetime;
    
    $db = db_down_connect();

    if ($db) {
        try {
            $upt_flds = '';
            foreach ($data as $key => $value) {
                if (is_string($value))
                    $value = "'" . normalize_sql_str($value, $db) . "'";
                if ($upt_flds)
                    $upt_flds .= ', ';
                $upt_flds .= "$key = $value";
            }
            $sql =  "UPDATE bt_job SET $upt_flds WHERE id_bt_job = $id";

db_log("[update_job] 100 sql = $sql");  // debug

            return $db->query($sql);
        } finally {
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function get_job_data(?int $id_bt_job = null, ?string $job_id = null): ?array {

    if ($job_id)
        $job_id = trim($job_id);

    if (($id_bt_job && ($id_bt_job > 0)) || $job_id ) {
        $db = db_down_connect();
        if ($db) {
            try {
                $sql = "SELECT * FROM bt_job WHERE TRUE";
                if ($id_bt_job)
                    $sql .= " AND (id_bt_job = $id_bt_job) ";
                if ($job_id)
                    $sql .= " AND (job_id = '$job_id') ";
                $queryres = $db->query($sql);
                if ($queryres->num_rows > 0) {
                    $row = $queryres->fetch_assoc();
                    if (empty($row)) {
                        return null;
                    } else {
                        return $row;
                    }
                }
            } finally {
                $db->close();
            }
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    }

    return null;
    
}

function get_download_data(int $id): ?array {

    if ($id <= 0)
        throw new Exception("Wrong download ID.");

    $db = db_down_connect();

    if ($db) {
        try {
            $sql = "SELECT * FROM bt_job_downloads WHERE id_bt_job_downloads = $id";
            $queryres = $db->query($sql);
            if ($queryres->num_rows > 0) {
                $row = $queryres->fetch_assoc();
                if (empty($row)) {
                    return null;
                } else {
                    return $row;
                }
            }
        } finally {
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }    

    return null;
    
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

    if ($id <= 0)
        throw new Exception("Wrong job ID to delete.");

    $job_data = get_job_data($id);

    if (empty($job_data))
        throw new Exception("Failed reading data from job having ID $id.");

    $key = $job_data['job_id'];
    $db = db_down_connect();

    if ($db) {
        try {
            $db->autocommit(false);
            $db->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
            $db->autocommit(false);
            if ($db->query("DELETE FROM bt_job_downloads WHERE id_bt_job = $id")) {
                if ($db->query("DELETE FROM bt_job WHERE id_bt_job = $id")) {
                    $db->commit();   
                    delete_files(FILES_FOLDER_NAME, "{$key}*");         
                    return true;
                }
            }
            $db->rollback();
        } finally {
            $db->autocommit(true);
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function delete_download(int $id) { 

    if ($id <= 0)
        throw new Exception("Wrong job ID to delete.");

    $db = db_down_connect();

    if ($db) {
        try {
            $sql =  "DELETE FROM bt_job_downloads WHERE id_bt_job_downloads = $id";
            return $db->query($sql);
        } finally {
            $db->close();
        }
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return false;

}

function chk_job_download_complete(int $id_job): bool {

    try {
        if ($id_job <= 0)
            throw new Exception("Wrong job ID.");
        $db = db_down_connect();
        if ($db) {
            try {
                $sql = "SELECT * FROM bt_job_downloads WHERE (id_bt_job = $id_job) AND (download_status <> 'COMPLETED')";
                $queryres = $db->query($sql);
                if ($queryres->num_rows === 0) {
                    update_job($id_job, ['job_status' => 'DOWNLOADED', 'downloaded_datetime' => date("Y-m-d H:i:s")]);
                    $sql = "SELECT SUM(downloaded_size) AS totsize FROM bt_job_downloads WHERE (id_bt_job = $id_job) AND (download_status = 'COMPLETED')";
                    $queryres = $db->query($sql);
                    if ($queryres->num_rows === 1) {
                        $row = $queryres->fetch_assoc();
                        if (!empty($row)) {
                            $totsize = (int)$row['totsize'];
                            update_job($id_job, ['downloaded_size' => $totsize]);
                        }
                    }
                    return true;
                }
            } finally {
                $db->close();
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

function db_log(string $msg, ?int $id_bt_job_downloads = null, ?int $id_bt_job = null) {


return false;    

    if (!$id_bt_job)
        $id_bt_job = 'NULL';

    if (!$id_bt_job_downloads)
        $id_bt_job_downloads = 'NULL';

    $db = db_down_connect();

    if ($db) {
        $msg = "'" . normalize_sql_str($msg, $db) . "'";
        try {
            $sql =  "
                        INSERT INTO bt_log (
                            id_bt_job,
                            id_bt_job_downloads,
                            log_msg                            
                        ) VALUES (
                            $id_bt_job,
                            $id_bt_job_downloads,
                            $msg
                        )
                    ";
            $db->query($sql);
            return (int)$db->insert_id;
        } finally {
            $db->close();
        }
    }

    return false;

}