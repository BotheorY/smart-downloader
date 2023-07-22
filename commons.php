<?php

include_once ('utilities.php');

function update_download(int $id, array $data) { 

    if ((!$id) || ($id < 0))
        throw new Exception("Wrong download ID to update.");

    if (empty($data))
        throw new Exception("Missing data updating download.");

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

    if ((!$id) || ($id < 0))
        throw new Exception("Wrong job ID to update.");

    if (empty($data))
        throw new Exception("Missing data updating job.");

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
