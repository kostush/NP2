<?php
// Формироване кнопок оплаты через ликпей

require "liqpay.php";

$public_key = 'i48839519872';//i48839519872
$private_key = 'JQ3M8fkb154cMhDF2WKK4lmTdu1ZdnCkOXvxD4J2';


$server_url = APP_ROOT.'/liqpay_callback.php';
$result_url = APP_ROOT.'/liqpay_result.php';

//echo   $server_url;

$liqpay = new LiqPay($public_key,$private_key);

date_default_timezone_set("Europe/Kiev");

$today = date("Y-m-d H:i:s");
$order_id = $_REQUEST['DOMAIN']."_".$today;
$domain = $_REQUEST['DOMAIN'];

$description_pay= '"NOVA POSHTA off v2 " Оплата за месяц.';
$html_Pay = $liqpay->cnb_form(array(
    'action'         => 'pay',
    'amount'         => '200',
    'currency'       => 'UAH',
    'description'    => $description_pay,
    'order_id'       => $order_id,
    'version'        => '3',
    'server_url'     => $server_url,
    'result_url'     => $result_url,
    'product_url'    => $_REQUEST['DOMAIN'],
    'product_name'   => 'Нова Пошта off'
));
$description_sub= '"NOVA POSHTA off v2 "  Подписка.';
$html_Sub = $liqpay->cnb_form(array(
    'action'         => 'subscribe',
    'amount'         => '200',
    'currency'       => 'UAH',
    'description'    => $description_sub,
    'order_id'       => $order_id,
    'version'        => '3',
    'subscribe'            => '200',
    'subscribe_date_start' => $today,
    'subscribe_periodicity'=> 'month',
    'server_url'     => $server_url,
    'result_url'     => $result_url,
    'product_url'    => $_REQUEST['DOMAIN'],
    'product_name'   => 'Нова Пошта off'
));

// оплаченный период
global $db;
    $res = $db->getRow("SELECT * FROM b24_portal_payment  WHERE PORTAL = ?s", $domain );



    //writeToLog($res, "ответ по дате оплаты");
    //$html_data=date("d-m-Y");//,strtotime("+ 1 months"));
    if (!$res){
        $html_data= date("d-m-Y",strtotime("+ 1 months"));

    }
    else{

        $html_data= date("d-m-Y ",strtotime($res['END_DATE']));;
    }

?>


