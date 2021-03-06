<?php
/**
 * Created by PhpStorm.
 * User: sv
 * Date: 30.10.17
 * Time: 18:20
 */

writeToLog($_REQUEST, 'request');
print_r($_REQUEST);
return ("возврат");


/**
 * Write data to log file. (by default disabled)
 * WARNING: this method is only created for demonstration, never store log file in public folder
 *
 * @param mixed $data
 * @param string $title
 * @return bool
 */
function writeToLog($data, $title = '')
{

	$log = "\n------------------------\n";
	$log .= date("Y.m.d G:i:s")."\n";
	$log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
	$log .= print_r($data, 1);
	$log .= "\n------------------------\n";

	file_put_contents("sms.log", $log, FILE_APPEND);

	return true;
}