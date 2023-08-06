<?php

include_once ('commons.php');
    
function create_job(int $id_user, array $data): ?int {

	global $conn;

    if ($conn) {
		$file_url = null;
		if (!empty($data['url'])) {
			$res = trim($data['url']);
			if (filter_var($res, FILTER_VALIDATE_URL)) {
				$file_url = '"' . normalize_sql_str($res, $conn) . '"';
			}
		}
		if (!$file_url)
			throw new Exception("Missing file URL");
		$path = parse_url($res, PHP_URL_PATH);
		$file_info = pathinfo($path);
		$file_name = '"' . normalize_sql_str($file_info['basename'], $conn) . '"';
		$file_ext = 'NULL';
		if (empty($file_info['extension'])) {
			if (!empty($data['ext'])) {
				$ext = trim($data['ext']);
				if ($ext[0] === '.') {
					$ext = trim(substr($ext, 1));
				}
				if ($ext)
					$file_ext = '"' . normalize_sql_str($ext, $conn) . '"';
			}
		} else {
			$file_ext = '"' . normalize_sql_str($file_info['extension'], $conn) . '"';
		}
		$job_id = '"' . get_uuid() . '"';
		$callback_url = 'NULL';
		$callback_extra_data = 'NULL';
		$callback_type = 'NULL';
		if (!empty($data['callback'])) {
			$callback_url = trim($data['callback']);
			if (filter_var($callback_url, FILTER_VALIDATE_URL)) {
				$callback_url = '"' . normalize_sql_str($callback_url, $conn) . '"';
				if (!empty($data['callbackData'])) {
					$callback_extra_data = '"' . normalize_sql_str(trim($data['callbackData']), $conn) . '"';
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
		if ($conn->query($sql) === false)
			throw new Exception("Failed creating job record");
		return (int)$conn->insert_id;
    } else {
        throw new Exception("Downloads DB connection failed");
    }

    return null;

}

function start_job(int $id_user, array &$data): ?int {

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

    if (empty($data['cmd']))
        update_job($data['job']['id_bt_job'], ['job_status' => 'DOWNLOADING']);

    $internal_token = get_internal_token($data['token']);
    $data['internal_token'] = $internal_token;
    $data['index'] = $index;
    $data['key'] = $data['job']['job_id'];
    $data['job_id'] = $data['job']['id_bt_job'];
    $data['cmd'] = 'add_download';
    unset($data['url']);
    $err = '';    
    curl_post(get_curr_url(), $data, [CURLOPT_RETURNTRANSFER => 0, CURLOPT_TIMEOUT => 5], $err);

    return true;

}

function create_download(array $data): bool { 

	global $conn;

    $id_download = null;
    $index = (int)$data['index'];

    if (!chk_internal_token($data['token'], $data['internal_token']))
        throw new Exception("Wrong internal token sent to download #{$index}.");

    $id_bt_job = (int)$data['job']['id_bt_job'];

    $job_data = get_job_data($id_bt_job);

    if (!$job_data)
        throw new Exception("Job not found for download #{$index}.");

    if ($job_data['job_status'] === 'FAILED')
        return false;

    try {
        if ($conn) {
			if (!empty($data['retries'])) {
				sleep(PAUSE_BETWEEN_DOWNLOAD_RETRIES);
				$sql = "SELECT id_bt_job_downloads FROM bt_job_downloads WHERE (part_index = $index) AND (id_bt_job = $id_bt_job)";
				$queryres = $conn->query($sql);
				if ($queryres->num_rows > 0) {
					$row = $queryres->fetch_assoc();
					if (!empty($row)) {
						do_download($row['id_bt_job_downloads'], $data);
						return true;
					}
				}
			}            
			$sql =  "
						INSERT INTO bt_job_downloads (
							id_bt_job,
							part_index                            
						) VALUES (
							$id_bt_job,
							$index                            
						)
					";
			if ($conn->query($sql) === false)
				throw new Exception("Failed creating job record");
			$id_download = (int)$conn->insert_id;
			$next_index = $index + 1;
		/* [START] CALC $max_downloads  */
			$max_downloads = MAX_JOB_DOWNLOADS;
			if (!empty($data['max_threads']))
				$max_downloads = (int)$data['max_threads'];
			if ($max_downloads > MAX_JOB_DOWNLOADS)
				$max_downloads = MAX_JOB_DOWNLOADS;
			$file_size = null;
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
			if (isset($data['retries']))
				unset($data['retries']);
			if ($next_index <= $max_downloads) {
				start_download($next_index, $data);
				do_download($id_download, $data);
			} else {
				update_download($id_download, ['last_one' => 1]);
				$data['n_parts'] = $index;
				do_download($id_download, $data);
				start_parts_join($data);
			}
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    } catch (Exception $e) {
        if ($id_download) {
            $err = $e->getMessage();
            update_download($id_download, ['last_err' => $err, 'download_status' => 'FAILED']);
            update_job($id_bt_job, ['last_err' => $err, 'job_status' => 'FAILED']);
        }
    }

    return true;

}

function do_download(int $id_download, array $data) {

    update_download($id_download, ['download_status' => 'PROGRESS']);
    $id_bt_job = (int)$data['job']['id_bt_job'];

    try {
        $download_data = get_download_data($id_download);
        if (!$download_data)
            return false;
        $job_data = get_job_data($download_data['id_bt_job']);
        if (!$job_data)
            return false;
        $url = $job_data['file_url'];
        $index = $download_data['part_index'];
        $down_start_pos = null;
        if ($index > 1)
            $down_start_pos = ($index - 1) * DFT_CHUNK_SIZE;
        $last_one = $download_data['last_one'];
        $lenght = null;
        if (!$last_one)
            $lenght = DFT_CHUNK_SIZE;
        $file_name = get_part_file_name($id_download);
        if (file_exists($file_name))
            unlink($file_name);
        $err = '';
        $down_size = download_file($url, $file_name, $err, $down_start_pos, $lenght);
        if (!$down_size)
            throw new Exception($err);
        update_download($id_download, ['download_status' => 'COMPLETED', 'downloaded_size' => $down_size]);
    } catch (Exception $e) {
        $err = $e->getMessage();
        update_download($id_download, ['last_err' => $err]);
        update_job($id_bt_job, ['last_err' => $err]);
        if (!retry_download($index, $data)) {
            update_download($id_download, ['download_status' => 'FAILED']);
            update_job($id_bt_job, ['job_status' => 'FAILED']);
        }
    }

}

function start_parts_join($data) {

    $data['cmd'] = 'join';
    curl_post(get_curr_url(), $data, [CURLOPT_RETURNTRANSFER => 0, CURLOPT_TIMEOUT => 5], $err);

}


function start_remove_expired_jobs($data) {

    $data['cmd'] = 'remove_expired';
    curl_post(get_curr_url(), $data, [CURLOPT_RETURNTRANSFER => 0, CURLOPT_TIMEOUT => 1], $err);

}

function do_remove_expired_jobs() {

	global $conn;

    if ($conn) {
		$limit_datetime = get_mysql_datetime(MAX_JOB_LIFE_TIME * (-1));
		$sql = "SELECT * FROM bt_job WHERE (job_status = 'EXPIRED') OR (creation_datetime < '$limit_datetime') ORDER BY id_bt_job ASC";
		$queryres = $conn->query($sql);
		if ($queryres) {
			try {
				while ($row = $queryres->fetch_assoc()) {
					$id_job = $row['id_bt_job'];
					if (!($row['job_status'] === 'EXPIRED')) {
						update_job($id_job, ['job_status' => 'EXPIRED']);
						$data =     [
										'msg' => "Download expired at $limit_datetime", 
										'key' => $row['job_id'], 
										'url' => $row['file_url'], 
										'creation_datetime' => $row['creation_datetime'], 
										'err' => $row['last_err'], 
										'ext' => $row['file_ext'], 
										'file_name' => $row['file_name']
									];
						send_callback($id_job, $data);
					}
					delete_job($id_job);
				}    
			} finally {
				$queryres->free();
			}
		}
    } else {
        throw new Exception("Downloads DB connection failed");
    }    

}

function do_parts_join($data) {

	global $conn;

    $id_job = null;
    $id_job = (int)$data['job']['id_bt_job'];    

    try {
        $err = '';
        if (!$id_job)
            throw new Exception("Job ID missing during parts joining.");
        $job_data = get_job_data($id_job);
        if (empty($job_data))
            throw new Exception("Job data not found during parts joining.");    
        if ($job_data['job_status'] === 'FAILED')
            return false;        
        if (empty($data['joining'])) {
            if (chk_job_download_complete($id_job)) {
                update_job($id_job, ['job_status' => 'JOINING']);
                $data['joining'] = true;
            } else {
                start_parts_join($data);
                return false;
            }
        }
        if ($conn) {
			$sql = "SELECT * FROM bt_job_downloads WHERE (id_bt_job = $id_job) AND (download_status = 'COMPLETED') ORDER BY part_index ASC";
			$queryres = $conn->query($sql);
			$num_rows = $queryres->num_rows;
			if ($num_rows > 0) {
				$row = $queryres->fetch_assoc();
				$id_download = $row['id_bt_job_downloads'];
				$index = (int)$row['part_index'];
				if (!is_dir(FILES_FOLDER_NAME)) {
					mkdir(FILES_FOLDER_NAME, 0755, true);
				}        
				$target = FILES_FOLDER_NAME . '/' . $data['key'];
				$ext = $job_data['file_ext'];
				if ($ext)
					$target .= ".$ext";
				if ($index === 1) {
					$source1 = get_part_file_name($id_download);
					if (file_exists($target))
						unlink($target);
					if (copy($source1, $target)) {
						unlink($source1);
					} else {
						throw new Exception("Error copying file \"$source1\" to \"$target\".");                
					}
				} else {
					$source1 = $target;
					$source2 = get_part_file_name($id_download);
					if (!append_file($source1, $source2, $err, DFT_CHUNK_SIZE))
						throw new Exception("Error during parts joining: $err");                        
				}
				delete_download($id_download);
				if ($num_rows > 1) {
					start_parts_join($data);
				} else {
					update_job($id_job, ['job_status' => 'COMPLETED']);
				}
			} else {
				throw new Exception("Wrong number ($num_rows) of completed parts download during parts joining.");
			}
        } else {
            throw new Exception("Downloads DB connection failed");
        }    
    } catch (Exception $e) {
        if ($id_job) {
            update_job($id_job, ['job_status' => 'FAILED', 'last_err' => $e->getMessage()]);
        }
    }

}

function retry_download(int $index, array $data): bool {

    $retry = 0;

    if (!empty($data['retry'])) 
        $retry = (int)$data['retries'];

    $retry += 1;

    if ($retry <= MAX_DOWNLOAD_RETRIES) {
        $data['retries'] = $retry;
        start_download($index, $data);
        return true;
    }
    
    return false;

}
