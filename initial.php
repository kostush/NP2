<?php
/**
 * Created by PhpStorm.
 * User: kostush
 * Date: 8/7/19
 * Time: 1:45 AM
 */

require("include/config.php");
writeToLog($_POST, 'initial');
//print_r($_POST);

function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("mysql.log", $log, FILE_APPEND);

    return true;
}


$conn = mysqli_connect($server, $username, $password, $db);
if (!$conn) {
    die("Connection to mysql db failed: " . mysqli_connect_error());
}
//echo " Connected db successfully ";
$domain = $_POST['domain'];
writeToLog($domain, "domain from POST");


$sql="SELECT * FROM b24_np_statuses  WHERE PORTAL = '$domain' ";
writeToLog($sql, "запрос");
$res= mysqli_query($conn,$sql);

if ($res) {
    $row = mysqli_fetch_assoc($res);

    writeToLog($row, "row");
    Header('Cache-Control: no-cache');
    Header('Pragma: no-cache');
    echo json_encode(array('status' =>'success','result' =>$row),JSON_PRETTY_PRINT);

} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($sql);
}

mysqli_close($conn);



