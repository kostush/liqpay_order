<?
require_once("log.php");
require_once("pay.php");
require_once ("tools.php");
//require_once("pay.php");

?>
<!doctype html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка "Liq Pay" </title>
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Include roboto.css to use the Roboto web font, material.css to include the theme and ripples.css to style the ripple effect -->
    <link href="css/roboto.min.css" rel="stylesheet">
    <link href="css/material.min.css" rel="stylesheet">
    <link href="css/ripples.min.css" rel="stylesheet">
    <link href="css/application.css" rel="stylesheet">
</head>
<body>
    <div id="app" class="container-fluid">
                <div class="bs-callout bs-callout">
                   <!-- <i class="fa fa-trophy pull-left fa-3x"></i>-->
                    <p> <h4>Додаток  "Liq Pay off. - ЗАМОВЛЕННЯ Інтернет Магазину"  встановлено      </h4> </p>


                </div>
                <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12" id ="pay">

                    <p align = "center"> Користуйтесь  на здоров'я! <br>
                     <!-- До  <?/* echo $html_data;*/?> <br>-->
                        Якщо додаток Вам сподобався, Ви можете оплатити його <br>
                        двома способами <br>
                        ОПЛАТА</p>
                    <table class="col-sm-12 col-xs-12 col-md-12 col-lg-12" width = "100%" border ="0">
                        <thead>
                        <tr  h1>
                            <th class="text-center" width="50%" font-weight="bold">
                                Самостійно - вручну кожного місяця
                            </th>
                            <th class="text-center">
                                Підписка - автоматичне списання
                            </th>
                        </tr>
                        </thead>
                        <tbody  >
                        <tr  h1>
                            <th class="text-center" width="50%" font-weight="bold">
                                <? echo $html_Pay;?>
                            </th>
                            <th class="text-center">
                                <? echo $html_Sub;?>
                            </th>
                        </tr>

                        </tbody>
                    </table>

                </div>
                <div align = "center" font ="bold">
                    <b>Для зміни налаштувань натисни  ТУТ  <input  type="checkbox" id = "change" value="" style="display: inline-block"  onclick="app.change();" ></b>
                </div>
                <div id="lp_key">
                    <div id="form" class="container-fluid">
                        <div class="row">
                            <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12">
                                <br><br>

                                <form id="sms-settings">
                                    <input type="hidden" name="save" value="Y">
                                    <input type="hidden" name="access_token" value="<?=$_REQUEST['AUTH_ID'];?>">
                                    <input type="hidden" name="refresh_token" value="<?=$_REQUEST['REFRESH_ID'];?>">
                                    <input type="hidden" name="domain" value="<?=$_REQUEST['DOMAIN'];?>">
                                    <input type="hidden" name="member_id" value="<?=$_REQUEST['member_id'];?>">
                                    <div class="form-group">
                                        <label for="Public_key">Введіть Public_key ключ з кабинету Liq Pay</label>
                                        <input type="text" class="form-control" name="public_key" id="public_key" onchange="app.changeLiqPay(id);" placeholder="Public_key ключ з кабинету Liq Pay">
                                        <label for="Privat_key">Введіть Privat_key ключ з кабинету Liq Pay</label>
                                        <input type="text" class="form-control" name="privat_key" id="privat_key" onchange="app.changeLiqPay(id);" placeholder="Privat_key ключ з кабинету Liq Pay">
                                    </div>
                                    <div id="entity" class="form-group">
                                        <form >
                                            <table id="method" width = "100%" border="1px">
                                                <thead>
                                                <tr>
                                                    <th colspan="3">
                                                        <p align="center">Методи генерування повідомлення про оплату </p>
                                                    </th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td width ="33%" align="center">
                                                        <input    type="checkbox" id="methodLink" name="methodLink" onclick="app.ChangeMethod('methodLink');" value="link" checked> Ссилка в Бітрикс24
                                                    </td>
                                                    <td width ="33%" align="center">
                                                        <input type="checkbox" id ="methodBot" name="methodBot" onclick="app.ChangeMethod('methodBot');" value="bot" checked> Повідомлення в Приват24
                                                    </td>
                                                    <td width ="33%" align="center">
                                                        <input type="checkbox" id ="methodEmail" name="methodEmail" onclick="app.ChangeMethod('methodEmail');" value="email" checked> Invoice на Email від LiqPay
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <br>
                                            <table id="table" width = "100%">
                                                <thead>

                                                <tr>
                                                    <th colspan="2">
                                                        <p align="center">Виберіть  стадії Замовлення, коли буде сгенероване посилання на оплату Liq Pay та стадії після оплати </p>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td width ="50%" align="center">
                                                        <input    type="checkbox" id="check_lead" name="entity_Lead" onclick="app.CheckLeadEvent('div_lead_stage');" value="Leads" checked> Додаток працює з Замовленнями Інтернет-Магазину / Тимчасова пауза
                                                    </td>

                                                </tr>
                                                </thead>
                                              <!--  <tr>
                                                    <input type="checkbox" id ="check_event" name="entity_event" onclick="app.EventGet();" value="EventGet_value" checked> Event Get

                                                </tr>-->

                                            <tr>
                                                <td width ="50%">
                                                    <div id = "div_lead_stage">
                                                            <div id = "lead_start_text">
                                                                <span>Стадія генерування LiqPay</span>
                                                                <span id = "lead_start"></span>
                                                            </div>
                                                            <!-- <div id = "lead_start"></div>-->
                                                            <div id = "lead_finish_text">
                                                                <span>Стадія після сплати LiqPay</span>
                                                                <span id = "lead_finish"></span>
                                                            </div>
                                                            <!--  <div id = "lead_finish">финиш</div>-->
                                                           <div id = "lead_field_text">
                                                                <br>
                                                                <span>Поле Замовлення, в яке запишем </span>
                                                                <span>посилання на оплату LiqPay(поки що в пле "Коментар (не буде відображатись в замовленні)")</span><br>
                                                                <span id = "lead_field"></span>
                                                           </div>
                                                            <div id = "avans_lead_text">
                                                                <br>
                                                                <span>Поле Замовлення, в яке запишем </span>
                                                                <span>суму Авансу на оплату LiqPay</span><br>
                                                                <span id = "avans_lead"></span>
                                                            </div>

                                                    </div>
                                                </td>
                                                <td width ="50%">


                                                </td>
                                            </tr>
                                            </table>
                                        </form>
                                    </div>
                                    <button type="submit"  style="text-align: center" class="btn btn-primary btn-lg btn-save" onclick="app.saveChange();">Зберегти</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-dismissable alert-warning hidden" id="error">
                </div>
        <div id="error">
        </div>
    </div>

<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/tempusjs.min.js"></script>
<script src="js/ripples.min.js"></script>
<script src="js/material.min.js"></script>
<script type="text/javascript" src="js/application.js?<?php echo sha1(microtime(1))?>"></script>
<script src="//api.bitrix24.com/api/v1/"></script>
<!--<script type="text/javascript" src="js/my.js"></script>-->
<script>


    application.prototype.EventGet = function(){
        BX24.callMethod('event.get', {}, function(result){
            if(result.error())
            {
                alert('Ошибка запроса: ' + result.error());
            }
            else
            {
                if(result.more())
                    result.next();
                console.log(result.data());
            }
        });
    }
</script>

<script>

    //должно біть в индекс файле - остальное - в app.js
    $(document).ready(function () {

        $.material.init();

        BX24.init(function(){
            //$('#pay').hide();

           // app.GetDBDate();
            app.Start();
            $('#change').prop('checked',false);
            app.change();


            // app.saveFrameWidth();
            //app.displayCurrentUser();


        });
    });


</script>


</body>
</html>

