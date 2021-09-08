<?
function writeToLog($data, $title = '')
{

$log = "\n------------------------\n";
$log .= date("Y.m.d G:i:s")."\n";
$log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
$log .= print_r($data, 1);
$log .= "\n------------------------\n";

file_put_contents("app.log", $log, FILE_APPEND);

return true;
}

writeToLog($_REQUEST,"request");