<?
$server = 'localhost:3306';
$username = 'kozakov_skk';
$password = 'NlpdhESkK';
$db =  'kozakov_skk_nova_poshta_db';
$port =  3306;
$charset = 'utf8';

$array_stop_status = [3,9,10,11,102,103,105,106,108];
$public_key = 'i48839519872';
$private_key = 'JQ3M8fkb154cMhDF2WKK4lmTdu1ZdnCkOXvxD4J2';
$server_url = 'https://cremaprodotti.com.ua/Bitrix24/skk/NovaPoshta/liqpay_callback.php';
$result_url = 'https://cremaprodotti.com.ua/Bitrix24/skk/NovaPoshta/liqpay_result.php';


/**
 * client_id приложения
 */
define('CLIENT_ID', 'app.5d4916c3ea8164.35762900');//app.XXX
define('CLIENT_SECRET', 'nfSu71FGD8B2LowWqnt5d9LLN0rH4SCgxe1Xf2Vlz6EPcD1nV2');

define('PATH', '/Bitrix24/skk/NovaPoshta/app.php');
/**
 * полный адрес к приложения
 */
define('REDIRECT_URI', 'https://cremaprodotti.com.ua'.PATH);
/**
 * scope приложения
 */
define('SCOPE', 'crm,log,user');

/**
 * протокол, по которому работаем. должен быть https
 */
define('PROTOCOL', "https");

/**
 * поле сделки, в котором хранится номер ТТН Новой почты
 */
/*define('DEAL_TTN', 'UF_CRM_1450788591');

/**
 * Ключ апи Новой почт
 */
//define('API_KEY', 'f8d56f14ee0e8974952756b91a00748b');
define('NP_URL', 'https://api.novaposhta.ua/v2.0/json/');

/* МЕТОД запроса к апи  Новой почты
*/
define('CALLED_METHOD', 'getStatusDocuments');

/**
 * имя модели апи запроса  Новой почты
 */
define('MODEL_NAME', 'TrackingDocument');
/*
function redirect($url)
{
Header("HTTP 302 Found");
Header("Location: ".$url);
die();
}

/**
* Совершает запрос с заданными данными по заданному адресу. В ответ ожидается JSON
*
* @param string $method GET|POST
* @param string $url адрес
* @param array|null $data POST-данные
*
* @return array
*/
function query($method, $url, $data)
{
$query_data = "";

$curlOptions = array(
    CURLOPT_RETURNTRANSFER => true
    );

if($method == "POST")
{
    $curlOptions[CURLOPT_POST] = true;
    $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($data);
}
elseif(!empty($data))
{
    $url .= strpos($url, "?") > 0 ? "&" : "?";
    $url .= http_build_query($data);
}

$curl = curl_init($url);
curl_setopt_array($curl, $curlOptions);
$result = curl_exec($curl);

return json_decode($result, 1);
}

/*
* Запрос к апи Новой Почты
*/
function query_NP($url, $data)
{
$curlOptions = array(
CURLOPT_RETURNTRANSFER => true,
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => json_encode($data));
$curl = curl_init($url);
curl_setopt_array($curl, $curlOptions);
$result = curl_exec($curl);

return json_decode($result, 1);
}

/**
* Вызов метода REST.
*
* @param string $domain портал
* @param string $method вызываемый метод
* @param array $params параметры вызова метода
*
* @return array
*/
function call($domain, $method, $params)
{
return query("POST", PROTOCOL."://".$domain."/rest/".$method, $params);

}