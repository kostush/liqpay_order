<?php
// Формироване кнопок оплаты через ликпей
require_once "liqpay.php";
function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("pay.log", $log, FILE_APPEND);

    return true;
}

$public_key = 'i48839519872';//i48839519872
$private_key = 'JQ3M8fkb154cMhDF2WKK4lmTdu1ZdnCkOXvxD4J2';//JQ3M8fkb154cMhDF2WKK4lmTdu1ZdnCkOXvxD4J2

$server_url = 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/liqpay_callback.php';
$result_url = 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/index.php';

$liqpay = new LiqPay($public_key, $private_key);
date_default_timezone_set("Europe/Kiev");

$today = date("Y-m-d H:i:s");
$order_id = $_REQUEST['DOMAIN']."_".$today;
$domain = $_REQUEST['DOMAIN'];

$description_pay= 'LiqPay off. Замовлення Магазину -  Оплата за місяць.';
$html_Pay = $liqpay->cnb_form(array(
    'action'         => 'pay',
    'amount'         => '200',
    'currency'       => 'UAH',
    'description'    => $description_pay,
    'order_id'       => $order_id,
    'version'        => '3',
    'server_url'     => $server_url,
    'result_url'     => $result_url,
    'product_url'    => $_REQUEST['DOMAIN'],
    'product_name'   => 'LiqPay off. Замовлення Магазину'
));
$description_sub= 'LiqPay off. Замовлення Магазину - Підписка.';
$html_Sub = $liqpay->cnb_form(array(
    'action'         => 'subscribe',
    'amount'         => '200',
    'currency'       => 'UAH',
    'description'    => $description_sub,
    'order_id'       => $order_id,
    'version'        => '3',
    'subscribe'            => '1',
    'subscribe_date_start' => $today,
    'subscribe_periodicity'=> 'month',
    'server_url'     => $server_url,
    'result_url'     => $result_url,
    'product_url'    => $_REQUEST['DOMAIN'],
    'product_name'   => 'LiqPay off Замовлення Магазину.'
));
// запрос даты оплаченного периода
/*global $db;
$row = $db->getRow('SELECT * FROM `b24_portal_payment`'.  'WHERE `PORTAL` = ?s' ,$domain );
writeToLog($db, "$ db");*/
$server = 'localhost:3306';
$username = 'kozakov_skk';
$password = 'NlpdhESkK';
$db =  'kozakov_skk_liq_pay_order';
$port =  3306;
$charset = 'utf8';

//
$conn = mysqli_connect($server, $username, $password, $db);
$sql_pay="SELECT * FROM b24_portal_payment  WHERE PORTAL = '$domain' ";
    writeToLog($sql_pay, "запрос по дате оплаты");
$res= mysqli_query($conn,$sql_pay);
$row = mysqli_fetch_assoc($res);
writeToLog($row, "ответ по дате оплаты");
$html_data=date("d-m-Y");//,strtotime("+ 1 months"));
if (!$row){
    $html_data= date("d-m-Y",strtotime("+ 1 months"));

}
else{

    $html_data= date("d-m-Y ",strtotime($row['END_DATE']));;
}
mysqli_free_result($res);
mysqli_close($conn);
?>


