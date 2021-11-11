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
        writeToLog(json_encode($answer),"returnJSONResult");
        die();
    }

    private function getYesterday() {
        $result = new DateTime();
        $result->add(DateInterval::createFromDateString('yesterday'));
        // return $result->format('Y-m-d');
        return '2015-09-19';
    }

    private function getUsers (){
        $yesterday = $this->getYesterday();

        global $db;

        $res = $db->getAll('SELECT users.*, rating.ID as ID_RATING_ITEM, rating.RATE_SUM, rating.RATE_DATE '.
            'FROM `b24_users` as users '.
            'LEFT JOIN `b24_ratings` as rating ON (rating.ID_USER = users.ID AND (rating.RATE_DATE = ?s OR rating.RATE_DATE IS NULL) )'.
            'WHERE users.`PORTAL` = ?s', $yesterday, $this->arAccessParams['domain']);

        return $res;

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
                    $res = $db->getRow('SELECT * FROM `b24_liq_pay_key` AS lpk '.
                        'LEFT JOIN `b24_category` AS cat ON cat.PORTAL = lpk.PORTAL '.
                        'WHERE lpk.PORTAL = ?s ',  $params['authParams']['domain']);
                    //writeToLog($res,'res SELECT');
                    $this->returnJSONResult(array('status' => 'success', 'result' => $res));

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
                        $res = $db->query('INSERT INTO b24_liq_pay_key (`PORTAL`, `PUBLIC_KEY`,`PRIVAT_KEY`,`LEAD_LIQ_PAY`,`LEAD_START`, `LEAD_FINISH`, `DEAL_LIQ_PAY`,`DEAL_START`,`DEAL_FINISH`,`DEAL_LIQ_PAY_LANG`,
                                            `METHODLINK`,`METHODBOT`,`METHODEMAIL`,`AVANS_LEAD`,`AVANS_DEAL`)   
                                         values (?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?p, ?s, ?s, ?s, ?s)'.
                                        ' ON DUPLICATE KEY UPDATE `PUBLIC_KEY` = ?s, `PRIVAT_KEY` = ?s,`LEAD_LIQ_PAY` = ?s,`LEAD_START` = ?s, `LEAD_FINISH` = ?s, `DEAL_LIQ_PAY` = ?s,`DEAL_START` = ?s, `DEAL_FINISH` = ?s, `DEAL_LIQ_PAY_LANG` = ?s,
                                        `METHODLINK`= ?s,`METHODBOT` =?s,`METHODEMAIL`=?s, `AVANS_LEAD`=?s,`AVANS_DEAL`=?s',
                            $params['authParams']['domain'],$params['data']['public_key'],$params['data']['privat_key'],
                            $params['data']['lead_liq_pay_link_field_id'],$params['data']['leadStart'],$params['data']['leadFinish'],
                            $params['data']['deal_liq_pay_link_field_id'],$params['data']['dealStart'],$params['data']['dealFinish'],$params['data']['deal_liq_pay_language_id'],
                            $params['data']['methodLink'],$params['data']['methodBot'],$params['data']['methodEmail'],$params['data']['avans_lead_id'],$params['data']['avans_deal_id'],
                            $params['data']['public_key'],$params['data']['privat_key'],
                            $params['data']['lead_liq_pay_link_field_id'],$params['data']['leadStart'],$params['data']['leadFinish'],
                            $params['data']['deal_liq_pay_link_field_id'],$params['data']['dealStart'],$params['data']['dealFinish'],$params['data']['deal_liq_pay_language_id'],
                            $params['data']['methodLink'],$params['data']['methodBot'],$params['data']['methodEmail'],$params['data']['avans_lead_id'],$params['data']['avans_deal_id']);



                    }
                    catch (Exception $error) {
                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                    }
                    // Создаем колонки таблицы БД для каждой новой категории , если их нет


                    // запишем данные по оплате
                    try {
                        $today = date("Y-m-d H:i:s");
                        $end_date = date("Y-m-d H:i:s",strtotime($today."+ 1 months"));
                        $res = $db->query('insert into b24_portal_payment (`PORTAL`, `ACTION_PB`, `PAIMENT_ID`,`STATUS_PB`,`ORDER_ID`, `DESCRIPTION`, `AMOUNT`,`CURRENCY`, `CREATE_DATE`, `END_DATE`,`PRODUCT_NAME`) 
                                                values (?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s)'.
                                                ' ON DUPLICATE KEY UPDATE `DESCRIPTION`=?s',
                            $params['authParams']['domain'],'test','test','test','test','test','0','UAH',$today,$end_date,'test','test');
                         // $this->returnJSONResult(array('status' => 'success', 'result' => ''));
                    }
                    catch (Exception $error) {
                        $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                    }



                $this->saveAuth();
                $this->returnJSONResult(array('status' => 'success', 'result' => ''));

                break;

            case 'get_users':

                $res = $this->getUsers();

                if (count($res) == 0) $this->returnJSONResult(array('status' => 'error', 'result' => 'no users'));
                else $this->returnJSONResult(array('status' => 'success', 'result' => $res));

                break;

            case 'update_rating':

                try {

                    $res = $db->query('UPDATE `b24_ratings` SET RATE_SUM = ?s '.
                        'WHERE ID = ?i ', $params['sum'], $params['id']);
                    $this->returnJSONResult(array('status' => 'success', 'result' => ''));
                }
                catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }

                break;

            case 'add_rating':

                $res = $db->getRow('SELECT ID FROM `b24_users` '.
                    'WHERE `PORTAL` = ?s AND ID_USER = ?i ', $params['domain'], $params['id_user']);

                $portal_user_id = $res['ID'];

                try {

                    $res = $db->query('INSERT INTO `b24_ratings` (ID_USER, RATE_DATE, RATE_SUM) VALUES (?i, ?s, ?s)',
                        $portal_user_id, $params['rate_date'], $params['sum']);
                    $this->returnJSONResult(array('status' => 'success', 'result' => ''));
                }
                catch (Exception $error) {
                    $this->returnJSONResult(array('status' => 'error', 'result' => $error->getMessage()));
                }

                break;

            case 'uninstall':

                \CB24Log::Add('uninstall 1: '.print_r($_REQUEST, true));

                break;

            default:
                $this->returnJSONResult(array('status' => 'error', 'result' => 'unknown operation'));
        }
    }

    public function processBackgroundData () {
        $yesterday = $this->getYesterday();
        echo "Background Data ".$yesterday."<br/>";

        $users = $this->getUsers();
        $arPortalUsers = array();
        foreach ($users as $arUser)
            $arPortalUsers[$arUser["ID_USER"]] = $arUser;

        function cmp($a, $b)
        {
            if ($a["RATE_SUM"] == $b["RATE_SUM"]) {
                return 0;
            }
            return ($a["RATE_SUM"] > $b["RATE_SUM"]) ? -1 : 1;
        }

        uksort($arPortalUsers, "cmp");

        $arDealFilter = array(
            "CLOSED" => 'Y'
            /*,
            ">=DATE_MODIFY": yesterday,
            "<DATE_MODIFY": today */
        );

        $obB24Batch = new \Bitrix24\Bitrix24Batch\Bitrix24Batch($this->arB24App);

        $arUserIDs = array();
        foreach ($arPortalUsers as $arUser)
            $arUserIDs[] = $arUser["ID_USER"];

        $obB24Batch->addUserListCall('USERS',
            array("ID" => $arUserIDs)
        );

        foreach ($arPortalUsers as $arUser) {
            $arDealFilter["ASSIGNED_BY_ID"] = $arUser["ID_USER"];
            $obB24Batch->addDealListCall($arUser["ID_USER"],
                array("DATE_CREATE" => "ASC"),
                array("ID", "TITLE", "OPPORTUNITY", "ASSIGNED_BY_ID"),
                $arDealFilter
            );
        }

        $res = $obB24Batch->call();

        $arDealSums = array();

        foreach ($res as $id_user => $arResult) {
            if ($id_user == 'USERS') {
                foreach ($arResult["data"] as $arUser) {
                    $arPortalUsers[$arUser["ID"]]["FIRST_NAME"] = $arUser["NAME"];
                    $arPortalUsers[$arUser["ID"]]["LAST_NAME"] = $arUser["LAST_NAME"];
                    $arPortalUsers[$arUser["ID"]]["SECOND_NAME"] = $arUser["SECOND_NAME"];
                    $arPortalUsers[$arUser["ID"]]["PERSONAL_PHOTO"] = $arUser["PERSONAL_PHOTO"];
                }
            }
            else {
                $arDealSums[$id_user] = 0;
                foreach ($arResult["data"] as $arDeal) $arDealSums[$id_user] += $arDeal["OPPORTUNITY"];
            }
        }

        echo "<pre>";
        print_r($arDealSums);
        print_r($arPortalUsers);
        echo "</pre>";

        global $db;
        foreach ($arPortalUsers as $arUser) {

            try {

                if (isset($arUser["ID_RATING_ITEM"])) {
                    $res = $db->query('UPDATE `b24_ratings` SET RATE_SUM = ?s WHERE ID = ?i', $arDealSums[$arUser["ID_USER"]], $arUser["ID_RATING_ITEM"]);

                }
                else {
                    $res = $db->query('INSERT INTO `b24_ratings` (ID_USER, RATE_DATE, RATE_SUM) VALUES (?i, ?s, ?s)',
                        $arUser["ID"], $yesterday, $arDealSums[$arUser["ID_USER"]]);
                }

            }
            catch (Exception $error) {
                \CB24Log::Add('background save rating error: '.$error->getMessage());
                echo $error->getMessage();
                die;
            }

        }

        $rating = 1;
        // исходная картинка
        $srcPath = IMAGE_DIR.'rating.jpeg';

        echo "source: ".$srcPath."</br>";
        // получить размеры изображений
        list($newWidth, $newHeight) = getimagesize($srcPath);
        $srcPath = imagecreatefromjpeg($srcPath);

        // создать обьединенное изображение
        $out = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($out, $srcPath, 0, 0, 0, 0, $newWidth, $newHeight, $newWidth, $newHeight);

        $rating_list = '';
        foreach ($arPortalUsers as $indexUser => $arUser) {
            if ($rating <= 3) {

                echo "rating ".$rating.": ".$arUser["PERSONAL_PHOTO"]."</br>";
                $rating_list .= "<li><b>".$arUser["FIRST_NAME"]." ".$arUser["LAST_NAME"]."</b> с результатом ".number_format($arDealSums[$indexUser], 0, ',', ' ')." руб.";
                putPhoto($out, $arUser["PERSONAL_PHOTO"], $rating);
                $rating++;
            }
            else break;
        }

        // imagecopyresampled($out, $userImage, 165, $y_offset, 0, 0, 70, $oldHeight / $k, $oldWidth, $oldHeight);

        // сохранить результат
        $rating_image = IMAGE_DIR.$this->arB24App->getDomain().'.jpeg';
        echo "image: ".$rating_image;
        imagejpeg($out, $rating_image);
        imagedestroy($out);

        $obB24Log = new \Bitrix24\Bitrix24Log\Bitrix24Log($this->arB24App);
        $imageData = base64_encode(file_get_contents($rating_image));

        $message = 'Сегодня в рейтинге лидируют: <ul>'
            .$rating_list
            .'</ul>Поздравляем!'

        ;

        echo $message;

        $obB24Log->add($message, '', array(),
            array(array("rating".date("Ymd").".jpeg", $imageData)));

    }

    public function getData () {

        $obB24User = new \Bitrix24\Bitrix24User\Bitrix24User($this->arB24App);
        $arCurrentB24User = $obB24User->current();
        $this->currentUser = $arCurrentB24User["result"]["ID"];

        $users = $this->getUsers();

        $arDealFilter = array(
            "ASSIGNED_BY_ID" => $this->currentUser,
            "CLOSED" => 'Y'/*,
			">=DATE_MODIFY": yesterday,
			"<DATE_MODIFY": today */
        );

        $obB24Batch = new \Bitrix24\Bitrix24Batch\Bitrix24Batch($this->arB24App);
        $obB24Batch->addDealListCall(0,
            array("DATE_CREATE" => "ASC"),
            array("ID", "TITLE", "OPPORTUNITY", "ASSIGNED_BY_ID"),
            $arDealFilter
        );

        $arUserIDs = array();
        foreach ($users as $arUser)
            $arUserIDs[] = $arUser["ID_USER"];

        $obB24Batch->addUserListCall(1,
            array("ID" => $arUserIDs)
        );

        $res = $obB24Batch->call();

        foreach ($users as $key => $arUser)
            $this->arRatingUsers[$arUser["ID_USER"]] = $arUser;

        $this->currentRating = isset($this->arRatingUsers[$this->currentUser]["ID_RATING_ITEM"]) ? $this->arRatingUsers[$this->currentUser]["ID_RATING_ITEM"] : 0;

        foreach ($res[1]["data"] as $arUser)
            $this->arRatingUsers[$arUser["ID"]] = array_merge(
                $this->arRatingUsers[$arUser["ID"]],
                array(
                    "FIRST_NAME" => $arUser["NAME"],
                    "LAST_NAME" => $arUser["LAST_NAME"],
                    "SECOND_NAME" => $arUser["SECOND_NAME"],
                    "PERSONAL_PHOTO" => $arUser["PERSONAL_PHOTO"])
            );

        $new_deal_sum = 0;

        foreach ($res[0]["data"] as $arDeal)
            $new_deal_sum += $arDeal["OPPORTUNITY"];

        $this->arRatingUsers[$this->currentUser]["RATE_SUM"] = $new_deal_sum;

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
            writeToLog($this->b24_error,"this->b24_error");

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


    $application->start();
    writeToLog($application->is_ajax_mode,"application->is_ajax_mode");

    if ($application->is_ajax_mode) $application->manageAjax($_REQUEST['operation'], $_REQUEST);
    else {
        if ($application->is_background_mode) $application->processBackgroundData();
        //else $application->getData();
    }
}
?>/