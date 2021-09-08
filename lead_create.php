<?php
/**
 * Created by PhpStorm.
 * User: kostush
 * Date: 03.03.2020
 * Time: 11:37
 */
/** * Write data to log file. * * @param mixed $data * @param string $title * * @return bool */
function writeToLog1($data, $title = '') { $log = "\n------------------------\n";
$log .= date("Y.m.d G:i:s") . "\n"; $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
$log .= print_r($data, 1);
$log .= "\n------------------------\n";
file_put_contents(getcwd() . '/hook.log', $log, FILE_APPEND); return true;
}

function createLead ($params) {
   // $defaults = array('first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '');

        $defaults = $params;
        writeToLog1($params, 'webhook');

        $queryUrl = 'https://cremaprodotti.bitrix24.ua/rest/1/tazje1qbgluo0pgd/crm.lead.add.json';
        $queryData = http_build_query(array( 'fields' => array(
            "TITLE" => "SKK ".$params['comments']." ". $params['first_name'].' '.$params['last_name'].' '.$params['portal'],
            "NAME" => $params['first_name'],
            "LAST_NAME" => $params['last_name'],
            "STATUS_ID" => "NEW",
            "OPENED" => "Y",
            "ASSIGNED_BY_ID" => 10,
            "PHONE" => array(array("VALUE" => $params['phone'], "VALUE_TYPE" => "WORK" )),
            "EMAIL" => array(array("VALUE" => $params['email'], "VALUE_TYPE" => "WORK" )), ),
            'params' => array("REGISTER_SONET_EVENT" => "Y") ));
        $curl = curl_init();
        curl_setopt_array($curl,
            array( CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData, ));
        $result = curl_exec($curl); curl_close($curl);
        $result = json_decode($result, 1);
        writeToLog1($result, 'webhook result');
        if (array_key_exists('error', $result)) writeToLog1($result['error_description'],"Помилка при збереженні ліда: ") ;
        return $result;

}



