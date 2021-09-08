<?
require_once("tools.php");
require_once("db.php");
require_once("log.php");
require_once("lead_create.php");
//require_once("pay.php");

function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("application.log", $log, FILE_APPEND);

    return true;
}

class CApplication
{
    public $arB24App;
    public $arAccessParams = array();
    public $arRatingUsers = array();
    public $currentUser = 0;
    private $b24_error = '';
    public $is_ajax_mode = false;
    public $is_background_mode = false;
    public $currentRating = 0;

    private function checkB24Auth() {

        // проверяем актуальность доступа
        $isTokenRefreshed = false;

        // $arAccessParams['access_token'] = '123';
        // $arAccessParams['refresh_token'] = '333';

        $this->arB24App = getBitrix24($this->arAccessParams, $isTokenRefreshed, $this->b24_error);
        return $this->b24_error === true;
    }

    private function returnJSONResult ($answer) {

        ob_start();
        ob_end_clean();
        Header('Cache-Control: no-cache');
        Header('Pragma: no-cache');
        echo json_encode($answer);
      //  writeToLog(json_encode($answer),"returnJSONResult");
        die();
    }



    public function saveAuth() {
        global $db;

        $res = $db->query(
            'INSERT INTO b24_portal_reg (`PORTAL`, `ACCESS_TOKEN`, `REFRESH_TOKEN`, `MEMBER_ID`) values (?s, ?s, ?s, ?s)'.
            ' ON DUPLICATE KEY UPDATE `ACCESS_TOKEN` = ?s, `REFRESH_TOKEN` = ?s, `MEMBER_ID` = ?s',
            $this->arB24App->getDomain(), $this->arB24App->getAccessToken(), $this->arB24App->getRefreshToken(), $this->arB24App->getMemberId(),
            $this->arB24App->getAccessToken(), $this->arB24App->getRefreshToken(), $this->arB24App->getMemberId()
        );

    }

    public function manageAjax($operation, $params)
    {
        global $db;
        writeToLog($operation,"manageAjax");
        switch ($operation){
            case 'preset':
                //writeToLog($params,"case preset");
                try {
                   $res =  $db->getRow("SELECT * FROM  b24_portal_reg AS bpr 
                                LEFT JOIN  b24_np_statuses AS bns ON bns.PORTAL = bpr.PORTAL
                                LEFT JOIN  b24_portal_payment AS bpp ON bpp.PORTAL = bns.PORTAL
                                LEFT JOIN  b24_category AS bc ON bc.PORTAL = bpp.PORTAL
                                WHERE bpr.PORTAL= ?s", $params['authParams']['domain']);


                    writeToLog($res,'res SELECT');
                    $this->returnJSONResult(array('status' => 'success', 'result' => $res));
                    //LEFT JOIN  b24_category AS bc ON bc.PORTAL = bpp.PORTAL
                }
                catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }
               /* try {
                    //writeToLog($db,'db');
                    $res_category = $db->getRow('SELECT * FROM `b24_category` '.
                        'WHERE `PORTAL` = ?s ',  $params['authParams']['domain']);
                    writeToLog($res_category,'res SELECT category');
                    $this->returnJSONResult(array('status' => 'success', 'result' => $res));

                }
                catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }*/

                break;

            case 'install':

                    writeToLog($params,"case 'install':");

                    try {
                        $res = $db->query('INSERT INTO b24_users (`PORTAL`, `ID_USER`, `NAME`,`EMAIL`) values (?s, ?i, ?s,?s)'.
                           ' ON DUPLICATE KEY UPDATE `NAME` = ?s,`EMAIL` = ?s',
                            $params['authParams']['domain'], $params['user']['ID'], $params['user']['NAME'],$params['user']['EMAIL'],
                            $params['user']['NAME'],$params['user']['EMAIL']);
                        if ($db->affectedRows() == 1){
                            $res_create = createLead(array(
                                "portal" =>$params['authParams']['domain'],
                                "first_name" => $params['user']['NAME'],
                                "last_name" =>$params['user']['LAST_NAME'],
                                "email" =>$params['user']['EMAIL'],
                                "phone" =>$params['user']['PHONE'],
                                "comments" => "`NP_2 off`"
                            ));
                        }


                        //writetolog($db->affected_rows(), "db->affected_rows");// лид в моем портале

                    }
                    catch (Exception $error) {
                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                    }
                    // запишем данные по лик пей и стадиям лидов и сделок
                    try {
                        $res = $db->query('INSERT INTO b24_np_statuses (`PORTAL`, `TTN_FIELD_ID`,`DEAL_FIELD_NOVAPOSHTA_STATUS`,`DEAL_FIELD_NOVAPOSHTA_STATUS_CODE`,`NP_API_KEY`, `DEL_COST_ID`, `DEL_DATA_ID`)   
                                         values (?s, ?s, ?s, ?s, ?s, ?s, ?s)'.
                            ' ON DUPLICATE KEY UPDATE `TTN_FIELD_ID` = ?s,`DEAL_FIELD_NOVAPOSHTA_STATUS`= ?s,`DEAL_FIELD_NOVAPOSHTA_STATUS_CODE`= ?s,`NP_API_KEY`= ?s, `DEL_COST_ID`= ?s, `DEL_DATA_ID`= ?s',
                            $params['authParams']['domain'],
                            $params['data']['deal_ttn_field_id'],
                            $params['data']['deal_field_novaposhta_status_id'],
                            $params['data']['deal_field_novaposhta_code_id'],
                            $params['data']['api_keys'],
                            $params['data']['deal_del_cost_id'],
                            $params['data']['deal_del_date_id'],
                            // duplicate
                            $params['data']['deal_ttn_field_id'],
                            $params['data']['deal_field_novaposhta_status_id'],
                            $params['data']['deal_field_novaposhta_code_id'],
                            $params['data']['api_keys'],
                            $params['data']['deal_del_cost_id'],
                            $params['data']['deal_del_date_id']);

                    }
                    catch (Exception $error) {
                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                    }

                    // Создаем колонки таблицы БД для каждой новой категории , если их нет
                    try {
                        // получаем столбцы стадий из таблицы
                        $arCategory = $params['data']['arUserDealStage'];
                        $arStatusNovaPoshta = $params['data']['arStatusNovaPoshta'];
                        $query_insert = "`PORTAL`";
                        $query_values ="?s";
                        $query_param = "$"."params['authParams']['domain']";
                        $query_null_stage ="";

                        $query_add ="ALTER TABLE `b24_category` ";

                       // writeToLog($arCategory,"arCategory");


                        foreach ($arCategory as $category => $value) {

                            foreach ($arStatusNovaPoshta as $status => $value_np) {
                                    $code_np = $value_np[0];
                                $query = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA  = 'kozakov_skk_nova_poshta_db' AND TABLE_NAME = 'b24_category'");
                                $Existence_column = $query;
                                $Existence_column = $query;

                                // есть ли такой столбец в таблице
                               //writeToLog(array($Existence_column), "Existence_column- line 191");

                                while ($row = $db->fetch($Existence_column)) {
                                    $find = false;
                                   // writetolog( $row['COLUMN_NAME'],"row['COLUMN_NAME'] - line 195");
                                    if ($row['COLUMN_NAME'] == $category . "_".$code_np) {
                                        /* $query_insert .=",`".$category."_DEAL_START`";
                                         $query_values .=",?s";
                                         $query_param .=",$"."params['data']['arUserDealStage']['".$category."']['deal_start']";*/

                                        $find = true;
                                        break;
                                    }
                                }

                                if (!$find) {// нет столбца- создаем
                                    try {
                                        $res = $db->query("ALTER TABLE `b24_category` ADD `" . $category . "_" . $code_np . "` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
                                    }
                                    catch(Exception $error) {
                                        writetolog(array('status' => 'error', 'result' => $error->getMessage()));
                                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                                    }

                                }
                                //записшем в столбец значение
                                writetolog( $category . "_".$code_np,"category _code_np. перед записью новой категории line 212");
                                try {$res_insert = $db->query("INSERT INTO `b24_category` (`PORTAL`,`" . $category . "_".$code_np."`) 
                                                    values (?s,?s) 
                                                    ON DUPLICATE KEY UPDATE `" . $category . "_".$code_np."` = ?s",
                                    $params['authParams']['domain'], $params['data']['arUserDealStage'][$category][$code_np],
                                    $params['data']['arUserDealStage'][$category][$code_np]);
                                }
                                catch (Exception $error) {
                                    writetolog(array('status' => 'error', 'result' => $error->getMessage()));
                                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                                }

                            }
                        }
                        //writetolog( array($res_insert),"res insert");
                        //обнуляем все остальные стадии категории сделок в БД
                        //$Existence_column = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'b24_category'");

                        while ($row = $db->fetch($Existence_column)) {
                            if ($row['COLUMN_NAME'] !="PORTAL"){
                                //writetolog( $row['COLUMN_NAME'],"row['COLUMN_NAME'] - line 227 цикл вытирания");
                                $exist_stage = false;
                                foreach ( $arCategory as $category =>$value) {

                                    foreach ($arStatusNovaPoshta as $status => $value_np) {
                                        $code_np = $value_np[0];

                                        if ($row['COLUMN_NAME'] == $category ."_". $code_np)  {
                                            $exist_stage = true;//есть такая стадия, как условие срабатывания приложения - не вытираем значение в БД
                                            break;
                                        }
                                    }
                                }
                                if (!$exist_stage){
                                   // writeToLog($row['COLUMN_NAME'],"row['COLUMN_NAME'] если нет стадии -  перд записью NO");
                                    $res_null= $db->query("INSERT INTO `b24_category` (`PORTAL`,`".$row['COLUMN_NAME']."`) 
                                                        values (?s,?s) 
                                                        ON DUPLICATE KEY UPDATE `".$row['COLUMN_NAME']."` = ?s",
                                        $params['authParams']['domain'], "NO", "");

                                }
                            }

                        }


                        // запишем в них значения


                    }
                    catch (Exception $error) {
                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                    }

                    // запишем данные по оплате
                    try {
                        $today = date("Y-m-d H:i:s");
                        $end_date = date("Y-m-d H:i:s",strtotime($today."+ 7 days"));
                        $res = $db->query('insert into b24_portal_payment (`PORTAL`, `ACTION_PB`, `PAIMENT_ID`,`STATUS_PB`,`ORDER_ID`, `DESCRIPTION`, `AMOUNT`,`CURRENCY`, `CREATE_DATE`, `END_DATE`,`PRODUCT_NAME`) 
                                                values (?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s)'.
                                                ' ON DUPLICATE KEY UPDATE `DESCRIPTION`=?s',
                            $params['authParams']['domain'],'test','test','test','test','test','0','UAH',$today,$end_date,'Nova Poshta V2','test');
                         // $this->returnJSONResult(array('status' => 'success', 'result' => ''));
                    }
                    catch (Exception $error) {
                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                    }



                $this->saveAuth();
                $this->returnJSONResult(array('status' => 'success', 'result' => ''));

                break;



            case 'uninstall':

                \CB24Log::Add('uninstall 1: '.print_r($_REQUEST, true));

                break;

            default:
                $this->returnJSONResult(array('status' => 'error', 'result' => 'unknown operation'));
        }
    }

    public function processBackgroundData () {

    }



    private function getAuthFromDB() {
        global $db;

        $res = $db->getRow('SELECT * FROM `b24_portal_reg` LIMIT 1');
        $this->arAccessParams = prepareFromDB($res);

        $this->b24_error = $this->checkB24Auth();

        if ($this->b24_error != '') {
            echo $this->b24_error;
            \CB24Log::Add('background auth error: '.$this->b24_error);
            die;
        }

        \CB24Log::Add('background auth success!');

    }

    public function start () {

        $this->is_ajax_mode = isset($_REQUEST['operation']);
        $this->is_background_mode = isset($_REQUEST['background']);

        if ($this->is_background_mode) $this->getAuthFromDB();
        else {
            if (!$this->is_ajax_mode)
                $this->arAccessParams = prepareFromRequest($_REQUEST);
            else
                $this->arAccessParams = $_REQUEST['authParams'];

            $this->b24_error = $this->checkB24Auth();
            writeToLog($this->b24_error,"this->b24_error при проверке авторизации");

            if ($this->b24_error != '') {
                if ($this->is_ajax_mode)
                    $this->returnJSONResult(array('status' => 'error', 'result' => $this->b24_error));
                else
                    echo "B24 error: ".$this->b24_error;

                die;
            }
        }

    }
}

$application = new CApplication();
 writeToLog($_REQUEST,"request");
if (!empty($_REQUEST)) {

    //writeToLog($_REQUEST,"request");

    $application->start();
    writeToLog($application->is_ajax_mode,"application->is_ajax_mode");

    if ($application->is_ajax_mode) $application->manageAjax($_REQUEST['operation'], $_REQUEST);
    else {
        if ($application->is_background_mode) $application->processBackgroundData();
        //else $application->getData();
    }
}
?>/