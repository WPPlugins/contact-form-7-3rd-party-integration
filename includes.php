<?php

#if( !file_exists( WP_CONTENT_DIR . '/library/common-functions.php')):

if( !function_exists('v')):
/**
 * Safely get a value or return default if not set
 *
 * @param mixed $v value to get
 * @param mixed $d default if value is not set
 * @return mixed
 */
function v(&$v, $d = NULL){ return isset($v)?$v:$d; }
endif;	//check if common-functions exists

#endif;	//check if common-functions exists in /library
