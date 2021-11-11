<?
require_once("tools.php");
require_once("db.php");
require_once("log.php");
//---------------------------

class Ccallback
{
    public $liqpay_callback= array();
    public $arAccessParams = array();
    public $arResultDB = array();
    public $arOrderID = array();
    public $data = array() ;
    public $arB24App;
    public $b24_error;
    public $signature;
    public $LPsignature;
    public $LPdata ;

    public $same_signature = false;
    public $item;
    public $arCategoryStage = array();
    public $category;


    public function saveAuth() {
        global $db;

        $res = $db->query(
            'INSERT INTO b24_portal_reg (`PORTAL`, `ACCESS_TOKEN`, `REFRESH_TOKEN`, `MEMBER_ID`) values (?s, ?s, ?s, ?s)'.
            ' ON DUPLICATE KEY UPDATE `ACCESS_TOKEN` = ?s, `REFRESH_TOKEN` = ?s, `MEMBER_ID` = ?s',
            $this->arB24App->getDomain(), $this->arB24App->getAccessToken(), $this->arB24App->getRefreshToken(), $this->arB24App->getMemberId(),
            $this->arB24App->getAccessToken(), $this->arB24App->getRefreshToken(), $this->arB24App->getMemberId()
        );

    }


    private function checkB24Auth() {

        // проверяем актуальность доступа
        $isTokenRefreshed = false;

        // $arAccessParams['access_token'] = '123';
        // $arAccessParams['refresh_token'] = '333';

        $this->arB24App = getBitrix24($this->arAccessParams, $isTokenRefreshed, $this->b24_error);
        writeToLog($this->arAccessParams,"checkB24Auth : this->arAccessParams");
        if($isTokenRefreshed)(
            $this->saveAuth()
        );
        return $this->b24_error === true;
    }

    private function returnJSONResult ($answer) {

        ob_start();
        ob_end_clean();
        Header('Cache-Control: no-cache');
        Header('Pragma: no-cache');
        echo json_encode($answer);
        die();
    }

    public function getPortalFromLiqPay($data){
        $order_id = $data['order_id'];
        $this->arAccessParams['domain'] = substr($order_id, 0, strpos($order_id, "_"));
    }

    public function setArResultDB($portal){
        global $db;
        $res = $db->getRow('SELECT * FROM `b24_liq_pay_key` AS lpk '.
            'LEFT JOIN `b24_portal_payment` AS pp ON pp.PORTAL = lpk.PORTAL '.
            'LEFT JOIN `b24_portal_reg` AS pr ON pr.PORTAL = pp.PORTAL '.
            'WHERE lpk.PORTAL = ?s  ',  $portal );
        writeToLog($res,"res from DB");

        $this->arResultDB = $res;

      /* if ( $res_category= $db->getRow('SELECT * FROM `b24_category` WHERE PORTAL = ?s  ',  $portal )){
           $this->arCategoryStage = $res_category;
            writeToLog($res_category,"res from DB category");

        }*/



        //return $res;


    }
    public function getData($data){
        $this->data = json_decode(json_decode( json_encode(base64_decode($data) ),true),true);
    }


    public function parseOrderId($order_id){
        //сделать проверку на непустое

        $first= strpos($order_id, "_");
        $this->arOrderID['portal'] = substr($order_id, 0, $first);

        $second = strpos($order_id, "_",$first + 1);
        $this->arOrderID['item'] =   substr($order_id, $first+1, $second -1-$first);// с позициии следующей за символом "_" длиной от Второго вхождения -1 и вычесть позицию первоговхлждения

        $third = strpos($order_id, "_",$second + 1);
        $this->arOrderID['id'] = substr($order_id, $second+1, $third -1-$second);// с позициии следующей за символом "_" и до конца строки
        writeToLog($this->arOrderID,'this->arOrderID');
    }



    public function validation($post){

        $this->getData($post['data']);
        $order_id = $this->data['order_id'];

        $this->parseOrderId($order_id);

        $this->setArResultDB($this->arOrderID['portal']);

        $private_key =  $this->arResultDB['PRIVAT_KEY'];
        $sign= base64_encode( sha1($private_key .  $post['data'] . $private_key , 1 ));

        if (!$sign == $this->LPsignature) {
            $this->same_signature = false;
            //die;// сообщиь о вызове не от Лик Пей = несовпадают подписи
        }
        else{
            $this->same_signature = true;
        }
    }


    public function b24ChangeStage(){

        $obB24Log = new \Bitrix24\Bitrix24Log\Bitrix24Log($this->arB24App); //объект ЛОГ


        $obB24 =  new \Bitrix24\Bitrix24(); //создаем объект для записи в Б24

        $obB24->setDomain($this->arAccessParams['domain']);

        $obB24->setAccessToken($this->arAccessParams['access_token']);
       // writeToLog($obB24,' $obB24  in  b24ChangeStage()');




        writeToLog($this->arOrderID['item'],'$this->arOrderID[\'item\'])');
        switch ($this->arOrderID['item']){
            case 'DEAL':
                // получаем категорию сделки
               $method = 'crm.deal.get';
               $params = array(
                   'id' => $this->arOrderID['id']
               );
               $result_deal =$obB24->call($method,$params);
                writeToLog($result_deal," result_deal");
               //*****************


                //$result_deal = $obB24->call($method,$params );
                //$this->setDealFeild($result_deal);

                //проверяем , есть ли в таблице категорий этот портал(т.е. переустановлена ли версия с напрвлениями сделок)
                global $db;

                if($result_category_stage_db = $db->getRow('SELECT * FROM `b24_category`  '.
                    'WHERE PORTAL = ?s ',  $this->arOrderID['portal'])){

                    $stage_id = $result_category_stage_db[$result_deal['result']['CATEGORY_ID'].'_DEAL_FINISH'];


                }else {
                    $stage_id = $this->arResultDB['DEAL_FINISH'];

                }
                writeToLog($result_category_stage_db," result_category_stage_db");

               /* try {
                    $res = $db->query('UPDATE `deal_field` SET STATUS = ?s, PAYMENT_ID =?i, ACTUAL = ?i '.
                        'WHERE (PORTAL =?s) AND  (ITEM = ?s) AND (ITEM_ID=?i) ', $this->data['status'],  $this->data['payment_id'],$actual,
                        $this->arOrderID['portal'],$this->arOrderID['item'], $this->arOrderID['id']);

                }
                catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }*/


                $method= 'crm.deal.update';
                $params = array(
                    'id' =>  $this->arOrderID['id'],
                    'fields' => array(
                        'STAGE_ID' => $stage_id  //$this->arCategoryStage[$result_deal['result']['CATEGORY_ID'].'_DEAL_FINISH'] //добавить поле LP Status
                    )
                );
                $result_deal_update = $obB24->call($method,$params);
                writeToLog($params,'$paramsdeal update');
                writeToLog($result_deal_update,'result deal update');

                // вывод уведомления
                $deal_result = $obB24->call('crm.deal.get',array('id' => $this->arOrderID['id'] )); //получаем ответственного
                    if($deal_result['error']){
                        writeToLog($deal_result,'error');
                    }
                    else{
                        $ASSIGNED_BY_ID = $deal_result['result']['ASSIGNED_BY_ID'];
                        writeToLog($ASSIGNED_BY_ID,'ASSIGNED_BY_ID');
                        // отправляем ответственному уведомление об оплате
                        $result_im = $obB24->call('im.notify',array(
                            'to' => $ASSIGNED_BY_ID,
                            'message' => 'Угода (Сделка) № '.$this->arOrderID['id'].
                            ' сплачена через Liq Pay. Сума '.$this->data['amount'].' '.$this->data['currency'].'. Payment_id в Вашому Liq Pay кабінеті = '.$this->data['payment_id'].
                                ' Статус = '.$this->data['status'],
                            'type' => 'SYSTEM'));
                        if ($result_im['error']){
                            writeToLog($result_im,'error');
                        }
                    }

                break;
            case "LEAD"://order
                $method= 'sale.order.update';
                $params = array(
                    'id' =>  $this->arOrderID['id'],
                    'fields' => array(
                        'statusId' => $this->arResultDB['LEAD_FINISH'] //добавить поле LP Status
                    )
                );
                try{
                    $result_lead_update = $obB24->call($method,$params);
                    writeToLog($params,'$params order  update');
                    writeToLog($result_lead_update,'result order update');
                    // вывод уведомления
                    $lead_result = $obB24->call('sale.order.get',array('id' => $this->arOrderID['id'] )); //получаем ответственного
                    if($lead_result['error']){
                        writeToLog($lead_result,'error');
                    }
                    else{
                        $ASSIGNED_BY_ID = $lead_result['result']['order']['responsibleId'];
                        writeToLog($ASSIGNED_BY_ID,'responsibleId');
                        // отправляем ответственному уведомление об оплате
                        $result_im = $obB24->call('im.notify',array(
                            'to' => $ASSIGNED_BY_ID,
                            'message' => 'Замовлення   № '.$this->arOrderID['id'].
                                ' сплачене через Liq Pay. Сума '.$this->data['amount'].'. Payment_id в Вашому Liq Pay кабінеті = '.$this->data['payment_id'].
                                ' Статус = '.$this->data['status'],
                            'type' => 'SYSTEM'));
                        if ($result_im['error']){
                            writeToLog($result_im,'error');
                        }
                    }
                }catch(Exception $e){
                    writeToLog([__FILE__,__LINE__,$e],'exception');
                }


                break;
            default:

        }


        //return $res_b24;
    }

    public function saveDB($actual){

        global $db;
        try {
            $res = $db->query('UPDATE `deal_field` SET STATUS = ?s, PAYMENT_ID =?i, ACTUAL = ?i '.
                'WHERE (PORTAL =?s) AND  (ITEM = ?s) AND (ITEM_ID=?i) ', $this->data['status'],  $this->data['payment_id'],$actual,
                $this->arOrderID['portal'],$this->arOrderID['item'], $this->arOrderID['id']);

        }
        catch (Exception $error) {
            $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
        }


    }

    private function getAuthFromDB() {

        $this->arAccessParams = prepareFromDB($this->arResultDB);// перекодировка в маленькие буквы - установка свойств

        $this->b24_error = $this->checkB24Auth(); //проверка авторизации на сторонре Битрикс
        writeToLog($this->b24_error,'getAuthFromDB() -если нет ошибки - ниже будет пусто ');
        if ($this->b24_error != '') {
            echo $this->b24_error;
          //  \CB24Log::Add('background auth error: '.$this->b24_error);
            writeToLog($this->b24_error,"  \CB24Log::Add('background auth error: this->b24_error");
            die;
        }

      //  \CB24Log::Add('background auth success!');
        writeToLog($this->b24_error," \CB24Log::Add('background auth success!');");

    }

    public function manageCallback($post){

        $this->validation($post);// проверка подлинности от лик пей  на совпадение подписей
            writeToLog($this->same_signature,"this->same_signature");
        if(!$this->same_signature){
            die;// сообщить о вызове не от Лик Пей = несовпадают подписи
        }

       // writeToLog($this->arResultDB,"this->arResultDB");
        writeToLog($this->data['status'],'this->data[\'status\']');

        $this->getAuthFromDB(); //плучение токенов атвризции из БД


        switch ($this->data['status']){

            case "success":
                // изменение стадии сделки или лида
                $this->b24ChangeStage();
                $actual = 0;
                //  // здесь нужно написать обработку по обновлению БД - внести по порталу- сделке - сумму, статус, дату , payment_id
                $this->saveDB($actual);

                break;
            case "wait_accept": // c клиента деньги списаны, но не приняты получателем из-за проблем с ключами - "не боевые"

                $this->b24ChangeStage();
                $actual = 0;
                //  // здесь нужно написать обработку по обновлению БД - внести по порталу- сделке - сумму, статус, дату , payment_id
                $this->saveDB($actual);
                break;

            case "error":
                //сообщить о неверных данных
                break;
            case "failure":
                // сообщить о неуспешном платеже
                break;
            default:
                //неизвестный ответ
        }



        //$this->getSignature($this->LPdata);
    }


    public function start()
    {
        $this->liqpay_callback = $_POST;
        $this->LPsignature = $_POST['signature'];
        $this->LPdata = $_POST['data'];
        writeToLog($this->LPdata,'start() end');
    }



}

$cb = new Ccallback();

if (!empty($_POST)) {
    writeToLog($_POST,'liqpay callback');
    $cb->start();

    $cb->manageCallback($_POST);

}







function writeToLog($data, $title = '') {
 $log = "\n------------------------\n";
 $log .= date("Y.m.d G:i:s") . "\n";
 $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
 $log .= print_r($data, 1);
 $log .= "\n------------------------\n";
 file_put_contents(getcwd() . '/callback.log', $log, FILE_APPEND);
 return true;
}