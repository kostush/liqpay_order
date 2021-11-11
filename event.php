<?php
require_once("tools.php");
require_once("db.php");
require_once("log.php");
require_once("liqpay.php");

function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("Log/event_".date("Y.m.d").".log", $log, FILE_APPEND);

    return true;
}

class CEvent
{

    public $arB24App;
    public $is_lead_mode = false;
    public $is_deal_mode = false;
    public $currentUser = 0;
    public $arAccessParams = array();
    public $b24_error = '';

    public $leadId=null;
    public $dealId=null;

    public $access_token;
    public $user;
    public $domain;
    public $method;
    public $application_token;
    public $arDealField = null;
    public $arLeadField = null;
    public $server_url = 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/callback.php';
    public $contact = array();
    public $phone;
    public $email;
    public $goods = array();

    public $item;


    public $arRatingUsers = array();


    public function prepareToInvoice($obB24){
        $contact = $this->contact;

        $arPhone =$contact['PHONE'];
        $arEmail = $contact['EMAIL'];

        foreach ($arPhone as $value){
            if (!empty($value['VALUE'])){
                // нужно проверить правильность формата номера

                $this->phone=$value['VALUE'];
               // writeToLog(  $this->phone,'  $this->phone');
                break;
            }
        }
        foreach ($arEmail as $value){
            if (!empty($value['VALUE'])){
                $this->email = $value['VALUE'];
                //writeToLog(  $this->email,'  $this->email');
                break;
            }
        }
        if ($this->item == "deal")
        {
            $deal_rows = $obB24->call("crm.deal.productrows.get",array('id' => $this->dealId));
            //writeToLog(    $deal_rows,'   $deal_rows');
            foreach ($deal_rows['result'] as $value) {
                $goods[] = array(
                    'amount' => $value['PRICE'],
                    'count'  => round($value['QUANTITY']),
                    'unit'   => $value['MEASURE_NAME'],
                    'name'   => $value['PRODUCT_NAME'],
                );

            }

        }
        elseif ($this->item == "lead")
        {
            $lead_rows = $obB24->call("crm.lead.productrows.get",array('id' => $this->leadId));
            //writeToLog(    $lead_rows,'   $lead_rows');
            foreach ($lead_rows['result'] as $value) {
                $goods[] = array(
                    'amount' => $value['PRICE'],
                    'count'  => round($value['QUANTITY']),
                    'unit'   => $value['MEASURE_NAME'],
                    'name'   => $value['PRODUCT_NAME'],
                );

            }

        }


        $this->goods = $goods;
        writeToLog(  $this->goods,'  $this->goods');

    }

    private function returnJSONResult ($answer) {

        ob_start();
        ob_end_clean();
        Header('Cache-Control: no-cache');
        Header('Pragma: no-cache');
        echo json_encode($answer);
        die();
    }

    public function  getDealContact($obB24,$id){
        $contact = $obB24->call("crm.contact.get",array('id' =>$id));
        $this->contact = $contact['result'];
        return $contact;

    }

    public function setDealField($result){
        $this->arDealField = $result;
    }

    public function setLeadFeild($result){
        $this->arLeadField = $result;
    }

    public function isFieldChanged($result_deal,$db_res){
        $old_field = $db_res['OLD_ROW_FIELD'];
        $new_field = $result_deal[$db_res['DEAL_LIQ_PAY']];
        if ($old_field==$new_field){
            die; // поле link не менялось, т.е. не менялась сумма для оплаты лик пей
        }

    }
    public function isStageChanged($result_deal,$db_res){
        $old_field = $db_res['OLD_ROW_FIELD'];
        $new_field = $result_deal[$db_res['DEAL_LIQ_PAY']];
        if ($old_field==$new_field){
            die; // поле link не менялось, т.е. не менялась сумма для оплаты лик пей
        }

    }

    public function checkPaidPeriod($obB24_result,$db_result){
        // проверяем оплаченный период
        return true; //перешли на подписку маркетплейсы
        $end_date = date("Y-m-d H:i:s",strtotime($db_result['END_DATE']));;
        $current_date = date("Y-m-d H:i:s");

        if( $current_date > $end_date){

                $im_user = $obB24_result->call( "im.notify", array(
                    "to" => $this->user,
                    "message" => "Скінчився період оплати. Було [color=red]сплачено до [/color]"
                        .$end_date ."     Liq Pay поле з ссилкою не оновлюється !      Запустіть додаток та натисніть 'Оплатити'",
                    "type" => "SYSTEM"

                ));
            //writeToLog( $end_date, " current_date > END_DATE");
            return false;

        }
        //writeToLog( $end_date, " current_date < END_DATE");
        return true;

    }

    public function saveDB($params,$event, $result_deal){
        global $db;

    }


    public function manageEvent($event, $params){
        global $db;


        $obB24 = new \Bitrix24\Bitrix24();
        $obB24->setAccessToken($params['auth']['access_token']);
        $obB24->setDomain($params['auth']['domain']) ;
        $obB24->setMemberId($params ['auth']['member_id']);

        switch ($event){
        case 'lead':// запрос полей лида ORDER

            $this->method = 'sale.order.get';
            $this->leadId = $params['data']['FIELDS']['ID'];

            $result_lead = $obB24->call($this->method, array('id' => $this->leadId));
            $this->setLeadFeild($result_lead);
            //writeToLog($result_lead,"result_order");

            //провера смены стадии

            $result_item = $db->getRow('SELECT * FROM `deal_field` '.
                'WHERE PORTAL = ?s  '.
                'AND ITEM =?s AND ITEM_ID=?s', $params['auth']['domain'],'lead',$params['data']['FIELDS']['ID']);
            writeToLog($result_item,"result_item");

            //запись в базу стадии заказа


            $old_stage_id = $result_item['OLD_ROW_FIELD'];




            $result_db = $db->getRow('SELECT * FROM `b24_liq_pay_key` AS lpk '.
            'LEFT JOIN `b24_portal_payment` AS pp ON pp.PORTAL = lpk.PORTAL '.
            'LEFT JOIN `b24_portal_reg` AS pr ON pr.PORTAL = pp.PORTAL '.
            'WHERE lpk.PORTAL = ?s  ', $params['auth']['domain']);




            //writeToLog($result_db,"result_db");
            // проверяем стадию лида  на соответсвие стадии для старта создания ссылки
            $lead_start = $result_db['LEAD_START'];
            $lead_finish = $result_db['LEAD_FINISH'];
            $lead_liq_pay= $result_db['LEAD_LIQ_PAY'];
            $lead_stage_id = $result_lead['result']['order']['statusId'];
            $contact_id =    $result_lead['result']['order']['clients']['0']['entityId'];
            $currency_id = $result_lead['result']['order']['currency'];

            $private_key= $result_db['PRIVAT_KEY'];
            $public_key = $result_db['PUBLIC_KEY'];
            $amount_db = $result_item['OLD_AMOUNT'];

            $avans_lead_id =  $result_db['AVANS_LEAD'];






            $method_link = $result_db['METHODLINK'];
            $method_bot = $result_db['METHODBOT'];
            $method_email = $result_db['METHODEMAIL'];




            if ($lead_stage_id != $lead_start) {
                writeToLog('стадия не равна стартовой - выход ');
                // обновим  стадию в БД
                try {


                    $res_db_new_stage = $db->query('UPDATE `deal_field` 
                                                    SET `OLD_ROW_FIELD` = ?s 
                                                    WHERE `PORTAL` = ?s AND  `ITEM` = ?s AND `ITEM_ID` = ?i',
                        $lead_stage_id,$params['auth']['domain'], $event, $params['data']['FIELDS']['ID']);

                  //  writetolog($res_db_new_stage, "res_db_new_stage");
                } catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }
               // writeToLog($res_db_new_stage, "после попытки записи стадии в БД");





                die;
            }elseif ($result_item['ACTUAL']===0){ // лид  уже оплачен
                $im_user = $obB24->call( "im.notify", array(
                    "to" => $this->user,
                    "message" => "Лід  № ".$this->dealId."[color=red] вже сплачений через Liq Pay [/color]- нова ссилка не генерується!",
                    "type" => "SYSTEM"

                ));

                die;
            }

            elseif ($lead_stage_id == $old_stage_id){
                 /*$im_user = $obB24->call( "im.notify", array(
                     "to" => $this->user,
                     "message" => "Статус Ліда  № ".$this->dealId."[color=red] НЕ менялся  [/color]- выход",
                     "type" => "SYSTEM"

                 ));*/
               // writeToLog("old_stage_id = result_item['OLD_ROW_FIELD'];");


                die;

            }

            // отменили проверку на изменение суммы
           /* elseif ($amount_db == $result_lead['result']['OPPORTUNITY']){
                writeToLog('сумма не менялась ');
                die;
            }*/


            else {

                // проверяем валюту лида на поддерживаемую Ликпеем ( грн, рубль, доллар, евро) - если не поддерживается  отправляем уведомление в Битрикс
                if (($currency_id != 'UAH') AND ($currency_id != 'USD') AND ($currency_id != 'EUR') AND ($currency_id != 'RUB')){

                    writeToLog('Валюта не равна грн, долл, евро, руб - выход ');
                    $im_user = $obB24->call( "im.notify", array(
                        "to" => $this->user,
                        "message" => "Валюта ліда [color=red]".$currency_id ."[/color] не належить до UAH, USD, EUR, RUB. Liq Pay ссилка не генерується! Змініть валюту.",
                        "type" => "SYSTEM"

                    ));

                    die;

                }
                //оплачено ли приложение ?
                if ($isPayed = $this->checkPaidPeriod($obB24, $result_db)) {
                    //язык клиента
                    if( ($currency_id == 'USD') OR ($currency_id == "EUR")) {
                        $language = 'en';
                        $order_text = 'Order';
                    }
                    elseif ($currency_id == 'RUB') {
                        $language = 'ru';
                        $order_text = 'Заказ';
                    }
                    else {
                        $language = 'uk';
                        $order_text = 'Замовлення';
                    }
                   // writeToLog(array($currency_id,$language),'валюта, язык ');

                    //$phone = $result_deal['result'][];



                    $description = $order_text.' № ' . $result_lead['result']['order']['id'];
                    // Если поле аванс заполнено - больше 0 - в лик пей передаем єту сумму
                    if ($result_lead['result'][$avans_lead_id] >0){
                        $ammount =  $result_lead['result']['order'][$avans_lead_id];
                        $description = $description.' Аванс';
                      //  writeToLog(array($ammount,$description),'сумма, описание ');

                    }
                    else{
                        $ammount =  $result_lead['result']['order']['price'];

                    }

                    // LEAD вместо ORDER
                    $order_id = $params['auth']['domain'] . "_LEAD_" . $result_lead['result']['order']['id'] . "_" . time();
                    $expired_date  = date("Y-m-d H:i:s",strtotime("+ 3 day"));

                    if (($method_link) OR ((!$method_link)AND(!$method_bot)AND(!$method_email))) {
                        $query = array(
                            'public_key' => $public_key,
                            'action'         => 'pay',
                            'amount'         => $ammount,
                            'currency'       => $currency_id,
                            'language'       => $language,
                            'description'    => $description,
                            'order_id'       => $order_id,
                            'version'        => '3',
                            'server_url'    => $this->server_url,
                            'expired_date'  => $expired_date
                        );
                        $json_string = json_encode($query);
                      //  writeToLog($json_string, ' for LINK json_string');
                        $liqpayData = base64_encode($json_string);
                        $signString = $private_key . $liqpayData . $private_key;
                        $signature = base64_encode(sha1($signString, true));
                        $link = "https://www.liqpay.ua/api/3/checkout?data=" . $liqpayData . "&signature=" . $signature;
                      //  writetolog($link,"link");

                        // shorten URL

                        $full_link = urlencode($link);
                        $json_short = file_get_contents('https://cutt.ly/api/api.php?key='.API_KEY_SHORTEN.'&short='.$full_link.'&name=LiqPay_'.time());


                        $data_short = json_decode ($json_short, true);
                        if ($data_short["url"]["status"] != 7){
                            writeToLog($data_short, 'data_short');

                        } else {
                            $link = $data_short["url"]["shortLink"];
                            writeToLog($data_short, 'data_short');

                        }
                    }


                    $liqpay = new LiqPay($public_key, $private_key); //для инвойса


                    //подготовка к инвойсу и телеграму
                    if (($method_bot) OR ($method_email)) {
                        //обработка если есть контакт у сделки
                        if ($contact_id) {
                            $contact = $this->getDealContact($obB24, $contact_id);
                            $this->prepareToInvoice($obB24);// уствнавливает this->phone ,email
                        }else { // не выбран контакт - предупреждение

                           /* $im_user = $obB24->call( "im.notify", array(
                                "to" => $this->user,
                                "message" => "[color=red]В ліді відсутній Контакт клієнта [/color] не можливо відправити Email чи повідомлення в Приват 24! Додайте Контакт.",
                                "type" => "SYSTEM"

                            ));*/
                           $result_order = $result_lead;
                           $order_properties = $result_order['propertyValues'];
                           foreach($order_properties as $value){
                               if (($value['code'] == 'PHONE') AND (!empty($value['value']))){
                                   $this->phone = $value['value'];
                                  // writeToLog(  $this->phone,'  $this->phone');

                               }
                               if (($value['code'] == 'EMAIL') AND (!empty($value['value']))){
                                   $this->email = $value['value'];
                                  // writeToLog(  $this->email,'  $this->email');

                               }
                           }
                           //if ($result_order)

                        /*   if ($result_lead['result']['HAS_PHONE'] == 'Y'){
                               $arPhone = $result_lead['result']['PHONE'];
                               foreach ($arPhone as $value){
                                   if (!empty($value['VALUE'])){
                                       // нужно проверить правильность формата номера

                                       $this->phone=$value['VALUE'];
                                       writeToLog(  $this->phone,'  $this->phone');
                                       break;
                                   }
                               }

                           }
                           if ($result_lead['result']['HAS_EMAIL'] == 'Y') {
                               $arEmail = $result_lead['result']['EMAIL'];
                               foreach ($arEmail as $value){
                                   if (!empty($value['VALUE'])){
                                       $this->email = $value['VALUE'];
                                       writeToLog(  $this->email,'  $this->email');
                                       break;
                                   }
                               }

                           }*/
                            $lead_rows = $obB24->call("crm.lead.productrows.get",array('id' => $this->leadId));
                            //writeToLog(    $lead_rows,'   $lead_rows');
                            foreach ($lead_rows['result'] as $value) {
                                $goods[] = array(
                                    'amount' => $value['PRICE'],
                                    'count'  => round($value['QUANTITY']),
                                    'unit'   => $value['MEASURE_NAME'],
                                    'name'   => $value['PRODUCT_NAME'],
                                );

                            }
                            $this->goods = $goods;
                            writeToLog(  $this->goods,'  $this->goods');






                            writeToLog('Контакт не выбран, берем данніе ліда ');

                        }


                        //writeToLog($liqpay, 'liqpay');
                        if  ($method_email) {
                            // если есть мыло - отпрвляем инвойс
                            if ($this->email) {
                                $res_invoice = $liqpay->api("request", array(
                                        'action'    => 'invoice_send',
                                        'version'   => '3',
                                        'email'     => $this->email,
                                        'amount'    => $ammount,
                                        'currency'  => $currency_id,
                                        'language'       => $language,
                                        'description'    => $description,
                                        'order_id'  => $order_id,
                                        'goods'     => $this->goods,
                                        'server_url'    => $this->server_url,
                                        'expired_date'  => $expired_date
                                    )
                                );
                                writeToLog($res_invoice,"результат инвойса");
                                if ($res_invoice->result != 'error'){
                                    $im_user_email = $obB24->call( "im.notify", array(
                                        "to" => $this->user,
                                        "message" => "Статус відправки інвойсу на Email [color=red]".$res_invoice->result ." [/color]",
                                        "type" => "SYSTEM"

                                    ));
                                }

                            }else{
                              //  writeToLog('Контакт  выбран, мыла нет ');
                                $im_user_email = $obB24->call( "im.notify", array(
                                    "to" => $this->user,
                                    "message" => "[color=red]В ліді відсутній Email Контакта  [/color] не можливо відправити Інвойс на Email! Додайте Email.",
                                    "type" => "SYSTEM"

                                ));

                            }
                        }

                        //для бота в телеграм
                        //если есть телефон - отправляем в мессенджер
                        if ($method_bot){
                            if($this->phone) {
                                $res_bot = $liqpay->api("request", array(
                                    'action'    => 'invoice_bot',
                                    'version'   => '3',
                                    'amount'    => $ammount,
                                    'currency'  => $currency_id,
                                    'language'       => $language,
                                    'description'    => $description,
                                    'order_id'  => ($order_id."*"),
                                    'phone'  => ($tel = preg_replace('/[^0-9]/', '', $this->phone)),
                                    'server_url'    => $this->server_url,
                                    'expired_date'  => $expired_date
                                ));
                                writeToLog($res_bot,"результат Бота");
                                $res_bot_string = $res_bot->result;
                                if ($res_bot->result != 'error'){
                                    $im_user_bot = $obB24->call( "im.notify", array(
                                        "to" => $this->user,
                                        "message" => "Статус відправки повідомлення в Приват24 - [color=red] ".$res_bot->result." [/color] ",
                                        "type" => "SYSTEM"

                                    ));
                                }


                                $tel = preg_replace('/[^0-9]/', '', $tel);
                            }else{
                              //  writeToLog('нет теефона ');
                                $im_user_bot = $obB24->call( "im.notify", array(
                                    "to" => $this->user,
                                    "message" => "[color=red]В ліді відсутній телефон Контакту  [/color] не можливо відправити повідомлення в Приват 24! Додайте телефон.",
                                    "type" => "SYSTEM"

                                ));
                            }
                        }



                    }

                    $html = $liqpay->cnb_form(array(
                        'action'         => 'pay',
                        'amount'         => $ammount,
                        'currency'       => $currency_id,
                        'language'       => $language,
                        'description'    => $description,
                        'order_id'       => $order_id,
                        'version'        => '3',
                        'expired_date'  => $expired_date
                    ));
                   // writeToLog($html,"html link");

                    //запись линк в поле сделки
                    $lead_liq_pay = "comments";
                    if (($method_link) OR ((!$method_link)AND(!$method_bot)AND(!$method_email))) {
                        $this->lead_link = $link;
                       if( $result_lead_update = $obB24->call('sale.order.update', array(
                            'id' => $result_lead['result']['order']['id'],
                            'fields' => array(
                                $lead_liq_pay => $link)
                        ))) {
                         //  writeToLog($result_lead_update, 'result_order_update');
                           $im_user = $obB24->call("im.notify", array(
                               "to" => $this->user,
                               "message" => "Liq Pay link сгенеровано [color=red]" . $link . " [/color]",
                               "type" => "SYSTEM"

                           ));
                       };

                    }

                    writeToLog($result_lead_update, 'result_lead_update');
                    writeToLog($result_db['ACTUAL'], 'result_db[\'ACTUAL\']');
                    // save DB deal
                    if ((!$result_item['ACTUAL']) OR ($result_item['ACTUAL'] == 1)) { //первый раз сущность обрабатывается или не опалчена в success
                        $actual = 1;
                        try {
                            $res_db = $db->query('INSERT INTO `deal_field` (PORTAL, ITEM, ITEM_ID, STATUS, PAYMENT_ID,	OLD_ROW_FIELD, OLD_AMOUNT, ACTUAL)
                                VALUES (?s, ?s, ?i,?s, ?i,?s, ?s,?i)' .
                                ' ON DUPLICATE KEY UPDATE `ITEM`=?s, `ITEM_ID`=?i, `STATUS`=?s, `PAYMENT_ID`=?i,`OLD_ROW_FIELD`=?s,`OLD_AMOUNT`=?s, `ACTUAL`=?i',
                                $params['auth']['domain'], $event, $params['data']['FIELDS']['ID'], 'initial', 0, $result_lead['result']['order']['statusId'], $result_lead['result']['order']['price'], 1,
                                $event, $params['data']['FIELDS']['ID'], 'initial', 0, $result_lead['result']['order']['statusId'], $result_lead['result']['order']['price'], $actual);
                        } catch (Exception $error) {
                            $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                        }
                    }


                } else {
                    // сообщиь пользователю о конце оплаты
                    // сообщиь пользователю о конце оплаты


                    die;


                };



                //****************

                //*****************

            }

            break;


        case 'deal':
            $this->method = 'crm.deal.get';
            $this->dealId = $params['data']['FIELDS']['ID'];

            $result_deal = $obB24->call($this->method, array('id' => $this->dealId));
            $new_stage_id = $result_deal['result']['STAGE_ID'];

            writeToLog($result_deal,"result_deal");
            writetolog($new_stage_id,"new_stage_id");
            //$this->setDealFeild($result_deal);

            $result_item = $db->getRow('SELECT * FROM `deal_field` '.
                'WHERE PORTAL = ?s  '.
                'AND ITEM =?s AND ITEM_ID=?s', $params['auth']['domain'],'deal',$params['data']['FIELDS']['ID']);







            //*********************

            $result_db = $db->getRow('SELECT * FROM `b24_liq_pay_key` AS lpk '.
                'LEFT JOIN `b24_portal_payment` AS pp ON pp.PORTAL = lpk.PORTAL '.
                'LEFT JOIN `b24_portal_reg` AS pr ON pr.PORTAL = pp.PORTAL '.
               // 'LEFT JOIN `deal_field` AS df ON df.PORTAL = pr.PORTAL '.
                'WHERE lpk.PORTAL = ?s ', $params['auth']['domain']);

          //  writeToLog($result_db,"result_db");

            // получаем даные сделки



            // проверка на изменение поля link
            //$this->isStageChanged($result_deal, $result_item); //если не менялось - выход





            $deal_start = $result_db['DEAL_START'];
            $deal_finish = $result_db['DEAL_FINISH'];
            $deal_liq_pay= $result_db['DEAL_LIQ_PAY'];

            $deal_stage_id = $result_deal['result']['STAGE_ID'];
            $deal_category_id =$result_deal['result']['CATEGORY_ID'];
            $contact_id =    $result_deal['result']['CONTACT_ID'];
            $currency_id = $result_deal['result']['CURRENCY_ID'];



            $private_key= $result_db['PRIVAT_KEY'];
            $public_key = $result_db['PUBLIC_KEY'];

            $amount_db = $result_item['OLD_AMOUNT'];

            $old_stage_id = $result_item['OLD_ROW_FIELD'];
          //  writetolog($result_item, "result_item");
          //  writeToLog($old_stage_id,"old_stage_id");

            $method_link = $result_db['METHODLINK'];
            $method_bot = $result_db['METHODBOT'];
            $method_email = $result_db['METHODEMAIL'];

            $avans_deal_id = $result_db['AVANS_DEAL'];


            //********запрос стадий п категориям
            if($result_category_stage_db = $db->getRow('SELECT * FROM `b24_category`  '.
                'WHERE PORTAL = ?s ',  $params['auth']['domain'])){

                $deal_category_start = $result_category_stage_db[$deal_category_id."_DEAL_START"];
                $deal_category_finish = $result_category_stage_db[$deal_category_id."_DEAL_FINISH"];
            }else {
                $deal_category_start = $deal_start;
                $deal_category_finish = $deal_finish;
            }
          //  writeToLog($result_category_stage_db," result_category_stage_db");


            // проверяем стадию сделки на соответсвие стадии для старта создания ссылки
            //writeToLog(array($deal_stage_id,$deal_category_start,$deal_category_id,$deal_category_id."_DEAL_START"));

            if ($deal_stage_id != $deal_category_start) {
                writeToLog('стадия не равна стартовой - выход ');
                writeToLog( "ДО ы попытки записи стадии в БД");



                // обновим  стадию в БД
                try {


                    $res_db_new_stage = $db->query('UPDATE `deal_field` 
                                                    SET `OLD_ROW_FIELD` = ?s 
                                                    WHERE `PORTAL` = ?s AND  `ITEM` = ?s AND `ITEM_ID` = ?i',
                                                    $deal_stage_id,$params['auth']['domain'], $event, $params['data']['FIELDS']['ID']);

                   // writetolog($res_db_new_stage, "res_db_new_stage");
                } catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }
               // writeToLog($res_db_new_stage, "после попытки записи стадии в БД");


                die;
            }elseif ($result_item['ACTUAL']===0){ // сделка уже оплачена
                $im_user = $obB24->call( "im.notify", array(
                    "to" => $this->user,
                    "message" => "Угода  № ".$this->dealId."[color=red] вже сплачена через Liq Pay [/color]- нова ссилка не генерується!",
                    "type" => "SYSTEM"

                ));

                die;
            }


                // проверка стадии для срабатівания ( если стадия изменялась - ок)
            elseif ($old_stage_id == $result_deal['result']['STAGE_ID']){
               // writeToLog('стадия  не менялась ');
                /*$im_user = $obB24->call( "im.notify", array(
                    "to" => $this->user,
                    "message" => "Статус Сделки  № ".$this->dealId."[color=red] НЕ менялся  [/color]- выход",
                    "type" => "SYSTEM"

                ));*/

                die;

            }


            /*elseif ($amount_db == $result_deal['result']['OPPORTUNITY']){
                writeToLog('сумма не менялась ');
                die;

            }*/
            else{
                // проверяем валюту сделки на поддерживаемую Ликпеем ( грн, рубль, доллар, евро) - если не поддерживается  отправляем уведомление в Битрикс

                if (($currency_id != 'UAH') AND ($currency_id != 'USD') AND ($currency_id != 'EUR') AND ($currency_id != 'RUB')){

                  //  writeToLog('Валюта не равна грн, долл, евро, руб - выход ');
                    $im_user = $obB24->call( "im.notify", array(
                        "to" => $this->user,
                        "message" => "Валюта угоди [color=red]".$currency_id ."[/color] не належить до UAH, USD, EUR, RUB. Liq Pay ссилка не генерується! Змініть валюту.",
                        "type" => "SYSTEM"

                    ));

                    die;

                }
                // проверка оплаченного периода
                if ($isPayed = $this->checkPaidPeriod($obB24, $result_db)) {
                    //язык клиента
                    if( ($currency_id == 'USD') OR ($currency_id == "EUR")) {
                        $language = 'en';
                        $order_text = 'Order';
                    }
                    elseif ($currency_id == 'RUB') {
                        $language = 'ru';
                        $order_text = 'Заказ';
                    }
                    else {
                        $language = 'uk';
                        $order_text = 'Замовлення';
                    }
                    writeToLog(array($currency_id,$language),'валюта, язык ');
                    //$phone = $result_deal['result'][];


                    $description =$order_text.' № ' . $result_deal['result']['ID'];
                    if ($result_deal['result'][$avans_deal_id] >0){
                        $ammount =  $result_deal['result'][$avans_deal_id];
                        $description = $description.' Аванс';

                    }
                    else{
                        $ammount =  $result_deal['result']['OPPORTUNITY'];
                    }


                    $order_id = $params['auth']['domain'] . "_DEAL_" . $result_deal['result']['ID'] . "_" . time();
                    $expired_date  = date("Y-m-d H:i:s",strtotime("+ 3 day"));


                    //Выбор метода уведомления
                    if (($method_link) OR ((!$method_link)AND(!$method_bot)AND(!$method_email))){
                        $query = array(
                            'public_key' => $public_key,
                            'action'         => 'pay',
                            'amount'         => $ammount,
                            'currency'       => $currency_id,
                            'language'       => $language,
                            'description'    => $description,
                            'order_id'       => $order_id,
                            'version'        => '3',
                            'server_url'    => $this->server_url,
                            'expired_date'  => $expired_date
                        );
                        $json_string = json_encode($query);
                       // writeToLog($json_string,' for LINK json_string');
                        $liqpayData = base64_encode($json_string);
                        $signString = $private_key.$liqpayData.$private_key;
                        $signature = base64_encode(sha1($signString,true));
                        $link="https://www.liqpay.ua/api/3/checkout?data=".$liqpayData."&signature=".$signature;
                    }

                    $liqpay = new LiqPay($public_key, $private_key);//для инвойса
                    //подготовка к инвойсу и телеграму
                    if (($method_bot) OR ($method_email)){
                        //обработка если есть контакт у сделки
                        if ($contact_id){
                            $contact = $this->getDealContact($obB24,$contact_id);
                            $this->prepareToInvoice($obB24);// уствнавливает this->phone ,email


                          //  writeToLog($liqpay,'liqpay');

                            if  ($method_email){
                                // если есть мыло - отпрвляем инвойс
                                if ($this->email){
                                    $res_invoice = $liqpay->api("request", array(
                                        'action'    => 'invoice_send',
                                        'version'   => '3',
                                        'email'     => $this->email,
                                        'amount'    => $ammount,
                                        'currency'  => $currency_id,
                                        'language'       => $language,
                                        'description'    => $description,
                                        'order_id'  => $order_id,
                                        'goods'     => $this->goods,
                                        'server_url'    => $this->server_url,
                                        'expired_date'  => $expired_date
                                        )
                                    );
                                     writeToLog($res_invoice,"результат инвойса");
                                     if ($res_invoice->result != 'error'){
                                         $im_user_email = $obB24->call( "im.notify", array(
                                             "to" => $this->user,
                                             "message" => "Статус відправки інвойсу на Email [color=red]".$res_invoice->result ." [/color]",
                                             "type" => "SYSTEM"

                                         ));
                                     }


                                } else{
                                    //writeToLog('Контакт  выбран, мыла нет ');
                                    $im_user_email = $obB24->call( "im.notify", array(
                                        "to" => $this->user,
                                        "message" => "[color=red]В угоді відсутній Email Контакта  [/color] не можливо відправити Інвойс на Email! Додайте Email.",
                                        "type" => "SYSTEM"

                                    ));

                                }

                            }

                            //для бота в телеграм
                            //если есть телефон - отправляем в мессенджер
                            if ($method_bot){
                                if($this->phone) {
                                    $res_bot = $liqpay->api("request", array(
                                        'action'    => 'invoice_bot',
                                        'version'   => '3',
                                        'amount'    => $ammount,
                                        'currency'  => $currency_id,
                                        'language'       => $language,
                                        'description'    => $description,
                                        'order_id'  => ($order_id."*"),
                                        'phone'  => ($tel = preg_replace('/[^0-9]/', '', $this->phone)),
                                        'server_url'    => $this->server_url,
                                        'expired_date'  => $expired_date
                                    ));
                                    writeToLog($res_bot,"результат Бота");
                                    $res_bot_string = $res_bot->result;
                                    if ($res_bot->result != 'error'){
                                        $im_user_bot = $obB24->call( "im.notify", array(
                                            "to" => $this->user,
                                            "message" => "Статус відправки повідомлення в Приват24 - [color=red] ".$res_bot->result." [/color] ",
                                            "type" => "SYSTEM"

                                        ));
                                    }


                                    $tel = preg_replace('/[^0-9]/', '', $tel);
                                }else{
                                   // writeToLog('нет теефона ');
                                    $im_user_bot = $obB24->call( "im.notify", array(
                                        "to" => $this->user,
                                        "message" => "[color=red]В угоді відсутній телефон Контакту  [/color] не можливо відправити повідомлення в Приват 24! Додайте телефон.",
                                        "type" => "SYSTEM"

                                    ));
                                }
                            }



                        } else { // не выбран контакт - предупреждение
                           // writeToLog('Контакт не выбран, а метод требует - выход ');
                            $im_user = $obB24->call( "im.notify", array(
                                "to" => $this->user,
                                "message" => "[color=red]В угоді відсутній Контакт клієнта [/color] не можливо відправити Email чи повідомлення в Приват 24! Додайте Контакт.",
                                "type" => "SYSTEM"

                            ));

                        }

                    }


                    $html = $liqpay->cnb_form(array(
                        'action'         => 'pay',
                        'amount'         => $ammount,
                        'currency'       => $currency_id,
                        'language'       => $language,
                        'description'    => $description,
                        'order_id'       => $order_id,
                        'version'        => '3',
                        'expired_date'  => $expired_date
                    ));
                   // writeToLog($html,"html link");

                    //запись линк в поле сделки
                    if (($method_link) OR ((!$method_link)AND(!$method_bot)AND(!$method_email))) {
                        $this->deal_link = $link;

                        if ($result_deal_update = $obB24->call('crm.deal.update', array(
                            'id' => $result_deal['result']['ID'],
                            'fields' =>array(
                                $deal_liq_pay =>$link)
                        ))){

                            $im_user = $obB24->call( "im.notify", array(
                                "to" => $this->user,
                                "message" => "Liq Pay link сгенеровано [color=red]".$link." [/color]",
                                "type" => "SYSTEM"

                            ));

                        };
                    }

                    writeToLog($result_deal_update, 'result_deal_update');
                   // writeToLog($result_db['ACTUAL'], 'result_db[\'ACTUAL\']');

                    // save DB deal
                        if ((!$result_item['ACTUAL']) OR ($result_item['ACTUAL']==1)) { //первый раз сущность обрабатывается или не опалчена в success
                            $actual = 1;
                            try {
                                $res_db = $db->query('INSERT INTO `deal_field` (PORTAL, ITEM, ITEM_ID, STATUS, PAYMENT_ID,	OLD_ROW_FIELD, OLD_AMOUNT, ACTUAL)
                                VALUES (?s, ?s, ?i,?s, ?i,?s, ?s,?i)' .
                                    ' ON DUPLICATE KEY UPDATE `ITEM`=?s, `ITEM_ID`=?i, `STATUS`=?s, `PAYMENT_ID`=?i,`OLD_ROW_FIELD`=?s,`OLD_AMOUNT`=?s, `ACTUAL`=?i',
                                    $params['auth']['domain'], $event, $params['data']['FIELDS']['ID'], 'initial', 0, $deal_stage_id, $result_deal['result']['OPPORTUNITY'], 1,
                                    $event, $params['data']['FIELDS']['ID'], 'initial', 0, $deal_stage_id, $result_deal['result']['OPPORTUNITY'], $actual);
                            } catch (Exception $error) {
                                $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                            }
                        }


                }
                else{
                    // сообщиь пользователю о конце оплаты
                    // сообщиь пользователю о конце оплаты


                    die;


                };

            }
            if ($deal_stage_id == $deal_category_finish){
               // $this->process_finish();
            }
            // конец обработки - стадия не входит в установленные для реакции


            break;
        default:;


    }



    }
    private function checkB24Auth()
    {

        // проверяем актуальность доступа
        $isTokenRefreshed = false;

        // $arAccessParams['access_token'] = '123';
        // $arAccessParams['refresh_token'] = '333';
      //  writetolog( $this->arAccessParams,"this->arAccessParams");
        $this->arB24App = getBitrix24($this->arAccessParams, $isTokenRefreshed, $this->b24_error);
        return $this->b24_error === true;
    }

    public function start () {


        $this->is_lead_mode = ($_REQUEST['item']=='lead'? true:false);
        $this->is_deal_mode = ($_REQUEST['item']=='deal'? true:false);
        $this->arAccessParams = $_REQUEST['auth'];
        $this->access_token = $_REQUEST['auth']['access_token'];
        $this->domain = $_REQUEST['auth']['domain'];
        $this->user = $_REQUEST['auth']['user_id'];
        $this->application_token = $_REQUEST['auth']['application_token'];
        $this->item = $_REQUEST['item'];


        //$this->b24_error = $this->checkB24Auth();
        //writeToLog($this->b24_error,"this->b24_error");
       // writetolog( $this->is_lead_mode,"this->is_lead_mode");
/*
            if ($this->b24_error != '') {
                    echo "B24 error: ".$this->b24_error;

                die;
            }*/
    }


}


$application = new CEvent();
//writeToLog((array($_REQUEST['auth']['domain'],$_REQUEST['item'],$_REQUEST['data'])),"request from sale - order save");
if (!empty($_REQUEST)) {

    $application->start();

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
        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
    }



}
