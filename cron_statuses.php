<?php
require_once("tools.php");
require_once("log.php");
require_once( __DIR__ . "/src/Delivery/NovaPoshtaApi2.php");

function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("event_logs/cron_statuses_".date('Y.m.d').".log", $log, FILE_APPEND);

    return true;
}

class CCron
{
    public $auth = [];
    public $portal_from_db;
    public $actual;
    public $arAccessParams = array();
    private $b24_error = '';
    public $arB24App;
    public $arPortalFromDB;
    public $arDealFromDB;
    public $arTrackNP;
    public $ttnStatusCode;
    public $ttnStatus;
    public $ttnScheduledDeliveryDate;
    public $ttnDocumentCost;
    public $newDealStage;
    public $arrayStopStatus = array("3","9","10","11","102","103","105","106","108");

    public $isNpError;


    public function trackTTN(){
        $this->isNpError = false;
        $api_keys_array = explode("|", $this->arPortalFromDB['NP_API_KEY']);
        $np = new \LisDev\Delivery\NovaPoshtaApi2($api_keys_array[0]);

        $this->setArTrackNP($np->documentsTracking($this->arDealFromDB['TTN'])) ;//sdk NP


        writeToLog($np, "np");
        writeToLog($this->arTrackNP, "this->arTrackNP");

        if ( $this->arTrackNP['errors']){
            $this->isNpError = true;
            return;

        }
        $this->ttnStatus = $this->arTrackNP['data']['0']['Status'];
        $this->ttnStatusCode = $this->arTrackNP['data']['0']['StatusCode'];
        $this->ttnScheduledDeliveryDate = ($this->arTrackNP['data']['0']['ScheduledDeliveryDate'] ? $this->arTrackNP['data']['0']['ScheduledDeliveryDate'] : "");
        $this->ttnDocumentCost = ($this->arTrackNP['data']['0']['DocumentCost'] ? $this->arTrackNP['data']['0']['DocumentCost'] : "");

    }
    public function setArTrackNP($array){
        $this->arTrackNP = $array;
    }

    public function setArDealFromDB($array){
        $this->arDealFromDB = $array;
        //writeToLog($this->arDealFromDB, "this->arDealFromDB");
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

    public function saveDealDB(){
        global $db;

        try {
            $result_insert = $db->query('INSERT INTO stage_status (`PORTAL`, `DEAL_ID`, `DEAL_STAGE`,`TTN`,`STATUS_NP`,`STATUS_NP_TEXT`,`ACTUAL`) values (?s, ?i, ?s,?s,?s,?s,?s)' .
                ' ON DUPLICATE KEY UPDATE `DEAL_STAGE` =?s,`TTN`= ?s,`STATUS_NP` =?s,`STATUS_NP_TEXT`=?s,`ACTUAL` =?s',


                $this->arDealFromDB['PORTAL'], $this->arDealFromDB['DEAL_ID'], $this->newDealStage, $this->arDealFromDB['TTN'], $this->ttnStatusCode, $this->ttnStatus, $this->actual,
                $this->newDealStage, $this->arDealFromDB['TTN'], $this->ttnStatusCode, $this->ttnStatus, $this->actual
            );


            writetolog($result_insert, "result_insert to DB");// лид в моем портале

        } catch (Exception $error) {
            $this->saveError(array('status' => 'error', 'result' => $error->getMessage()));
        }

    }
    public function checkActualStatus(){
        // проверяем актуальность дальнейших запросов по этой ТТН (если статус ноовой почты входит в массив "забрал, отказ"- дальше проверять неактуально
        if (in_array($this->ttnStatusCode,$this->arrayStopStatus)){
            $this->actual = '0';
        }
        else{
            $this->actual = '1';

        }
        writeToLog($this->isNpError,"this->isNpError");
        if ($this->isNpError) {

            $this->actual = '0';

        }


    }

    private function checkB24Auth()
    {

        // проверяем актуальность доступа
        $isTokenRefreshed = false;

        // $arAccessParams['access_token'] = '123';
        // $arAccessParams['refresh_token'] = '333';

        $this->arB24App = getBitrix24($this->arAccessParams, $isTokenRefreshed, $this->b24_error);
        if ($isTokenRefreshed){
            $this->saveAuth();
        }
        return $this->b24_error ;
    }

    public function saveError($array)
    {
        writeToLog($array, "Массив ошибок записи сделки в БД  ");
    }


    public function getPortalFromDB ($portal){
        global $db;
        writeToLog($portal," portal при запуске getPortalFromDB");
        $this->arPortalFromDB = $db->getRow("SELECT * FROM  b24_portal_reg AS bpr 
                                LEFT JOIN  b24_np_statuses AS bns ON bns.PORTAL = bpr.PORTAL
                                LEFT JOIN  b24_portal_payment AS bpp ON bpp.PORTAL = bns.PORTAL
                                WHERE bpr.PORTAL= ?s", $portal
        );
       // writeToLog( $this->arPortalFromDB," this->arPortalFromDB");

    }

    public function GetAuthFromDB()
    {

        $this->arAccessParams = prepareFromDB($this->arPortalFromDB);

    }


    public function start()
    {
        global $db;
        $db_stage_status = $db->getAll('SELECT * FROM stage_status 
                                WHERE ACTUAL=1');

        writeToLog($db_stage_status, "db_stage_status");
        $old_domain = null;
        $new_auth = null;

        foreach ($db_stage_status as $row) {
            writeToLog($row, '$row');
            $this->setArDealFromDB($row);
            writeToLog(array($old_domain, $this->arDealFromDB['PORTAL']),"old domain, this->arDealFromDB['PORTAL']");

            if ($old_domain != $this->arDealFromDB['PORTAL']) {

                $this->getPortalFromDB($this->arDealFromDB['PORTAL']);

                writetolog($this->arPortalFromDB, "this -> arPortalFromDB - запрос из БД ");

                if($this->arPortalFromDB['PORTAL']==""){ // переименовали портал

                   //$old_domain = $this->arDealFromDB['PORTAL'];
                    continue;
                }

                $this->GetAuthFromDB();
                writetolog($this->arAccessParams, "this -> arAccessParam - запрос из БД ");

            }
            // Nova Poshta
            $this->trackTTN();
            $this->checkActualStatus(); // ПРоверяем, не попал ли статус в финальные - когда дальше нет смысла проверять



            //********запрос стадий по категориям
            $this->b24_error = $this->checkB24Auth();
            while ($this->b24_error =='QUERY_LIMIT_EXCEEDED') { //цикл с паузой 1 сек при первышении лимита обращений в Битрикс24 50 в сек
                sleep(1);
                writeToLog($this->b24_error,"Ошибка авторизации в Б24 - QUERY_LIMIT_EXCEEDED");
                $this->b24_error = $this->checkB24Auth();
            }
            if ($this->b24_error != '') {
                 writeToLog($this->b24_error,"ОШибка авторизации в Б24");


                 if (($this->b24_error =='Application not installed')
                         OR ($this->b24_error =='Wrong authorization data')
                         OR ($this->b24_error =='Portal was deleted')
                         OR ($this->b24_error =='Not found'))
                        // OR ($this->b24_error =='Access denied'))
                     {

                     $this->actual = false;

                 }
                 $this->newDealStage = $this->arDealFromDB['DEAL_STAGE'];// сохраняем старое значение стадии
                 $this->saveDealDB();
                 continue; // цикл перебора сделок продолжить на новую итерацию
            }

            $method = 'crm.deal.get';
            $result_deal = $this->arB24App->call($method, array('id' => $this->arDealFromDB['DEAL_ID']));
            writeToLog($result_deal, "result_deal from b24");
            //$category_id = $result_deal['result']['CATEGORY'];

            if ($result_category_stage_db = $db->getRow('SELECT * FROM `b24_category`  ' .
                'WHERE PORTAL = ?s ', $this->arDealFromDB['PORTAL'])) {
                //$category = $result_deal['result']['CATEGORY_ID'];
                $this->newDealStage = $result_category_stage_db[$result_deal['result']['CATEGORY_ID'] . "_" . $this->ttnStatusCode];

                // $deal_category_start = $result_category_stage_db[$deal_category_id . "_DEAL_START"];
                // $deal_category_finish = $result_category_stage_db[$deal_category_id . "_DEAL_FINISH"];

            } else { // для версии 1 где не было категорий сделок
                $this->newDealStage = $this->arPortalFromDB [$this->ttnStatusCode];

                //$deal_category_start = $deal_start;
                //$deal_category_finish = $deal_finish;
            }

            writeToLog($result_category_stage_db, " result_category_stage_db");
            writeToLog( $this->newDealStage, " this->newDealStage");

            $deal_np_status_id = $this->arPortalFromDB ['DEAL_FIELD_NOVAPOSHTA_STATUS'];
            $deal_np_status_code_id = $this->arPortalFromDB ['DEAL_FIELD_NOVAPOSHTA_STATUS_CODE'];
            $deal_np_ScheduledDeliveryDate_id = $this->arPortalFromDB ['DEL_DATA_ID'];
            $deal_np_DocumentCost_id = $this->arPortalFromDB ['DEL_COST_ID'];
            $deal_np_ttn_field_id = $this->arPortalFromDB ['TTN_FIELD_ID'];


                // Запись в Б24
                // меняем стадию сделки (если нужно) и значение полей сделки со статусами НП


                if ($this->newDealStage !== 'NO') {
                    $fields = array(
                            "STAGE_ID" => $this->newDealStage,
                            $deal_np_status_id => $this->ttnStatus,
                            $deal_np_status_code_id => $this->ttnStatusCode,
                            $deal_np_ScheduledDeliveryDate_id => $this->ttnScheduledDeliveryDate,
                            $deal_np_DocumentCost_id => $this->ttnDocumentCost
                    );

                } else {
                    $fields = array(
                        $deal_np_status_id => $this->ttnStatus,
                        $deal_np_status_code_id => $this->ttnStatusCode,
                        $deal_np_ScheduledDeliveryDate_id => $this->ttnScheduledDeliveryDate,
                        $deal_np_DocumentCost_id => $this->ttnDocumentCost
                    );
                }

                try{
                    $result_deal_update = $this->arB24App->call('crm.deal.update', array(
                        'id' => $result_deal['result']['ID'],
                        'fields' => $fields  ));

                    // Запись в БД
                    $this->saveDealDB();
                }
                catch (Exception $error) {
                    $this->saveError(array('status' => 'error BX24 update', 'result' => $error->getMessage()));
                }

                writeToLog($result_deal_update,"result_deal_update");


            $old_domain = $this->arDealFromDB['PORTAL'];

        };

    }
}

$application = new CCron();
writeToLog($_REQUEST,"request");
 $application->start();







