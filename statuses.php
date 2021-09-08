<?php
include ('rest.php');

// скрипт, который работает с заданной периодичностью, проверяет статусы отправленных
// сообщений и передает эту информацию в Битрикс24

// 1. логика обработки одного сообщения из очереди

// откуда из базы получаем идентификатор оотправленного сообщения - это id, который
// нам передал Битрикс24 в обработчик handler.php
$bx24_message_id = 'e35864edce3eaad987d32f8955c1177b';

// находим запись о соответствующем Битрикс24 в сохраненных данных
// для аутентификации по OAuth 2.0, которые мы сохраняли в install.php или app.php
$bx24_domain = 'restapi.bitrix24.ru';
$bx24_access_token = 'x4o81wfl4g57qur2u8sjrlcha76zn0tj';
$bx24_refresh_token = 'g06ete91nmfa14ew5s2gt432l4in6q6h';

// берем через API провайдера информацию о статусе сообщения и выбираем соответствующий
// ему статус в Битрикс24: delivered, undelivered, failed
$status = 'delivered';

// 2. сообщаем Битрикс24 о статусе сообщения
$result = restCommand ('messageservice.message.status.update',
	array(
		'CODE' => 'fastsms',
		'message_id' => $bx24_message_id,
		'status' => $status
	),
	array(
		'access_token' => $bx24_access_token,
		'refresh_token' => $bx24_refresh_token,
		'domain' => $bx24_domain
	),
	true
);

echo "<pre>"; print_r($result); echo "</pre>";

if (isset($result['error'])) {
	// что-то пошло не так, анализируем ошибку и реагируем на нее
}

