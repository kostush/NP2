<?
define('APP_ID', 'app.5d4916c3ea8164.35762900');//app.5d4916c3ea8164.35762900
define('APP_SECRET_CODE', 'nfSu71FGD8B2LowWqnt5d9LLN0rH4SCgxe1Xf2Vlz6EPcD1nV2');//nfSu71FGD8B2LowWqnt5d9LLN0rH4SCgxe1Xf2Vlz6EPcD1nV2
define('APP_REG_URL', 'https://cremaprodotti.com.ua/Bitrix24/skk/np2/install.php');
define ('APP_ROOT', 'https://cremaprodotti.com.ua/Bitrix24/skk/np2');

define ('IMAGE_DIR', $_SERVER['DOCUMENT_ROOT'].'/Bitrix24/skk/np2/images/');

$public_key = 'i48839519872';//i48839519872
$private_key = 'JQ3M8fkb154cMhDF2WKK4lmTdu1ZdnCkOXvxD4J2';



require_once("db.php");
require_once('bitrix24.php');
require_once('bitrix24exception.php');
require_once('bitrix24entity.php');
require_once('bitrix24user.php');
require_once('bitrix24log.php');
require_once('bitrix24batch.php');

$settings = array(
    'host' => 'localhost:3306',
    'user' => 'kozakov_skk',
    'pass' => 'NlpdhESkK',
    'db' => 'kozakov_skk_nova_poshta_db',
    'port' => 3306,
    'charset' => 'utf8',
);
// альтернатива
$server = 'localhost:3306';
$username = 'kozakov_skk';
$password = 'NlpdhESkK';
$db =  'kozakov_skk_nova_poshta_db';
$port =  3306;
$charset = 'utf8';
//************** для liqpay_callback



// Images

function getExtension($filename) {
    return substr($filename, strrpos($filename, '.') + 1);
}



//B24

function prepareFromRequest($arRequest) {
    $arResult = array();
    $arResult['domain'] = $arRequest['DOMAIN'];
    $arResult['member_id'] = $arRequest['member_id'];
    $arResult['refresh_token'] = $arRequest['REFRESH_ID'];
    $arResult['access_token'] = $arRequest['AUTH_ID'];

    return $arResult;
}

function prepareFromDB($arAccessParams) {
    $arResult = array();
    $arResult['domain'] = $arAccessParams['PORTAL'];
    $arResult['member_id'] = $arAccessParams['MEMBER_ID'];
    $arResult['refresh_token'] = $arAccessParams['REFRESH_TOKEN'];
    $arResult['access_token'] = $arAccessParams['ACCESS_TOKEN'];

    return $arResult;
}

function getBitrix24 (&$arAccessData, &$btokenRefreshed, &$errorMessage, $arScope=array()) {
    $btokenRefreshed = null;

    $obB24App = new \Bitrix24\Bitrix24();
    if (!is_array($arScope)) {
        $arScope = array();
    }
    if (!in_array('user', $arScope)) {
        $arScope[] = 'user';
    }
    $obB24App->setApplicationScope($arScope);
    $obB24App->setApplicationId(APP_ID); //�� �������� � MP
    $obB24App->setApplicationSecret(APP_SECRET_CODE); //�� �������� � MP

    // set user-specific settings
    $obB24App->setDomain($arAccessData['domain']);
    $obB24App->setMemberId($arAccessData['member_id']);
    $obB24App->setRefreshToken($arAccessData['refresh_token']);
    $obB24App->setAccessToken($arAccessData['access_token']);

    try {
        $resExpire = $obB24App->isAccessTokenExpire();
    }
    catch(\Exception $e) {
        $errorMessage = $e->getMessage();
        // cnLog::Add('Access-expired exception error: '. $error);
    }

    if ($resExpire) {
        // cnLog::Add('Access - expired');

        $obB24App->setRedirectUri(APP_REG_URL);

        try {
            $result = $obB24App->getNewAccessToken();
        }
        catch(\Exception $e) {
            $errorMessage = $e->getMessage();
           /// writeToLog($errorMessage,"errorMessage");
            //\cnLog::Add('getNewAccessToken exception error: '. $error);
        }
        if ($result === false) {
            $errorMessage = 'access denied';
        }
        elseif (is_array($result) && array_key_exists('access_token', $result) && !empty($result['access_token'])) {
            $arAccessData['refresh_token']=$result['refresh_token'];
            $arAccessData['access_token']=$result['access_token'];
            $obB24App->setRefreshToken($arAccessData['refresh_token']);
            $obB24App->setAccessToken($arAccessData['access_token']);
            // \cnLog::Add('Access - refreshed');
            $btokenRefreshed = true;
        }
        else {
            $btokenRefreshed = false;
        }
    }
    else {
        $btokenRefreshed = false;
    }

    return $obB24App;
}



global $db;

$db = new SafeMySQL($settings);

