<?php
require_once("tools.php");
require_once("log.php");
require_once( __DIR__ . "/src/Delivery/NovaPoshtaApi2.php");


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

    file_put_contents("event_logs/event".date('Y.m.d').".log", $log, FILE_APPEND);

    return true;
}

class CEvent
{
    public $is_deal_mode ;
    public $arAccessParams ;
    public $access_token ;
    public $domain ;
    public $user ;
    public $application_token ;
    public $item ;
    public $isTTNempty = false;
    public $isTTNnotChange = false;
    public $is_deal_closed = false;
    public $ttn;
    public $actual_from_db = false;
    public $actual =true;
    public $old_ttn;

    public function checkPaidPeriod($obB24_result,$db_result){
        // проверяем оплаченный период
        return true;

        $end_date = date("Y-m-d H:i:s",strtotime($db_result['END_DATE']));;
        $current_date = date("Y-m-d H:i:s");

        if( $current_date > $end_date){

            $im_user = $obB24_result->call( "im.notify", array(
                "to" => $this->user,
                "message" => "Запити до 'Нової Пошти' не працюють ! Скінчився період оплати. Було сплачено по "
                    .$end_date ."    Запустіть додаток та натисніть 'Оплатити'",
                "type" => "SYSTEM"

            ));
            writeToLog( $end_date, " current_date > END_DATE");
            return false;

        }
        writeToLog( $end_date, " current_date < END_DATE");
        return true;

    }
    public function saveError ($array){
        writeToLog($array,"Массив ошибок записи сделки в БД  ");
    }

    public function start () {

        $this->is_deal_mode = ($_REQUEST['item']=='deal'? true:false);
        $this->arAccessParams = $_REQUEST['auth'];
        $this->access_token = $_REQUEST['auth']['access_token'];
        $this->domain = $_REQUEST['auth']['domain'];
        $this->user = $_REQUEST['auth']['user_id'];
        $this->application_token = $_REQUEST['auth']['application_token'];
        $this->item = $_REQUEST['item'];



    }

    public function connect_NP($array){

    }
    public function check_Actuality($result_deal, $result_db, $result_item){

        $this->ttn = $result_deal['result'][$result_db['TTN_FIELD_ID']];
        if ($this->ttn == "" ){
            $this->isTTNempty = true;

        }
        if ( $result_item != 0){  // запрос в БД выдал кол-во  строк больше 0 - есть запись в БД
            $this->actual_from_db = $result_item["ACTUAL"];
            if ($this->ttn == $result_item['TTN']){ // поле ТТН в Б24  не измениласть
                $this->isTTNnotChange = true;
            }
        }/*else {
            $this->isTTNnotChange = true;
            }*/


        if ($this->is_deal_closed or  $this->isTTNempty or $this->isTTNnotChange ){//or $this->actual_from_db
            $res = false;// не нужно дергать НП
        }else {
            $res = true;
        }
        //$this->actual =  $res;


        writeToLog(array ("this->is_deal_closed"=>$this->is_deal_closed,"this->isTTNempty" =>$this->isTTNempty, "this->isTTNnotChange" =>$this->isTTNnotChange,"this->actual_from_db" =>$this->actual_from_db));

        return $res;
    }

    public function manageEvent($event, $params)
    {
        global $db;


        $obB24 = new \Bitrix24\Bitrix24();
        $obB24->setAccessToken($params['auth']['access_token']);
        $obB24->setDomain($params['auth']['domain']);
        $obB24->setMemberId($params ['auth']['member_id']);

        switch ($event) {

            case 'deal':
                $this->method = 'crm.deal.get';
                $this->dealId = $params['data']['FIELDS']['ID'];

                $result_deal = $obB24->call($this->method, array('id' => $this->dealId));
                writeToLog($result_deal , "result_deal ");

                sleep(5);
                $this->is_deal_closed = ($result_deal['result']['CLOSED'] == 'Y' ? true:false);


                //writeToLog($this->is_deal_closed , "this->is_deal_closed ");
               // writetolog($new_stage_id, "new_stage_id");
                //$this->setDealFeild($result_deal);

                if ($result_item = $db->getRow('SELECT * FROM `stage_status` ' .
                    'WHERE PORTAL = ?s  ' .
                    'AND DEAL_ID=?s', $params['auth']['domain'],  $params['data']['FIELDS']['ID'])) {

                    $this->old_ttn = $result_item['TTN'];


                };

                writeToLog($result_item , "result_item ");
                //*********************

                $result_db = $db->getRow('SELECT * FROM `b24_np_statuses` AS nps ' .
                    'LEFT JOIN `b24_portal_payment` AS pp ON pp.PORTAL = nps.PORTAL ' .
                    'LEFT JOIN `b24_portal_reg` AS pr ON pr.PORTAL = pp.PORTAL ' .
                    'WHERE nps.PORTAL = ?s ', $params['auth']['domain']);

                writeToLog($result_db, "result_db");

                if(!$this -> check_Actuality($result_deal, $result_db, $result_item)){ //не актуальна проверка на НП
                    //
                    if ($this->isTTNempty or $this->is_deal_closed){
                        $this->actual = false;
                        // Запись в БД данных сделки
                        try {
                            $res = $db->query('INSERT INTO stage_status (`PORTAL`, `DEAL_ID`, `TTN`,`ACTUAL`) values (?s, ?i, ?s,?s)'.
                                ' ON DUPLICATE KEY UPDATE `TTN`= ?s,`ACTUAL` =?s',


                                $params['auth']['domain'], $result_deal['result']['ID'], $this->ttn,$this->actual,
                                $this->ttn, $this->actual
                            );
                        }
                        catch (Exception $error) {
                            $this->saveError(array('status' => 'error', 'result' => $error->getMessage()));
                        }
                    }

                    if ($this->isTTNnotChange){ // если ТТН не менялась - выход без обновлени] БД
                        die;
                    }

                    die;

                };


                $deal_np_status_id = $result_db['DEAL_FIELD_NOVAPOSHTA_STATUS'];
                $deal_np_status_code_id = $result_db['DEAL_FIELD_NOVAPOSHTA_STATUS_CODE'];
                $deal_np_ScheduledDeliveryDate_id = $result_db['DEL_DATA_ID'];
                $deal_np_DocumentCost_id = $result_db['DEL_COST_ID'];
                $deal_np_ttn_field_id = $result_db['TTN_FIELD_ID'];


                if(!$this->checkPaidPeriod($obB24,$result_db)){ // проверка оплты
                    die;
                };

                // Nova Poshta
                $api_keys_array  = explode("|",$result_db['NP_API_KEY']);
                $np = new \LisDev\Delivery\NovaPoshtaApi2($api_keys_array[0]);

                $result_ttn  = $np->documentsTracking($this->ttn);
                writeToLog($np,"np");
                writeToLog($result_ttn,"result_ttn");

                if ($result_ttn['errors']){
                    $err=$result_ttn['errors'][0];

                    $im_user = $obB24->call( "im.notify", array(
                        "to" => $this->user,
                        "message" => "Помилка запиту до Нової Пошти ! #BR#  #BR# - [b] $err [/b]",
                        "type" => "SYSTEM"

                    ));

                }

                $np_Status = $result_ttn['data']['0']['Status'];
                $np_StatusCode = $result_ttn['data']['0']['StatusCode'];
                $np_ScheduledDeliveryDate =  ($result_ttn['data']['0']['ScheduledDeliveryDate'] ? $result_ttn['data']['0']['ScheduledDeliveryDate']:"");
                $np_DocumentCost = ($result_ttn['data']['0']['DocumentCost'] ? $result_ttn['data']['0']['DocumentCost']:"");

                if ($np_StatusCode == '3'){// Номер не знайдено

                    $im_user = $obB24->call( "im.notify", array(
                        "to" => $this->user,
                        "message" => "Номер ТТН - НЕ ЗНАЙДЕНО !",
                        "type" => "SYSTEM"

                    ));
                    $this->actual = 0;

                }




                // получаем даные сделки



                //********запрос стадий п категориям
                if ($result_category_stage_db = $db->getRow('SELECT * FROM `b24_category`  ' .
                    'WHERE PORTAL = ?s ', $params['auth']['domain'])) {
                    //$category = $result_deal['result']['CATEGORY_ID'];
                    $new_deal_stage_id = $result_category_stage_db[$result_deal['result']['CATEGORY_ID']."_".$np_StatusCode];

                   // $deal_category_start = $result_category_stage_db[$deal_category_id . "_DEAL_START"];
                   // $deal_category_finish = $result_category_stage_db[$deal_category_id . "_DEAL_FINISH"];

                } else { // для версии 1 где не было категорий сделок
                    $new_deal_stage_id = $result_db [$np_StatusCode];

                    //$deal_category_start = $deal_start;
                    //$deal_category_finish = $deal_finish;
                }

                writeToLog($result_category_stage_db, " result_category_stage_db");
                writeToLog($new_deal_stage_id,"new_deal_stage_id");

                // Запись в Б24
                // меняем стадию сделки (если нужно) и значение полей сделки со статусами НП
                if ($new_deal_stage_id !== 'NO') {
                    $stage_to_DB = $new_deal_stage_id;
                    $result_deal_update = $obB24->call('crm.deal.update', array(
                        'id' => $result_deal['result']['ID'],
                        'fields' =>array(
                            "STAGE_ID" => $new_deal_stage_id,
                            $deal_np_status_id => $np_Status,
                            $deal_np_status_code_id => $np_StatusCode,
                            $deal_np_ScheduledDeliveryDate_id => $np_ScheduledDeliveryDate,
                            $deal_np_DocumentCost_id => $np_DocumentCost)
                        ));
                }
                else {
                    $stage_to_DB = $result_deal['result']['STAGE_ID']; // save old stage
                    $result_deal_update = $obB24->call('crm.deal.update', array(
                        'id' => $result_deal['result']['ID'],
                        'fields' =>array(
                           // "STAGE_ID" => $new_deal_stage_id,  т.к стату НП не привязан к конкретной стадии сделки
                            $deal_np_status_id => $np_Status,
                            $deal_np_status_code_id => $np_StatusCode,
                            $deal_np_ScheduledDeliveryDate_id => $np_ScheduledDeliveryDate,
                            $deal_np_DocumentCost_id => $np_DocumentCost)
                    ));
                }

                // Запись в БД
                try {
                    $result_insert = $db->query('INSERT INTO stage_status (`PORTAL`, `DEAL_ID`, `DEAL_STAGE`,`TTN`,`STATUS_NP`,`STATUS_NP_TEXT`,`ACTUAL`) values (?s, ?i, ?s,?s,?s,?s,?s)'.
                        ' ON DUPLICATE KEY UPDATE `DEAL_STAGE` =?s,`TTN`= ?s,`STATUS_NP` =?s,`STATUS_NP_TEXT`=?s,`ACTUAL` =?s',


                        $params['auth']['domain'], $result_deal['result']['ID'],$new_deal_stage_id, $this->ttn,$np_StatusCode, $np_Status, $this->actual,
                        $stage_to_DB, $this->ttn,$np_StatusCode,$np_Status, $this->actual
                    );



                    writetolog($result_insert, "result_insert");// лид в моем портале

                }
                catch (Exception $error) {
                    $this->saveError(array('status' => 'error', 'result' => $error->getMessage()));
                }

                break;
            default:
                ;


        }
    }


}
$application = new CEvent();
writeToLog($_REQUEST);
writeToLog(array($_REQUEST['auth']['domain'],$_REQUEST['data']['FIELDS']['ID']),"request");
if (!empty($_REQUEST)) {

     //$application->saveQueue();

    $application->start();

    //$application->manageEvent($_REQUEST['item'], $_REQUEST);

    // контроль потоков (типа очередь)
    try {
        $fileName = "queue/" . $_REQUEST['auth']['domain'] . "_" . $_REQUEST['event'] . "_" . $_REQUEST['data']['FIELDS']['ID'] . ".txt";

        /*if (!file_exists($fileName)) {
            throw new Exception('File not found.');
        }*/
        writeToLog($fileName,"fileName");

        $fp = fopen($fileName, "w+");
        if (!$fp) {
            throw new Exception('File open failed.');
        }
        if(flock($fp, LOCK_EX )) {

            $application->manageEvent($_REQUEST['item'], $_REQUEST);

            flock($fp, LOCK_UN);
        }

        fclose($fp);
        // unlink($fileName);

        // send success JSON

    } catch (Exception $error) {
        // send error message if you can
        $application->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
    }


}