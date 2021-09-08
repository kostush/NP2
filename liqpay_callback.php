<?php
require_once("tools.php");
require_once("log.php");
require_once("liqpay.php");
function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("liqpay.log", $log, FILE_APPEND);

    return true;
}
/**
 * Created by PhpStorm.
 * User: kostush
 * Date: 9/22/19
 * Time: 4:53 PM
 */

writeToLog($_POST,"Post");
$signature = $_POST['signature'];
$data = $_POST['data'];

$sign = base64_encode( sha1(
    $private_key .
    $data .
    $private_key
    , 1 ));
$data_array = json_decode(base64_decode($data),true);
if ($sign !== $signature) {


    $data_log['sign'] = $sign;
    $data_log['signature'] = $signature;
    writeToLog($data_log, $title = 'Liq_pay Callback - Sign <> Signature');
    echo "Ответ не от Лик Пей - подписи не совпадают";
    die;
}
else {

    writeToLog($data_array, $title = 'Liq_pay Callback - Sign = Signature');


}


// Create connection
global $db;
    $pay_date = (int)($data_array['create_date']/1000);
    $pay_time = date("Y-m-d H:i:s",$pay_date);
    $today = date("Y-m-d H:i:s");
    $end_date = date("Y-m-d H:i:s",strtotime($today."+ 1 month"));
    if ($data_array['status'] !== 'success') { //не обговляем END_DATE
        try {

            $res = $db->query('INSERT INTO b24_portal_payment (`PORTAL`, `ACTION_PB`, `PAIMENT_ID`,`STATUS_PB`,`ORDER_ID`, `DESCRIPTION`, `AMOUNT`,`CURRENCY`, `CREATE_DATE`, `PRODUCT_NAME`) 
                                                values ( ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s)'.
                ' ON DUPLICATE KEY UPDATE `ACTION_PB`=?s, `PAIMENT_ID`=?s,`STATUS_PB`=?s,`ORDER_ID`=?s,
                 `DESCRIPTION`=?s, `AMOUNT`=?s,`CURRENCY`=?s, `CREATE_DATE`=?s, `PRODUCT_NAME`=?s',
                $data_array['product_url'], $data_array['action'],$data_array['payment_id'],$data_array['status'],
                $data_array['order_id'], $data_array['description'],$data_array['amount'],$data_array['currency'],$pay_time, $data_array['product_name'],

                $data_array['action'],$data_array['payment_id'],$data_array['status'],
                $data_array['order_id'], $data_array['description'],$data_array['amount'],$data_array['currency'],$pay_time, $data_array['product_name']
            );
            writeToLog(array('status' => 'success', 'result' => $res));

        }
        catch (Exception $error) {
            writetolog(array('status' => 'error', 'result' => $error->getMessage()));
        }
    }else{
        try {

            $res = $db->query('INSERT INTO b24_portal_payment (`PORTAL`, `ACTION_PB`, `PAIMENT_ID`,`STATUS_PB`,`ORDER_ID`, `DESCRIPTION`, `AMOUNT`,`CURRENCY`, `CREATE_DATE`, `END_DATE`,`PRODUCT_NAME`) 
                                                values (?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s)'.
                ' ON DUPLICATE KEY UPDATE `ACTION_PB`=?s, `PAIMENT_ID`=?s,`STATUS_PB`=?s,`ORDER_ID`=?s,
                 `DESCRIPTION`=?s, `AMOUNT`=?s,`CURRENCY`=?s, `CREATE_DATE`=?s,`END_DATE`=?s, `PRODUCT_NAME`=?s',
                $data_array['product_url'], $data_array['action'],$data_array['payment_id'],$data_array['status'],
                $data_array['order_id'], $data_array['description'],$data_array['amount'],$data_array['currency'],$pay_time,$end_date, $data_array['product_name'],

                $data_array['action'],$data_array['payment_id'],$data_array['status'],
                $data_array['order_id'], $data_array['description'],$data_array['amount'],$data_array['currency'],$pay_time,$end_date, $data_array['product_name']
            );
            writeToLog(array('status' => 'success', 'result' => $res));

        }
        catch (Exception $error) {
            writetolog(array('status' => 'error', 'result' => $error->getMessage()));
        }
    }


