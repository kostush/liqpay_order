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



}
application.prototype.CreateArUserDealStage = function(){
    var verify = false;
    for ( let key in this.dealCategoryFlags){
        console.log("dealCategoryFlags", key);
        if (this.dealCategoryFlags[key]){
            this.arUserDealStage[key]={};
            this.arUserDealStage[key]['deal_start']=  $('#selector_deal_start' +key).prop('value');
            this.arUserDealStage[key]['deal_finish']=  $('#selector_deal_finish' +key).prop('value');
            verify = true;
        }
    }
    console.log("this.arUserDealStage", this.arUserDealStage);
   return verify;
}

application.prototype.ChangeCategoryBox = function(id){
   var  key = id.split('_')[2];
    this.dealCategoryFlags[key] = $('#'+id).prop('checked');
    console.log(id,key,this.dealCategoryFlags);




}

application.prototype.SetCategoryFlags = function(){


}

application.prototype.DisplayCategory = function(id_category,name_category){



            $('#deal_li').append('<li data-dealCategory-id="' + id_category + '">' +
                '<input type="checkbox" name="checkbox_category_'+ id_category+'" id="checkbox_category_'+ id_category+ '"  value = name_category  checked onchange = "app.ChangeCategoryBox(id)"><span id="name_category'+ id_category +'"></span>'+
               /* '<a href="javascript:void(0);" onclick="app.removeDealCategory(' + id +
                ');" class="btn btn-danger btn-raised"><i class="fa fa-times"></i><div class="ripple-wrapper"></div></a> ' +*/
                '<span id = "deal_category' + id_category + '" ></span>' +
                '<div ><span>Стадія генерування LiqPay </span><span id = "deal_start' + id_category + '" > </span>' +
                '</div><div ><span>Стадія після сплати LiqPay </span><span id = "deal_finish' + id_category + '"> </span>' +
                '</div></li>');

            $('#name_category'+ id_category ).html("  "+name_category);
            app.ChangeCategoryBox("checkbox_category_"+id_category);

    app.SetarB24DealCategory(id_category);//  стадии катергории

}


application.prototype.displayDealCategoryStage = function(array, selector, category_id,  select_from_DB){ //array- bp ,24 массив стади, ИД - категории
    console.log("displayDealCategoryStage",array,  category_id, );
    var myDiv = document.getElementById(selector+category_id);
    // console.log(myDiv,$(selector));

    //Create and append select list
    var selectList = document.createElement("select");
    selectList.id = "selector_"+selector+category_id;
    //console.log("selectList ", selectList, "myDiv ", myDiv);
    // selectList.onchange="alert('privet')";
    myDiv.appendChild(selectList);
    var sel = this.dbResult;

    array.forEach(function(item, i, array) {
        //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );

        var option = document.createElement("option");
        option.value = array[i]["STATUS_ID"];
        option.text = array[i]["NAME"];
        selectList.appendChild(option);
    });
    //console.log(selector," select ",app.dbResult[select]," val(app.dbResult[select])");
    if (app.dbResult[select_from_DB]){
        $("#"+selector +" select").val(app.dbResult[select_from_DB]);
    }

    //document.getElementById("select").innerHTML = selectList;
    // Lead_Stage.replaceWith(selectList);

    //document.getElementById("Lead_Stage").innerHTML=myDi


    $("#"+selector+category_id).empty().append(selectList);



}




/*application.prototype.GetB24CategoryDealStage = function(id,list_id){  // id - категория в Битрикс24, селектор - id в HTML (list_deal_category
    console.log("GetB24CategoryDealStage",id, list_id);

    BX24.callMethod(
        "crm.dealcategory.stage.list",
        { id: id },
        function(result)
        {
            if(result.error())
                console.error(result.error());
            else
                console.dir(result.data());
            app.displayDealCategoryStage(result.data(), list_id, "list_deal_start", id, );
            app.displayDealCategoryStage(result.data(), list_id, "list_deal_finish", id, );

            //app.addDealCategoryRow(result.data(),id);
        }
    );
}*/

/*application.prototype.DisplayDealCategory = function(array, selector, list_id){ //list_deal_category
    console.log("DisplayDealCategory ",array, selector, list_id);
    var myDiv = document.getElementById(selector);
    console.log(myDiv,$(selector));

    //Create and append select list
    var selectList = document.createElement("select");
    selectList.id = "selector_"+selector;
    selectList.onchange = function(){app.GetB24CategoryDealStage(this.value,list_id)};
    myDiv.appendChild(selectList);
    var sel = this.dbResult;

    array.forEach(function(item, i, array) {
       // alert( i + ": " + array[i]["ID"] + " " + array[i]["NAME"] );

        var option = document.createElement("option");
        option.value = array[i]["ID"];
        option.text = array[i]["NAME"];
        selectList.appendChild(option);

    });
    //console.log(selector," select ",app.dbResult[select]," val(app.dbResult[select])");
    /!*if (app.dbResult[select]){
        $("#"+selector +" select").val(app.dbResult[select]);
    }*!/

   /!* $("#"+selector).change(function() {
        app.GetB24CategoryDealStage($("#selector_"+selector).val(),selector);
    });*!/

    //document.getElementById("select").innerHTML = selectList;
    // Lead_Stage.replaceWith(selectList);

    //document.getElementById("Lead_Stage").innerHTML=myDi


    $("#"+selector).empty().append(selectList);
    return;



}*/
application.prototype.SetarB24DealCategory = function(id){
   // this.arB24DealCategory = array;

    // console.log(currapp.arB24DealCategory, "currapp.arB24DealCategory");

    var curr= this;

         // curr.arB24DealCategory[id]=[];
          //curr.arB24DealCategory[id][0] = name;
            console.log("id=",id);
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
                        app.displayDealCategoryStage(result.data(),  "deal_finish", id, );


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
                console.dir(result.data()['ID'], "crm.dealcategory.default.get");

                category_count =1;
            currapp.arB24DealCategory[result.data()['ID']] = result.data()['NAME'];
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

/*application.prototype.addDealCategoryRow = function (list_DealCategoryID) {
    $('#deal_li').append('<li data-dealCategory-id="' + list_DealCategoryID +'"><a href="javascript:void(0);" onclick="app.removeDealCategory(' + list_DealCategoryID +
        ');" class="btn btn-danger btn-raised"><i class="fa fa-times"></i><div class="ripple-wrapper"></div></a> '+
                '<span id = "list_deal_category'+list_DealCategoryID+'" ></span>' +
                '<div ><span>Стадія генерування LiqPay</span><span id = "list_deal_start'+list_DealCategoryID+'" ></span>'+
                '</div><div ><span>Стадія після сплати LiqPay</span><span id = "list_deal_finish'+list_DealCategoryID+'"></span>'+
                '</div></li>');
    //console.log(list_DealCategoryID);

    this.DisplayDealCategory( this.arB24DealCategory,'list_deal_category'+list_DealCategoryID,list_DealCategoryID);

    //app.displayLeadStage(this.arB24DealStage,"deal_start"+arDealCategoryID, "DEAL_START"+arDealCategoryID);
   // app.displayLeadStage(this.arB24DealStage,'deal_finish'+arDealCategoryID,'DEAL_FINISH'+arDealCategoryID);


}*/

/*
application.prototype.removeDealCategory = function (idCategory) {
    result = confirm('Ви впевнені, що хочете видалити "НАПРАВЛЕННЯ" угод №' + idCategory+' ?');
    if (result) {
        $('[data-dealCategory-id=' + idCategory + ']').remove();
        this.listDealCategory = deleteByKey(this.listDealCategory, idCategory);
      //  app.checkSaving();
        // console.log(this.arInstallRatingUsers);
    }
}

function deleteByKey (arData, keyToRemove) {
    for(key in arData){
        if(arData.hasOwnProperty(key) && (key == keyToRemove)) {
            delete arData[key];
        }
    }

    return arData;
}
*/

/*
application.prototype.addDealCategory = function() {
    var arDeal = this.listDealCategory;
    arDeal.push("");

    this.addDealCategoryRow(arDeal.length);

    //$("#"+)


}
*/


application.prototype.ChangeMethod = function(method){
    this[method] = $('#'+method).prop('checked');
    console.log(method,' ',$('#'+method).prop('checked'));

}
application.prototype.SetMethod = function() {
    //
    //alert("CheckLeadEvent start");
    // alert(this.leadEvent);
    this.methodLink = $('#methodLink').prop('checked');
    this.methodBot = $('#methodBot').prop('checked');
    this.methodEmail = $('#methodEmail').prop('checked');
    console.log("SetMethod", this.methodLink, this.methodBot, this.methodEmail);
}

application.prototype.change = function(){
   // console.log("change",$('#change').prop('checked'));
    if ($('#change').prop('checked')){
        $('#lp_key').show();

        /*var id = prompt("Введите ID");
        BX24.callMethod(
            "crm.deal.userfield.get",
            {
                id: id
            },
            function(result)
            {
                if(result.error())
                    console.error(result.error());
                else
                    console.dir(result.data());
            }
        );*/
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
            if (answer.status == 'error') {
                console.log('error - ошибка предварительной установки значений. Возможно устанавливается впервые', answer);
                curapp.displayErrorMessage('К сожалению, произошла ошибка сохранения списка участников рейтинга. Попробуйте перезапустить приложение');
            }
            else {

                app.dbResult = answer.result;

                app.GetLeadStage('lead_start','LEAD_START');
                app.GetLeadStage('lead_finish','LEAD_FINISH');
                app.GetDealStage("deal_start", "DEAL_START");
                app.GetDealStage('deal_finish','DEAL_FINISH');
                app.GetLeadField("LEAD_LIQ_PAY");
                app.GetDealField("DEAL_LIQ_PAY");

                $('#check_lead').prop('checked',(app.dbResult['LEAD_LIQ_PAY'])?true:false);
                app.CheckLeadEvent("div_lead_stage");
                $('#check_deal').prop('checked',(app.dbResult['DEAL_LIQ_PAY'])?true:false);
                app.CheckDealEvent("div_deal_stage");

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
application.prototype.CreateLeadField = function(field){

    BX24.callMethod(
        "crm.lead.userfield.add",
        {
            fields:
                {
                    "FIELD_NAME": field,
                    "EDIT_FORM_LABEL": field,
                    "LIST_COLUMN_LABEL": field,
                    "USER_TYPE_ID": "string",
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

                return true;
            }
        }
    );

}
application.prototype.CreateDealField = function(field){

    BX24.callMethod(
        "crm.deal.userfield.add",
        {
            fields:
                {
                    "FIELD_NAME": field,
                    "EDIT_FORM_LABEL": field,
                    "LIST_COLUMN_LABEL": field,
                    "USER_TYPE_ID": "string",
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
        "crm.status.list",
        {
            order: {"SORT": "ASC"},
            filter: {"ENTITY_ID": "STATUS"}
        },
        function (result) {
            if (result.error())
                console.error(result.error());
            else {
                // console.dir(result.data());
                if (result.more())
                    result.next();
                app.displayLeadStage(result.data(),selector, select);
            }
        }
    );



}
application.prototype.GetLeadField = function(select) {
    BX24.callMethod(
        "crm.lead.userfield.list",
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
               // console.dir(" crm.lead.userfield.list - GET",result.data());
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
                option.text = "не выбрано - СОЗДАТЬ НОВОЕ (LEAD_LIQPAY)";
                selectList.appendChild(option);
                var ar=[];
                ar.push("NO");
                $("#lead_field  select").val("NO");
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
                if (app.dbResult[select]){
                    $("#lead_field  select").val(app.dbResult[select]);
                }

                $("#lead_field").empty().append(selectList);
                app.SetarLeadFields(ar);

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
                option.text = "не выбрано - СОЗДАТЬ НОВОЕ (DEAL_LIQPAY)";
                selectList.appendChild(option);
                var ar=[];
                ar.push("NO");

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
                // console.dir(result.data());
                if (result.more())
                    result.next();
                app.SetDealStage(result.data());
                app.displayLeadStage(result.data(),selector,select);
            }
        }
    );
}
/*  application.prototype.Alert = function(){
      alert (document.getElementById("selector_lead_start"))
  }*/
application.prototype.displayLeadStage = function(array, selector, select){
   // console.log("displayLeadStage",array, selector, select);
    var myDiv = document.getElementById(selector);
   // console.log(myDiv,$(selector));

    //Create and append select list
    var selectList = document.createElement("select");
    selectList.id = "selector_"+selector;
    console.log("selectList ", selectList, "myDiv ", myDiv);
    // selectList.onchange="alert('privet')";
    myDiv.appendChild(selectList);
    var sel = this.dbResult;

    array.forEach(function(item, i, array) {
        //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );

        var option = document.createElement("option");
        option.value = array[i]["STATUS_ID"];
        option.text = array[i]["NAME"];
        selectList.appendChild(option);
    });
    //console.log(selector," select ",app.dbResult[select]," val(app.dbResult[select])");
    if (app.dbResult[select]){
        $("#"+selector +" select").val(app.dbResult[select]);
    }

    //document.getElementById("select").innerHTML = selectList;
    // Lead_Stage.replaceWith(selectList);

    //document.getElementById("Lead_Stage").innerHTML=myDi


    $("#"+selector).empty().append(selectList);



}
application.prototype.CheckLeadEvent = function(checked_div){ //
    //alert("CheckLeadEvent start");
    // alert(this.leadEvent);
    if ($("#check_lead").prop('checked')){
        $('#'+checked_div).show();
        this.leadEvent  = true;


    } else{
        $('#'+checked_div).hide();
        this.leadEvent = false;



    }


}
application.prototype.CheckDealEvent = function(checked_div){
    if ($("#check_deal").prop('checked')){
        $('#'+checked_div).show();
        this.dealEvent = true;
    } else{
        $('#'+checked_div).hide();
        this.dealEvent = false;
    }

}



application.prototype.checkField = function(){

    var result = true;
    var curapp = this;
    if ((this.public_key == null) || (this.privat_key == null) ){
        alert ("Заполните поля ключей Liq Pay");
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
            alert ("Вы выбрали одинаковые стадии для лида. Может быть разные нужно ?" );
            //result = false;
            //throw new Error("my error message");
        }



        if (($("#selector_lead_field").prop('selectedIndex')>0) && ($("#selector_lead_field").prop('value')!="NO")) {
            this.lead_liq_pay_link_field_id = $("#selector_lead_field").prop('value');

        }
        else  { // если в списке поле не вібрано - по умолчанию поле =  "UF_CRM_LEAD_LIQPAY"
            // Если нет такого поля - создать
            this.SetLeadField("UF_CRM_LEAD_LIQPAY");

            if(!this.arLeadFields.includes("UF_CRM_LEAD_LIQPAY")) {

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


            }
        }

        // Устанавливаем обработчик события на изменение лида

        if (!this.bx24LeadEvent)
            BX24.callBind(
                'onCrmLeadUpdate',
                'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead'
            );



    }else{
        this.leadStart = null;
        this.leadFinish = null;
        this.lead_liq_pay_link_field_id = null;
        if (this.bx24LeadEvent) {
            BX24.callUnbind('onCrmLeadUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead');
        }

        //alert($("#selector_lead_start").prop('selectedIndex'));
        //alert("Не выбран чек лид");
        // удаляем обработчики событий на создание и изменение лида, если чек - лида выключен
        // BX24.callUnbind('onCrmLeadUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead');
        //BX24.callUnbind('onCrmLeadAdd', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead');

    }

    if (this.dealEvent){
        var res = app.CreateArUserDealStage();
        if(!res){
            alert("ОБеріть хоча б одне напревлення угод!");
           return res;
        }



        this.dealStart = $("#selector_deal_start").prop('value');
        this.dealFinish = $("#selector_deal_finish").prop('value');
        //alert($("#selector_lead_start").prop('selectedIndex'));
        if ( $("#selector_deal_start").prop('selectedIndex') == $("#selector_deal_finish").prop('selectedIndex') )  {
            alert ("Вы выбрали одинаковые стадии для СДЕЛКИ. Может быть разные нужно ?" );
            //throw new Error("my error message");
            //result = false;
        }
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
        //  СОЗДАЕМ ПОЛЕ ДЛЯ ЯЗЫКА КЛИЕНТА в ЛИК ПЕЙ
        var lang = "UF_CRM_DEAL_LIQ_LANG";
        this.deal_liq_pay_language_id = lang;
        if(!this.arDealFields.includes("UF_CRM_DEAL_LIQ_LANG")) {

           /* var field = "DEAL_LIQ_LANG";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_DEAL_LIQ_LANG");
            } else{
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }*/

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
                        console.dir(result.data());


                    }

                }
            );


        }
        else{
           /* var ident="UF_CRM_DEAL_LIQ_LANG";
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
            );*/
        }
        // конец создания поля ДЛЯ ЯЗЫКА КЛИЕНТА в ЛИК ПЕЙ

        if (!this.bx24DealEvent){
            BX24.callBind('onCrmDealUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');
            // var bind_deal_result1 = BX24.callBind('onCrmDealAdd',   'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');

        }


    }else{
        this.dealStart = null;
        this.dealFinish = null;
        this.deal_liq_pay_link_field_id = null;
        this.deal_liq_pay_language_id = null;
        if (this.bx24DealEvent) {
            BX24.callUnbind('onCrmDealUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');
        }

        // alert("Не выбран чек Сделки");
    }
    // проверка полей,  для ссылки Лик пей УТОЧНИТЬ ПО значению по умолчанию




    // BX24.callUnbind('onCrmDealAdd', 'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');

    return result;

}

application.prototype.finishInstallation = function(){
   // console.log("finishInstall", this);
    app.SetMethod();
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
        data['arUserDealStage'] = {};
        data['arUserDealStage'] = this.arUserDealStage;

        var userStage= this.arUserDealStage;


        BX24.callMethod('user.current', {}, function(result) {
                var user = result.data();

                params = {authParams, user, 'operation':'install',data, userStage};
                console.log('params', params);
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
       // data['arUserDealStage'] = {};

      //  data['arUserDealStage'] = this.arUserDealStage;
       // data['arDealStage'] =this.arDealStage;
        console.log("this.arUserDealStage",this.arUserDealStage);
        console.log(data);

        var arUserDealStage =JSON.stringify(this.arUserDealStage )//{"1":1,"2":4,"3":6};

        console.log(arUserDealStage);


        BX24.callMethod('user.current', {}, function(result) {
            var user = result.data();

            params = {authParams, user, 'operation':'install',data, arUserDealStage };
            console.log("param",params);
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
           // console.log(" BX24.callMethod('event.get',", result.answer.result);
            var res = result.answer.result;
            res.forEach(function (item, i, res) {
                if ((item.event == "ONCRMLEADUPDATE") && (item.handler == "https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=lead")) {

                    app.bx24LeadEvent = true;
                }
                if ((item.event == "ONCRMDEALUPDATE") && (item.handler == "https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal")) {

                    app.bx24DealEvent = true;
                }
            });
            console.log("Установленны ли ивенты", app.bx24LeadEvent, app.bx24DealEvent);

        }
    });

}

application.prototype.Start = function(){





    app.CheckLeadEvent("div_lead_stage");
    app.CheckDealEvent("div_deal_stage");
    app.CurrentUser();
    app.checkEvent();
    app.GetDealCategory();








}

app = new application();


