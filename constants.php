<?php

/*==============================================================================
CONSTANTS
==============================================================================*/
defined('MAX_SIMULTANEOUS_JOBS') OR define('MAX_SIMULTANEOUS_JOBS', 10);
defined('DFT_CHUNK_SIZE') OR define('DFT_CHUNK_SIZE', 3072000);
//defined('DFT_CHUNK_SIZE') OR define('DFT_CHUNK_SIZE', 524288);
defined('DFT_MAX_EXECUTION_TIME') OR define('DFT_MAX_EXECUTION_TIME', 60);
defined('MAX_JOB_LIFE_TIME') OR define('MAX_JOB_LIFE_TIME', 60 * 60 * 10); // 10 hours
defined('MAX_JOB_DOWNLOADS') OR define('MAX_JOB_DOWNLOADS', 100000);
defined('FILES_FOLDER_NAME') OR define('FILES_FOLDER_NAME', 'downloads');
defined('API_TOKEN_TIMESTAMP_SPAN') OR define('API_TOKEN_TIMESTAMP_SPAN', 60 * 60 * 24 * 50);	// 1689622954_a986b53a7be5d94506c09dbc290c7f4f4b0ac245_084c8a4eccdf4465f52ab517d8a1ae913fe22d673285ddfcefa78cc46ce3ed1e
//defined('API_TOKEN_TIMESTAMP_SPAN') OR define('API_TOKEN_TIMESTAMP_SPAN', MAX_JOB_LIFE_TIME);
defined('MAX_DOWNLOAD_RETRIES') OR define('MAX_DOWNLOAD_RETRIES', 3);
defined('PAUSE_BETWEEN_DOWNLOAD_RETRIES') OR define('PAUSE_BETWEEN_DOWNLOAD_RETRIES', 30);	// seconds
