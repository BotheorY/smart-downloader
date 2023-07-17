<?php

/*==============================================================================
RELEASE TYPE
==============================================================================*/
/* defined('RELEASE_TARGET') OR define('RELEASE_TARGET', 'prod');	*/
defined('RELEASE_TARGET') OR define('RELEASE_TARGET', 'dev');

if (trim(strtolower(RELEASE_TARGET)) === 'dev')
	require_once('config/dev/settings.php');	
if (trim(strtolower(RELEASE_TARGET)) === 'test')
	require_once('config/test/settings.php');		
if (trim(strtolower(RELEASE_TARGET)) === 'prod')
	require_once('config/prod/settings.php');	

/*==============================================================================
CONSTANTS
==============================================================================*/
defined('DFT_CHUNK_SIZE') OR define('DFT_CHUNK_SIZE', 3072000);
defined('FILES_FOLDER_NAME') OR define('FILES_FOLDER_NAME', 'downloads');
defined('API_TOKEN_TIMESTAMP_SPAN') OR define('API_TOKEN_TIMESTAMP_SPAN', 60 * 60 * 24 * 5);	// 1689622954_a986b53a7be5d94506c09dbc290c7f4f4b0ac245_084c8a4eccdf4465f52ab517d8a1ae913fe22d673285ddfcefa78cc46ce3ed1e
//defined('API_TOKEN_TIMESTAMP_SPAN') OR define('API_TOKEN_TIMESTAMP_SPAN', 60 * 2);