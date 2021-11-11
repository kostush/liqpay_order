<?php
require_once("tools.php");
require_once("log.php");
require_once("liqpay.php");
//require "include/LiqPay.php";
function writeToLog($data, $title = '')
{

    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s")."\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";

    file_put_contents("liqpay.log", $log, FILE_APPEND);

    return true;
}
/**
 * Created by PhpStorm.
 * User: kostush
 * Date: 9/22/19
 * Time: 4:53 PM
 */
$public_key = 'i48839519872';//i48839519872
$private_key = 'JQ3M8fkb154cMhDF2WKK4lmTdu1ZdnCkOXvxD4J2';

// альтернатива
$server = 'localhost:3306';
$username = 'kozakov_skk';
$password = 'NlpdhESkK';
$dbase =  'kozakov_skk_liq_pay';
$port =  3306;
$charset = 'utf8';
//************** для liqpay_callback

writeToLog($_POST,"Post");
$signature = $_POST['signature'];
$data = $_POST['data'];

$sign = base64_encode( sha1(
    $private_key .
    $data .
    $private_key
    , 1 ));
$data_array = json_decode(base64_decode($data),true);


if ($sign !== $signature) {


    $data_log['sign'] = $sign;
    $data_log['signature'] = $signature;
    writeToLog($data_log, $title = 'Liq_pay Callback - Sign <> Signature');
    echo "Ответ не от Лик Пей - подписи не совпадают";
    die;
}
else {

    writeToLog($data_array, $title = 'Liq_pay Callback - Sign = Signature');

}


// Create connection
$conn = mysqli_connect($server, $username, $password, $dbase);

writeToLog($conn,"conn");
if (!$conn) {
    writeToLog("Не зконнектились");
    die("Connection failed: " . mysqli_connect_error());
}
$pay_date = (int)($data_array['create_date']/1000);
$pay_time = date("Y-m-d H:i:s",$pay_date);
$end_date = date( "Y-m-d H:i:s",strtotime($pay_time."+ 1 months"));
writeToLog($pay_date);
writetolog($end_date);

$order_id = $data_array['order_id'];
$first= strpos($order_id, "_");
$portal= substr($order_id, 0, $first);
$product_name=$data_array['description'];

$sql = "INSERT INTO b24_portal_payment (PORTAL, ACTION_PB, PAIMENT_ID, STATUS_PB, ORDER_ID, DESCRIPTION, AMOUNT, CURRENCY,CREATE_DATE,END_DATE, PRODUCT_NAME) 
        VALUES ('$portal', '$data_array[action]','$data_array[payment_id]','$data_array[status]',
        '$data_array[order_id]', '$data_array[description]','$data_array[amount]','$data_array[currency]','$pay_time','$end_date','$data_array[product_name]')
        ON DUPLICATE KEY UPDATE 
        ACTION_PB = '$data_array[action]',
        PAIMENT_ID = '$data_array[payment_id]',
        STATUS_PB = '$data_array[status]',
        ORDER_ID = '$data_array[order_id]',
        DESCRIPTION = '$data_array[description]',
        AMOUNT = '$data_array[amount]',
        CURRENCY = '$data_array[currency]',
        CREATE_DATE = '$pay_time',
        END_DATE = '$end_date',
        PRODUCT_NAME = '$data_array[product_name]'";


writeToLog($sql,"$sql");

if (mysqli_query($conn, $sql)) {
    echo "New record created successfully ";
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($sql);
}


mysqli_close($conn);


