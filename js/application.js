function application () {
    this.bx24LeadEvent;
    this.bx24DealEvent;

    this.leadEvent = null;
    this.dealEvent = null;
    this.leadStart = null;
    this.leadFinish = null;

    this.dealStart = null;
    this.dealFinish = null;

    this.public_key = null;
    this.privat_key = null;
    this.currentUser = [];
    this.lead_field = null;
    this.deal_field = null;
    this.lead_liq_pay_link_field_id = null;
    this.deal_liq_pay_link_field_id = null;

    this.lead_liq_pay_language_id = null;
    this.deal_liq_pay_language_id = null;

    this.arLeadFields = [];
    this.arDealFields = [];
    this.dbResult=[];

    this.methodLink = null;
    this.methodBot = null;
    this.methodEmail = null;

    this.category_id = null;
    this.arB24DealCategory =  {};

    this.listDealCategory = [];

    this.arB24DealStage =[];
    this.dealCategoryFlags = {};
    this.arUserDealStage = {};

    this.avans_lead_id = null;
    this.avans_deal_id = null;

    this.lang;
    this.saleStatusNameByLang=[];



}
application.prototype.SetSaleStatusNameByLang = function(array){
    this.saleStatusNameByLang = array['statusLangs'];


}

application.prototype.CreateArUserDealStage = function(){
    var verify = false;
    this.arUserDealStage = {};

   // console.log("this.dealCategoryFlags",this.dealCategoryFlags);
    for ( let key in this.dealCategoryFlags){
       // console.log("dealCategoryFlags", key);
        if (this.dealCategoryFlags[key]){
            this.arUserDealStage[key]={};
            this.arUserDealStage[key]['deal_start']=  $('#selector_deal_start' +key).prop('value');
            this.arUserDealStage[key]['deal_finish']=  $('#selector_deal_finish' +key).prop('value');
            verify = true;
        }
    }
  //  console.log("this.arUserDealStage", this.arUserDealStage);
   return verify;
}

application.prototype.ChangeCategoryBox = function(id){
   var  key = id.split('_')[2];
    this.dealCategoryFlags[key] = $('#'+id).prop('checked');
   // console.log(id,key,this.dealCategoryFlags);




}

application.prototype.SetCategoryFlags = function(){


}

application.prototype.DisplayCategory = function(id_category,name_category){



            $('#deal_li').append('<li data-dealCategory-id="' + id_category + '">' +
                '<input type="checkbox" name="checkbox_category_'+ id_category+'" id="checkbox_category_'+ id_category+ '"  value = name_category   onchange = "app.ChangeCategoryBox(id)"><span id="name_category'+ id_category +'"></span>'+
               /* '<a href="javascript:void(0);" onclick="app.removeDealCategory(' + id +
                ');" class="btn btn-danger btn-raised"><i class="fa fa-times"></i><div class="ripple-wrapper"></div></a> ' +*/
                '<span id = "deal_category' + id_category + '" ></span>' +
                '<div ><span>Стадія генерування LiqPay </span><span id = "deal_start' + id_category + '" > </span>' +
                '</div><div ><span>Стадія після сплати LiqPay </span><span id = "deal_finish' + id_category + '"> </span>' +
                '</div></li>');

            $('#name_category'+ id_category ).html("  "+name_category);


            app.SetarB24DealCategory(id_category);//  стадии катергории
            app.ChangeCategoryBox("checkbox_category_"+id_category);

}


application.prototype.displayDealCategoryStage = function(array, selector, category_id,  select_from_DB){ //array- bp ,24 массив стади, ИД - категории
   // console.log("displayDealCategoryStage",array, selector,  category_id, );
    var myDiv = document.getElementById(selector+category_id);
   // console.log(myDiv,$(selector));

    //Create and append select list
    var selectList = document.createElement("select");
    selectList.id = "selector_"+selector+category_id;
    myDiv.appendChild(selectList);
   // console.log("selectList ", selectList, "myDiv ", myDiv);
    // selectList.onchange="alert('privet')";

    

    var option = document.createElement("option");
    option.value = "";
    option.text = "  --";
    selectList.appendChild(option);


    var sel = this.dbResult;

    array.forEach(function(item, i, array) {
        //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );

        var option = document.createElement("option");
        option.value = array[i]["STATUS_ID"];
        option.text = array[i]["NAME"];
        selectList.appendChild(option);
    });
    //console.log(selector," select ",app.dbResult[select]," val(app.dbResult[select])");

    //*********** Выбор из списка стадий ту, котроая в БД

    if (selector == 'deal_start'){
        select_from_DB = category_id+"_DEAL_START";
    }
    else if (selector == 'deal_finish'){
        select_from_DB = category_id+"_DEAL_FINISH";
    }
    if (app.dbResult[select_from_DB]){
        //alert(app.dbResult[select_from_DB]);
        $('#selector_'+selector+category_id).val(app.dbResult[select_from_DB]);
        $('#checkbox_category_'+category_id).prop('checked', true);


        app.ChangeCategoryBox("checkbox_category_"+category_id);
    }

    //****************************


    $("#"+selector+category_id).empty().append(selectList);



}


application.prototype.SetarB24DealCategory = function(id){
   // this.arB24DealCategory = array;

    // console.log(currapp.arB24DealCategory, "currapp.arB24DealCategory");

    var curr= this;

          var entity_id;
          if (id >0){
              entity_id = 'DEAL_STAGE_'+id;

          }
          else {
              entity_id = 'DEAL_STAGE';
          }


             BX24.callMethod(
                "crm.status.list",
                {
                    filter: { "ENTITY_ID":entity_id  }
                },
                function(result)
                {
                    if(result.error())
                        console.error(result.error());
                    else
                    {
                       // console.dir(result.data());
                        if(result.more())
                            result.next();

                        app.displayDealCategoryStage(result.data(), "deal_start", id, );
                        app.displayDealCategoryStage(result.data(), "deal_finish", id, );


                    }
                }
            );






}


application.prototype.GetDealCategory = function(){
    var currapp = this;
    var category_count=0;

    BX24.callMethod(
        "crm.dealcategory.default.get",
        {},
        function(result)
        {
            if(result.error())
                console.error(result.error());
            else
               // console.dir(result.data()['ID'], "crm.dealcategory.default.get");

                category_count =1;
            app.arB24DealCategory[result.data()['ID']] = result.data()['NAME'];
            app.DisplayCategory(result.data()['ID'] ,result.data()['NAME']);


           // currapp.SetarB24DealCategory (category_count,result.data()['ID'],result.data()['NAME']);

            BX24.callMethod(
                "crm.dealcategory.list",
                {
                    order: { "SORT": "ASC" },
                    filter: { "IS_LOCKED": "N" },
                    select: [ "ID", "NAME", "SORT" ]
                },
                function(result1)
                {
                    if(result1.error())
                        console.error(result.error());
                    else
                    {
                       // console.dir(result1.data());
                        if(result1.more())
                            result1.next();

                        for ( let i=0; i<result1.data().length; i++){
                            app.arB24DealCategory[result1.data()[i]['ID']] = result1.data()['NAME'];
                            app.DisplayCategory(result1.data()[i]['ID'],result1.data()[i]['NAME']);

                           // currapp.arB24DealCategory[result1.data()[i]['ID']] = result1.data()[i]['NAME'];
                            //currapp.SetarB24DealCategory (category_count,result1.data()[i]['ID'],result1.data()[i]['NAME']);
                        };
                    }
                }
            );

        }
    );


   // console.log(this.arB24DealCategory, "Итог");



}

application.prototype.SetDealStage = function(result){
    this.arB24DealStage = result;
}

application.prototype.ChangeMethod = function(method){
    this[method] = $('#'+method).prop('checked');
   // console.log(method,' ',$('#'+method).prop('checked'));

}
application.prototype.SetMethod = function() {
    //
    //alert("CheckLeadEvent start");
    // alert(this.leadEvent);
    this.methodLink = $('#methodLink').prop('checked');
    this.methodBot = $('#methodBot').prop('checked');
    this.methodEmail = $('#methodEmail').prop('checked');
   // console.log("SetMethod", this.methodLink, this.methodBot, this.methodEmail);
}

application.prototype.change = function(){
   // console.log("change",$('#change').prop('checked'));
    if ($('#change').prop('checked')){
        $('#lp_key').show();
        $('#avans_lead_text').hide();

    }
    else {
        $('#lp_key').hide();
    }
}

application.prototype.GetDBDate  =function(){
    var authParams = BX24.getAuth(),
        params= {},
        db_re;
    curapp = this;
    params = {authParams, 'operation':'preset'};
   // console.log("params GetDBDate", params);
    $.post(
        "application.php",
        params,
        function (data)
        {
            var answer = JSON.parse(data);
            //console.log(answer);
            if (answer.status == 'error' || answer.result == null){

                console.log('error - ошибка предварительной установки значений. Возможно устанавливается впервые', answer);
               // curapp.displayErrorMessage('К сожалению, произошла ошибка сохранения списка участников рейтинга. Попробуйте перезапустить приложение');
                app.GetLeadStage('lead_start','LEAD_START');
                app.GetLeadStage('lead_finish','LEAD_FINISH');
               // app.GetDealCategory();
                app.GetLeadField("LEAD_LIQ_PAY");
                //app.GetDealField("DEAL_LIQ_PAY");


            }
            else {

                app.dbResult = answer.result;
              //  console.log( app.dbResult," app.dbResult");

                app.GetLeadStage('lead_start','LEAD_START');
                app.GetLeadStage('lead_finish','LEAD_FINISH');
               // app.GetDealCategory();
                //app.GetDealStage("deal_start", "DEAL_START");
                //app.GetDealStage('deal_finish','DEAL_FINISH');
                app.GetLeadField("LEAD_LIQ_PAY");
               // app.GetDealField("DEAL_LIQ_PAY");

                $('#check_lead').prop('checked',(app.dbResult['LEAD_LIQ_PAY'])?true:false);
                app.CheckLeadEvent("div_lead_stage");
               // $('#check_deal').prop('checked',(app.dbResult['DEAL_LIQ_PAY'])?true:false);
               // app.CheckDealEvent("div_deal_stage");

                $('#public_key').val(app.dbResult['PUBLIC_KEY']);
                app.changeLiqPay('public_key');
                $('#privat_key').val(app.dbResult['PRIVAT_KEY']);
                app.changeLiqPay('privat_key');

                $('#methodLink').prop('checked',(app.dbResult['METHODLINK']==1)?true:false);
                //console.log(app.dbResult['METHODLINK'],' ',(app.dbResult['METHODLINK']==1)?'true':'false');
                app.ChangeMethod('methodLink');
                $('#methodBot').prop('checked',(app.dbResult['METHODBOT']==1)?true:false);
                app.ChangeMethod('methodBot');
                $('#methodEmail').prop('checked',(app.dbResult['METHODEMAIL']==1)?true:false);
                app.ChangeMethod('methodEmail');



                //BX24.callBind('ONAPPUNINSTALL', 'http://www.b24go.com/rating/application.php?operation=uninstall');

            }
        }

    )

}


application.prototype.SetLeadField  = function(field){
    this.lead_liq_pay_link_field_id = field;

}

application.prototype.SetDealField  = function(field){
    this.deal_liq_pay_link_field_id = field;

}
application.prototype.SetarLeadFields = function(ar) {
    this.arLeadFields = ar;

}
application.prototype.SetarDealFields = function(ar) {
    this.arDealFields = ar;

}
application.prototype.CreateLeadField = function(field,type){
   var field_type =(type) ? type : "string";
    BX24.callMethod(
        'sale.property.add',
        {
            fields: {
                personTypeid: '1',
                propsGroupId: '8',
                name: 'Поле для LiqPay ссылки',
                code: field,
                active: 'Y',
                util: 'Y',
                userProps: 'Y',
                isFiltered: 'Y',
                sort: '100',
                description: '',
                type: field_type ,
                required: 'N',
                settings :{
                    maxlength: 100}
            }
            },
    function(result)
    {
        if(result.error()){
            console.error(result.error().ex);
            return false;}
        else {
            console.log(result.data());
            return true;}
    });

   /* BX24.callMethod(
        "sale.order.userfield.add",
        {
            fields:
                {
                    "FIELD_NAME": field,
                    "EDIT_FORM_LABEL": field,
                    "LIST_COLUMN_LABEL": field,
                    "USER_TYPE_ID": field_type,
                    "XML_ID": field,
                    "SETTINGS": { "DEFAULT_VALUE": "" }
                }
        },
        function(result)
        {
            if(result.error()){
                console.error(result.error());
                return false;
            }
            else {
                console.log(result.data());
                return true;
            }
        }
    );*/

}
application.prototype.CreateDealField = function(field,type){
    var field_type =(type) ? type : "string";
    BX24.callMethod(
        "crm.deal.userfield.add",
        {
            fields:
                {
                    "FIELD_NAME": field,
                    "EDIT_FORM_LABEL": field,
                    "LIST_COLUMN_LABEL": field,
                    "USER_TYPE_ID": field_type,
                    "XML_ID": field,
                    "SETTINGS": { "DEFAULT_VALUE": "" }
                }
        },
        function(result)
        {
            if(result.error()){
                console.error(result.error());
                return false;
            }
            else{
                //console.dir(result.data());
                return true;
            }

        }
    );

}

application.prototype.displayErrorMessage = function(message) {
    $('#error').html(message);

}

application.prototype.CurrentUser = function(){
    BX24.callMethod('user.current', {}, function(result) {
            this.currentUser = result.data();

        }
    );
}
application.prototype.changeLiqPay = function(key){
    this[key] = $('#'+key).val();

}

application.prototype.GetLeadStage = function(selector,select){
    var current = this;
    BX24.callMethod(
            'sale.status.list',
        { select:{} ,
                    filter:{
                        type: "O"
                    },
                    order: {
                        sort:"asc"
                    },
                    navigation: 1
        },
        function (result) {
            if (result.error())
                console.error(result.error());
            else {

                if (result.more())
                    result.next();
                //console.log(result.data(),'sale.status.list' );
                app.displayLeadStage(result.data(),selector, select);
            }
        }
    );



}
application.prototype.GetLeadField = function(select) {
    /*BX24.callMethod(
        'sale.propertygroup.list',
        { select:{} ,
            filter:{},
            order:{},
            navigation: 1
        },
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data());
        });
    BX24.callMethod(
        'sale.order.getFields',
        {},
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data());
        });*/
   /* BX24.callMethod(
        'sale.statusLang.getFields',
        {},
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data(), 'sale.statusLang.getFields');
        });*/
   /* BX24.callMethod(
        'sale.statusLang.getListLangs',
        {},
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data(),  'sale.statusLang.getListLangs');
        });
*/

    BX24.callMethod(
        "sale.property.list",
        { select:{} ,
            filter:{},
            order:{},
            navigation: 1
        },
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
            {
               // console.log("sale.property.list - GET",result.data());
                if(result.more())
                    result.next();


                var lead_field = document.getElementById('lead_field');
                var array = result.data();
                var selectList = document.createElement("select");
                selectList.id = "selector_lead_field";
                //selectList.onchange="alert('privet')";
                lead_field.appendChild(selectList);
                var option = document.createElement("option");
                option.value = "NO";
                option.text = "не обрано - Записуватиметься в 'Коментар (не буде відображатись в замовленні)'";
                selectList.appendChild(option);
                var ar=[];
                ar.push("NO");
                $("#lead_field  select").val("NO");

                   /* $.each(array.properties, function(index, value) {

                        //console.log(value);

                          //  ar.push(value.code);
                            //console.log(item,item1);
                            var option = document.createElement("option");
                            option.value = value.code;
                            option.text = value.name;
                            selectList.appendChild(option);
                        });*/



                    // если инсталл и в БД нет значения - сделатьпроверку и не вібирать селект



                if (app.dbResult[select]){
                    $("#lead_field  select").val("NO"); //app.dbResult[select]);
                }

                $("#lead_field").empty().append(selectList);
                app.SetarLeadFields(ar);




                // *** создание поля для аванса в лиде
                var avans_lead = document.getElementById('avans_lead');
                var array = result.data();
                var selectList = document.createElement("select");
                selectList.id = "selector_avans_lead";
                //selectList.onchange="alert('privet')";
                avans_lead.appendChild(selectList);
                var option = document.createElement("option");
                option.value = "NO";
                option.text = "не обрано - СТВОРИТИ НОВЕ (AVANS_ORDER)";
                selectList.appendChild(option);
                var ar=[];
                ar.push("NO");
                $("#avans_lead  select").val("NO");
                if (array.length >0) {

                    array.forEach(function(item, i, array) {
                        //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );
                        ar.push(array[i]["FIELD_NAME"]);
                        var option = document.createElement("option");
                        option.value = array[i]["FIELD_NAME"];
                        option.text = array[i]["FIELD_NAME"];
                        selectList.appendChild(option);
                    });

                    // если инсталл и в БД нет значения - сделатьпроверку и не вібирать селект


                }
                if (app.dbResult['AVANS_LEAD']){
                    $("#avans_lead  select").val(app.dbResult['AVANS_LEAD']);

                }
                //console.log(app.dbResult['AVANS_LEAD']);
                $("#avans_lead").empty().append(selectList);
                //app.SetarLeadFields(ar);
                //********
            }
        }
    );
}

application.prototype.GetDealField = function(select) {
    BX24.callMethod(
        "crm.deal.userfield.list",
        {
            order: { "SORT": "ASC" },
            filter: { "MANDATORY": "N" }
        },
        function(result)
        {
            if(result.error())
                console.error(result.error());
            else
            {
                if(result.more())
                    result.next();
                this.arDealFields = result.data();
                var deal_field = document.getElementById('deal_field');
                var array = result.data();
                var selectList = document.createElement("select");
                selectList.id = "selector_deal_field";
                //selectList.onchange="alert('privet')";
                deal_field.appendChild(selectList);
                var option = document.createElement("option");
                option.value = "NO";
                option.text = "не обрано - СТВОРИТИ НОВЕ (DEAL_LIQPAY)";
                selectList.appendChild(option);
                var ar=[];
                ar.push("NO");
                $("#deal_field  select").val("NO");
                if (array.length>0){
                    array.forEach(function(item, i, array) {
                        //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );
                        ar.push(array[i]["FIELD_NAME"]);
                        var option = document.createElement("option");
                        option.value = array[i]["FIELD_NAME"];
                        option.text = array[i]["FIELD_NAME"];
                        selectList.appendChild(option);
                    });

                }
                // console.log("GetDealField select ",select);
                //console.log(" GetDealField app.dbResult[select] ",app.dbResult[select],"end");
                if (app.dbResult[select]) {
                    $("#deal_field  select").val(app.dbResult[select]);
                }

                $("#deal_field").empty().append(selectList);
                app.SetarDealFields(ar);

                // *** *********************
                //
                // создание поля для аванса в сделке

                var deal_field = document.getElementById('avans_deal');
                var array = result.data();
                var selectList = document.createElement("select");
                selectList.id = "selector_avans_deal";
                //selectList.onchange="alert('privet')";
                deal_field.appendChild(selectList);
                var option = document.createElement("option");
                option.value = "NO";
                option.text = "не выбрано - СОЗДАТЬ НОВОЕ (AVANS_DEAL)";
                selectList.appendChild(option);
                var ar=[];
                ar.push("NO");
                $("#avans_deal  select").val("NO");
                if (array.length >0) {

                    array.forEach(function(item, i, array) {
                        //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );
                        ar.push(array[i]["FIELD_NAME"]);
                        var option = document.createElement("option");
                        option.value = array[i]["FIELD_NAME"];
                        option.text = array[i]["FIELD_NAME"];
                        selectList.appendChild(option);
                    });

                    // если инсталл и в БД нет значения - сделатьпроверку и не вібирать селект


                }
                if (app.dbResult['AVANS_DEAL']){
                    $("#avans_deal  select").val(app.dbResult['AVANS_DEAL']);
                }

                $("#avans_deal").empty().append(selectList);
               // app.SetarDealFields(ar);
                //********

            }
        }
    );
}

application.prototype.GetDealStage = function(selector,select){

    BX24.callMethod(
        "crm.dealcategory.stage.list",
        {},
        function (result) {
            if (result.error()){
                console.error(result.error());
            }
            else {
                 console.dir(result.data());
                if (result.more())
                    result.next();
                app.SetDealStage(result.data());
                app.displayLeadStage(result.data(),selector,select);
            }
        }
    );
}


application.prototype.displayLeadStage = function(array, selector, select){
    //console.log("displayLeadStage",array, selector, select);
    var myDiv = document.getElementById(selector);
   // console.log(myDiv,$(selector));

    //Create and append select list
    var selectList = document.createElement("select");
    selectList.id = "selector_"+selector;
  //  console.log("selectList ", selectList, "myDiv ", myDiv);
    // selectList.onchange="alert('privet')";
    myDiv.appendChild(selectList);
    var sel = this.dbResult;
    var statusName;
    var arStatus = app.saleStatusNameByLang;
    var flag_find ;
   // console.log(array.statuses," array.statuses")
   // console.log(arStatus," saleStatusNameByLang - arStatus");

     for (let key2 in array.statuses){
        //console.log(key," ",arStatus[key]);
         flag_find =0;
        for ( let key in arStatus){

           // console.log(key, arStatus[key],  "item  i");
            //console.log(key2," ", array.statuses[key2]);
            if (arStatus[key]['statusId'] == array.statuses[key2].id){
                flag_find =1;
                statusName = arStatus[key]['name'];
               // console.log(statusName, " совпало - statusName");
                var option = document.createElement("option");
                option.value = array.statuses[key2].id;
                option.text = statusName;
                selectList.appendChild(option);}
        };
        if (flag_find == 0){
            var option = document.createElement("option");
            option.value = array.statuses[key2].id;
            option.text = "id Статуса = '"+array.statuses[key2].id + "' Нет названия  статуса на языке '" + this.lang + "' портала" ;
            selectList.appendChild(option);

        }



    };

   // предустановленное значение из БД
    if (app.dbResult[select]){
        $("#"+selector +" select").val(app.dbResult[select]);
    }


    $("#"+selector).empty().append(selectList);



}
application.prototype.CheckLeadEvent = function(checked_div){ //

    if ($("#check_lead").prop('checked')){
        $('#'+checked_div).show();
        this.leadEvent  = true;
       /* BX24.callBind(
            'onSaleOrderSaved',
            'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=lead'
        );
        BX24.callMethod('event.get', {}, function(result) {
            if (result.error()) {
                // alert('Ошибка запроса: ' + result.error());
            }
            else {
                if (result.more())
                    result.next();
                console.log(" BX24.callMethod('event.get',", result.answer.result);

                // console.log("Установленны ли ивенты", app.bx24LeadEvent, app.bx24DealEvent);

            }
        });
*/




    } else{
        $('#'+checked_div).hide();
        this.leadEvent = false;
       /* BX24.callUnbind('ONSALEORDERSAVED', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=lead');

        BX24.callMethod('event.get', {}, function(result) {
            if (result.error()) {
                // alert('Ошибка запроса: ' + result.error());
            }
            else {
                if (result.more())
                    result.next();
                console.log(" BX24.callMethod('event.get',", result.answer.result);

                // console.log("Установленны ли ивенты", app.bx24LeadEvent, app.bx24DealEvent);

            }
        });
*/


    }


}
application.prototype.CheckDealEvent = function(checked_div){
   /* if ($("#check_deal").prop('checked')){
        $('#'+checked_div).show();
        this.dealEvent = true;
    } else{
        $('#'+checked_div).hide();
        this.dealEvent = false;
    }*/

}



application.prototype.checkField = function(){

    var result = true;
    var curapp = this;
   // alert(this.public_key,"1", this.public_key)
    if (((this.public_key == null) || (this.public_key == "")) || ((this.privat_key == null) ||(this.privat_key == ""))){
        alert ("Заповніть  поля ключів Liq Pay");
        result = false;
        return result;
    }
    app.SetMethod();
    if ((this.methodLink == false) && (this.methodBot == false) && (this.methodEmail == false)) {
        alert("Оберіть хоча б один метод повідомлення про оплату");
        result = false;
        return result;
    }



    if (this.leadEvent){

        this.leadStart = $("#selector_lead_start").prop('value');
        this.leadFinish = $("#selector_lead_finish").prop('value');
        //alert($("#selector_lead_start").prop('selectedIndex'));
        if ( $("#selector_lead_start").prop('selectedIndex') == $("#selector_lead_finish").prop('selectedIndex') )  {
            alert ("Ви обрали однакові стадії для ліда. Можливо потрібно різні ?" );
            //result = false;
            //throw new Error("my error message");
        }



        if (($("#selector_lead_field").prop('selectedIndex')>0) && ($("#selector_lead_field").prop('value')!="NO")) {
            this.lead_liq_pay_link_field_id = $("#selector_lead_field").prop('code');

        }
        else  { // если в списке поле не вібрано - по умолчанию поле =  "UF_CRM_ORDER_LIQPAY"
            // Если нет такого поля - создать
            this.SetLeadField("comments");

           /* if(!this.arLeadFields.includes("UF_CRM_LEAD_LIQPAY")) {

                var field = "LEAD_LIQPAY";
                var field_result = this.CreateLeadField(field);
                if (field_result == false) {
                    console.log("Ошибка создания поля Лида UF_CRM_LEAD_LIQPAY");
                } else{
                    //console.log("Поле создано  UF_CRM_LEAD_LIQPAY");
                }
            }
            else{
               // console.log ("Да");


            }*/
        }
        /*
        // создаем пользовтельское поле для аванса
        if (($("#selector_avans_lead").prop('selectedIndex')>0) && ($("#selector_avans_lead").prop('value')!="NO")) {
            this.avans_lead_id = $("#selector_avans_lead").prop('value');

        }
        else  { // если в списке поле не вібрано - по умолчанию поле =  "UF_CRM_AVANS_LEADY"
            // Если нет такого поля - создать
            this.avans_lead_id = "UF_CRM_AVANS_LEAD";

            if(!this.arLeadFields.includes("UF_CRM_AVANS_LEAD")) {

                var field = "AVANS_LEAD";
                var field_result = this.CreateLeadField(field,"double");
                if (field_result == false) {
                    console.log("Ошибка создания поля Лида UF_CRM_AVANS_LEAD");
                } else{
                    //console.log("Поле создано  UF_CRM_LEAD_LIQPAY");
                }
            }
            else{
                // console.log ("Да");


            }
        }*/
        // Устанавливаем обработчик события на изменение лида

        if (!this.bx24LeadEvent)
            BX24.callBind(
                'onSaleOrderSaved',
                'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=lead'
            );



    }else{
        this.leadStart = null;
        this.leadFinish = null;
        this.lead_liq_pay_link_field_id = null;
        this.avans_lead_id = null;
        if (this.bx24LeadEvent) {
            BX24.callUnbind('ONSALEORDERSAVED', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=lead');
        }

        //alert($("#selector_lead_start").prop('selectedIndex'));
        //alert("Не выбран чек лид");
        // удаляем обработчики событий на создание и изменение лида, если чек - лида выключен
        // BX24.callUnbind('onCrmLeadUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead');
        //BX24.callUnbind('onCrmLeadAdd', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead');

    }

   /* if (this.dealEvent){
        var res = app.CreateArUserDealStage();//создаем массив из выбранных по чек боксу категорий (категория - старт стадия, финиш стадия)
        if(!res){
            alert("Оберіть хоча б одне направлення угод!");
           return res;
        }



        this.dealStart = (this.arUserDealStage.hasOwnProperty(['0']['deal_start'])) ? this.arUserDealStage['0']['deal_start']:"NO"  ;//$("#selector_deal_start").prop('value');
        this.dealStart = (this.arUserDealStage.hasOwnProperty(['0']['deal_finish'])) ? this.arUserDealStage['0']['deal_finish']:"NO"  ; //this.dealFinish = $("#selector_deal_finish").prop('value');
        //alert($("#selector_lead_start").prop('selectedIndex'));
        /!*if ( $("#selector_deal_start").prop('selectedIndex') == $("#selector_deal_finish").prop('selectedIndex') )  {
            alert ("Вы выбрали одинаковые стадии для СДЕЛКИ. Может быть разные нужно ?" );
            //throw new Error("my error message");
            //result = false;
        }*!/
        if (($("#selector_deal_field").prop('selectedIndex')>0) && ($("#selector_deal_field").prop('value')!="NO")) {
            this.deal_liq_pay_link_field_id = $("#selector_deal_field").prop('value');


        } else  {
            // есть ли уже такое поле?
            this.SetDealField("UF_CRM_DEAL_LIQPAY");

            if(!this.arDealFields.includes("UF_CRM_DEAL_LIQPAY")) {

                var field = "DEAL_LIQPAY";
                var field_result = this.CreateDealField(field);
                if (field_result == false) {
                    console.log("Ошибка создания поля Сделки UF_CRM_DEAL_LIQPAY");
                } else{
                   // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
                }


            }
            else{

            }

        }
        //создаем поле для аванса сделки

        if (($("#selector_avans_deal").prop('selectedIndex')>0) && ($("#selector_avans_deal").prop('value')!="NO")) {
            this.avans_deal_id = $("#selector_avans_deal").prop('value');

        }
        else  { // если в списке поле не вібрано - по умолчанию поле =  "UF_CRM_AVANS_DEAL"
            // Если нет такого поля - создать
            this.avans_deal_id = "UF_CRM_AVANS_DEAL";

            if(!this.arDealFields.includes("UF_CRM_AVANS_DEAL")) {

                var field = "AVANS_DEAL";
                var field_result = this.CreateDealField(field,"double");
                if (field_result == false) {
                    console.log("Ошибка создания поля Сделки UF_CRM_AVANS_DEAL");
                } else{
                    //console.log("Поле создано  UF_CRM_LEAD_LIQPAY");
                }
            }
            else{
                // console.log ("Да");


            }
        }



        //  СОЗДАЕМ ПОЛЕ ДЛЯ ЯЗЫКА КЛИЕНТА в ЛИК ПЕЙ
        var lang = "UF_CRM_DEAL_LIQ_LANG";
        this.deal_liq_pay_language_id = lang;
        if(!this.arDealFields.includes("UF_CRM_DEAL_LIQ_LANG")) {

           /!* var field = "DEAL_LIQ_LANG";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_DEAL_LIQ_LANG");
            } else{
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }*!/

            BX24.callMethod(
                "crm.deal.userfield.add",
                {
                    fields:
                        {
                            "FIELD_NAME": "UF_CRM_DEAL_LIQ_LANG",
                            "EDIT_FORM_LABEL": "Язык LiqPay в сделке",
                            "LIST_COLUMN_LABEL": "Язык LiqPay в сделке",
                            "USER_TYPE_ID": "enumeration",
                            "LIST":[ { "VALUE": "uk","XML_ID":"uk_xml" }, { "VALUE": "en" }, { "VALUE": "ru" }],
                            "XML_ID": "UF_CRM_DEAL_LIQ_LANG",
                            "SETTINGS":[ { "LIST_HEIGHT": 3 },{"DEFAULT_VALUE": "uk" }]
                        }
                },
                function(result)
                {
                    if(result.error()){
                        console.error(result.error());
                    }
                    else{
                       // console.dir(result.data());


                    }

                }
            );


        }
        else{
           /!* var ident="UF_CRM_DEAL_LIQ_LANG";
            BX24.callMethod(
                "crm.deal.userfield.get",
                {
                    id:ident
                },
                function(result)
                {
                    if(result.error())
                        console.error(result.error());
                    else
                        console.dir(result.data());
                }
            );*!/
        }
        // конец создания поля ДЛЯ ЯЗЫКА КЛИЕНТА в ЛИК ПЕЙ

        if (!this.bx24DealEvent){
            BX24.callBind('onCrmDealUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=deal');
            // var bind_deal_result1 = BX24.callBind('onCrmDealAdd',   'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');

        }


    }else{
        this.dealStart = null;
        this.dealFinish = null;
        this.deal_liq_pay_link_field_id = null;
        this.deal_liq_pay_language_id = null;
        this.avans_deal_id = null;
        if (this.bx24DealEvent) {
            BX24.callUnbind('onCrmDealUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=deal');
        }

        // alert("Не выбран чек Сделки");
    }
    // проверка полей,  для ссылки Лик пей УТОЧНИТЬ ПО значению по умолчанию




    // BX24.callUnbind('onCrmDealAdd', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');
*/
    return result;

}

application.prototype.finishInstallation = function(){
   // console.log("finishInstall", this);

    if (!app.checkField()) {

    }

    else{
        curapp = this;

        //  $('#save-btn').find('i').removeClass('fa-check').addClass('fa-spinner').addClass('fa-spin');
        var authParams = BX24.getAuth(),
            params= {},
            operation = {'operation':'install'},
            us={},
            data={};
        data['lead_liq_pay_link_field_id'] = this.lead_liq_pay_link_field_id;
        data['deal_liq_pay_link_field_id'] = this.deal_liq_pay_link_field_id;
        data['dealEvent'] = this.dealEvent;
        data['leadEvent'] = this.leadEvent;
        data['leadStart'] = this.leadStart;
        data['leadFinish'] = this.leadFinish;
        data['dealStart'] = this.dealStart;
        data['dealFinish'] = this.dealFinish;
        data['public_key'] = this.public_key;
        data['privat_key'] = this.privat_key;
        data['deal_liq_pay_language_id'] = this.deal_liq_pay_language_id;
        data['methodLink'] = (this.methodLink)?1:0;
        data['methodBot'] = (this.methodBot)?1:0;
        data['methodEmail'] = (this.methodEmail)? 1 : 0;
        data['arUserDealStage'] = this.arUserDealStage;
        data['avans_lead_id'] = this.avans_lead_id;
        data['avans_deal_id'] = this.avans_deal_id;





        BX24.callMethod('user.current', {}, function(result) {
                var user = result.data();

                params = {authParams, user, 'operation':'install',data};
                //console.log('params', params);
                $.post(
                    "application.php",
                    params,
                    function (data)
                    {
                        var answer = JSON.parse(data);
                        if (answer.status == 'error') {
                            console.log('error', answer.result);
                            curapp.displayErrorMessage('К сожалению, произошла ошибка сохранения списка участников рейтинга. Попробуйте перезапустить приложение');
                        }
                        else {

                            var db= answer.result;


                            //BX24.callBind('ONAPPUNINSTALL', 'http://www.b24go.com/rating/application.php?operation=uninstall');

                            BX24.installFinish();
                            //$('#pay').show();
                            $('#lp_key').hide();


                        }
                    }

                )
            }
        );

    };
}

application.prototype.saveChange = function(){


    if (!app.checkField()) {
       // alert(" ошибки - выход ");// если заполнено Аякс
    }

    else{
        curapp = this;

        //  $('#save-btn').find('i').removeClass('fa-check').addClass('fa-spinner').addClass('fa-spin');
        var authParams = BX24.getAuth(),
            params= {},
            operation = {'operation':'install'},
            us={},
            data={};
        data['lead_liq_pay_link_field_id'] = this.lead_liq_pay_link_field_id;
        data['deal_liq_pay_link_field_id'] = this.deal_liq_pay_link_field_id;
        data['dealEvent'] = this.dealEvent;
        data['leadEvent'] = this.leadEvent;
        data['leadStart'] = this.leadStart;
        data['leadFinish'] = this.leadFinish;
        data['dealStart'] = this.dealStart;
        data['dealFinish'] = this.dealFinish;
        data['public_key'] = this.public_key;
        data['privat_key'] = this.privat_key;
        data['deal_liq_pay_language_id'] = this.deal_liq_pay_language_id;
        data['methodLink'] = (this.methodLink)?1:0;
        data['methodBot'] = (this.methodBot)?1:0;
        data['methodEmail'] = (this.methodEmail)? 1 : 0;
        data['arUserDealStage'] = this.arUserDealStage;
        data['avans_lead_id'] = this.avans_lead_id;
        data['avans_deal_id'] = this.avans_deal_id;





        BX24.callMethod('user.current', {}, function(result) {
            var user = result.data();

            params = {authParams, user, 'operation':'install',data };
           // console.log("param",params);
            $.post(
                "application.php",
                params,
                function (data)
                {
                    var answer = JSON.parse(data);
                    if (answer.status == 'error') {
                        console.log('error', answer.result);
                        curapp.displayErrorMessage('К сожалению, произошла ошибка сохранения списка участников рейтинга. Попробуйте перезапустить приложение');
                    }
                    else {
                       // console.log("ajax answer",answer);
                        var db= answer.result;


                        //BX24.callBind('ONAPPUNINSTALL', 'http://www.b24go.com/rating/application.php?operation=uninstall');


                        //$('#pay').show();
                        $('#lp_key').hide();
                        $('#change').prop('checked',false);
                        app.checkEvent();


                    }
                }
            )
        });

    }
}

application.prototype.checkEvent = function(){
    app.bx24LeadEvent = false;
    app.bx24DealEvent = false;
    BX24.callMethod('event.get', {}, function(result) {
        if (result.error()) {
           // alert('Ошибка запроса: ' + result.error());
        }
        else {
            if (result.more())
                result.next();
            //console.log(" BX24.callMethod('event.get',", result.answer.result);
            var res = result.answer.result;
            res.forEach(function (item, i, res) {
                if ((item.event == "ONSALEORDERSAVED") && (item.handler == "https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=lead")) {

                    app.bx24LeadEvent = true;
                }
              /*  if ((item.event == "ONCRMDEALUPDATE") && (item.handler == "https://cremaprodotti.com.ua/Bitrix24/skk/liqpay_order/event.php?item=deal")) {

                    app.bx24DealEvent = true;
                }*/
            });
           // console.log("Установленны ли ивенты", app.bx24LeadEvent, app.bx24DealEvent);

        }
    });

}

application.prototype.Start = function(){




    this.lang =  BX24.getLang();
    //console.log(this.lang," this.lang");
    //запрос списка статусов на языке
    BX24.callMethod(
        'sale.statusLang.list',
        { select:{

            } ,
            filter:{
                lid: this.lang
            },
            order:{
                //name: "asc"
            },
            navigation: 1
        },
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data()," vtnjl ,24 по статусам");
                app.SetSaleStatusNameByLang(result.data());

        });
    BX24.callMethod(
        'sale.status.list',
        { select:{

            } ,
            filter:{
                type: 'O'
            },
            order:{
                sort: "asc"
            },
            navigation: 1
        },
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data()," sale.status.list");
            //app.SetSaleStatusNameByLang(result.data());

        });
    BX24.callMethod(
        'sale.status.get',
        { id: 'P' },
        function(result)
        {
            if(result.error())
                console.error(result.error().ex);
            else
                console.log(result.data());
        });

    app.GetDBDate();

    app.CheckLeadEvent("div_lead_stage");
    //app.CheckDealEvent("div_deal_stage");
    app.CurrentUser();
    app.checkEvent();


    //app.GetDealCategory();








}

app = new application();


