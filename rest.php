<?php
require_once("include/config.php");
// здесь нужно прописать значения констант, полученные из карточки решения
// https://partners.1c-bitrix.ru/personal/b24marketplace/edit_module.php?ID=XXX

define(CLIENT_ID, 'app.5d4916c3ea8164.35762900');//app.XXX
define(CLIENT_SECRET, 'nfSu71FGD8B2LowWqnt5d9LLN0rH4SCgxe1Xf2Vlz6EPcD1nV2');

function restCommand($method, array $params = Array(), array $auth = Array(), $authRefresh = true)
{
	$queryUrl = "https://".$auth["domain"]."/rest/".$method;
	$queryData = http_build_query(array_merge($params, array("auth" => $auth["access_token"])));

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_SSL_VERIFYPEER => 1,
		CURLOPT_URL => $queryUrl,
		CURLOPT_POSTFIELDS => $queryData,
	));
	$result = curl_exec($curl);
	curl_close($curl);
	$result = json_decode($result, 1);

	writeToLog($result,"curl_result");

    if ($authRefresh && isset($result['error']) && in_array($result['error'], array('expired_token', 'invalid_token')))
    {
        $auth = restAuth($auth);
        if ($auth)
        {
            $result_rest = restCommand($method, $params, $auth, false);
            writeToLog($result,"curl_result_RESTAuth");
        }
    }

	return $result;
}

function restAuth($auth)// обновление access токена
{
    if (!CLIENT_ID || !CLIENT_SECRET)
        return false;

    if (!isset($auth['refresh_token']) || !isset($auth['domain']))
        return false;

    $queryUrl = 'https://oauth.bitrix.info/oauth/token/';
    $queryData = http_build_query($queryParams = array(
        'grant_type' => 'refresh_token',
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'refresh_token' => $auth['refresh_token']
    ));

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl . '?' . $queryData,
    ));

    $result = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($result, 1);

    if (!isset($result['error'])) {
        $result['domain'] = $auth['domain'];
        $auth = $result;

        // вот здесь надо заново сохранить где-то на стороне приложения
        // обновленные данные аутентификации
        saveParams($auth);
    } else {
        $result = false;
        writeToLog($result,"Ошибка обновления токена результат ");
    }

    return $result;
}
function saveParams($auth){
    global $new_auth;
    $new_auth = array(
    "refresh_token" => $auth["refresh_token"],
    "access_token" => $auth["access_token"],
    );
    writeToLog($auth,"Auth сохраняемые новые параметры авторизации REST.php");

    $server = 'localhost:3306';
    $username = 'kozakov_skk';
    $password = 'NlpdhESkK';
    $db =  'kozakov_skk_nova_poshta_db';
    $port =  3306;
    $charset = 'utf8';
    $connect = mysqli_connect($server, $username, $password, $db);
// Check connection
    if (!$connect) {
        die("Connection failed: " . mysqli_connect_error());
    }

//echo "Законектились к Рег базе данных";
//Сохраняем данные авторизации
    $sql ="UPDATE `b24_portal_reg` 
          SET ACCESS_TOKEN = '$auth[access_token]',
              REFRESH_TOKEN = '$auth[refresh_token]',
              EXPIRES_IN = '$auth[expires_in]'
			WHERE PORTAL = '$auth[domain]'
			";

       ;

    if (mysqli_query($connect, $sql)) {
        //echo "New record created successfully ";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($sql);
    }

    writeToLog(mysqli_free_result($sql),"Обновление БД по токенам");

    mysqli_close($connect);

    return  $sql;
}