function application(){
    this.category = [];
    this.np_key =[];

    this.deal_field_novaposhta_status;
    this.deal_field_novaposhta_code;
    this.bx24DealEvent;
    this.bx24DealEvent_V1;


    this.arB24DealCategory = {};
    this.dbResult=[];
    this.arStatusNovaPoshta = [
        ["1", " Нова пошта очікує надходження від відправника"],
        ["2", " Видалено"],
        ["3", " Номер не знайдено"],
        ["4", " Відправлення у місті ХХXХ. (Статус для міжобластных оідправлень)"],
        ["41", " Відправлення у місті ХХXХ. (Статус для услуг локал стандарт и локал экспресс - доставка в пределах города)"],
        ["5", " Відправлення прямує до міста YYYY."],
        ["6", " Відправлення у місті YYYY, орієнтовна доставка до ВІДДІЛЕННЯ-XXX dd-mm. Очікуйте додаткове повідомлення про прибуття."],
        ["7", " Прибув на відділення"],
        ["8", " Прибув на відділення"],
        ["9", " Відправлення отримано"],
        ["10", " Відправлення отримано %DateReceived% Протягом доби ви одержите SMS-повідомлення про надходження грошового переказу та зможете отримати його в касі відділення «Нова пошта»."],
        ["11", " Відправлення отримано %DateReceived%. Грошовий переказ видано одержувачу."],
        ["14", " Відправлення передано до огляду отримувачу"],
        ["101", " На шляху до одержувача"],
        ["102", " Відмова одержувача"],
        ["103", " Відмова одержувача"],
        ["104", " Змінено адресу"],
        ["105", " Припинено зберігання"],
        ["106", " Одержано і створено ЕН зворотньої доставки"],
        ["108", " Відмова одержувача"],
    ];
    this.arDealFields =[];
    this.dealCategoryFlags ={};

}

function addField () {
        var telnum = parseInt($('#add_field_area').find('div.add:last').attr('id').slice(3))+1;
        $('#add_field_area').append('<div id="add'+telnum+'" class="add"><label>№'+telnum+'</label><input type="text" class="add" width="120" name="val'+telnum+'" id="val'+telnum+'" onblur="writeFieldsVlues();" value=""/><div class="deletebutton" onclick="deleteField('+telnum+');"></div></div>');
    }
//onblur="writeFieldsVlues();"
function deleteField (id) {
    $('div#add'+id).remove();
    writeFieldsVlues();
}

function writeFieldsVlues () {
    var str = [];
    var tel = '';
    for(var i = 0; i<$("input.add").length; i++) {
        tel = $($("input.add")[i]).val();
       // console.log($("input.add")[i],"$(\"input.addl\")[i]",i);
        if (tel !== '') {
            str.push($($("input.add")[i]).val());
        }
    }
    app.np_key = str;
   // console.log(app.np_key,"app.np_key");
    $("input#values").val(str.join("|"));
}

var n=0;
function apiKeyPlus(){
    document.getElementById("div-api-key").innerHTML+='<br><input type=text id="np_api_key'+n+'" name="np_api_key'+n+'">';
    n++;
}

var UserFeild;

application.prototype.checkEvent = function(){

    app.bx24DealEvent = false;
    app.bx24DealEvent_V1 = false;

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

                if ((item.event == "ONCRMDEALUPDATE") && (item.handler == "https://cremaprodotti.com.ua/Bitrix24/skk/np2/event.php?item=deal")) {

                    app.bx24DealEvent = true;
                }

                if ((item.event == "ONCRMDEALUPDATE") && (item.handler == "https://cremaprodotti.com.ua/Bitrix24/skk/NovaPoshta/event.php")) {

                    app.bx24DealEvent_V1 = true;
                }
            });
           //  console.log("Установленны ли ивенты", app.bx24DealEvent_V1, app.bx24DealEvent);

        }
    });

}


application.prototype.change = function(){
   // console.log("change",$('#change').prop('checked'));
    if ($('#change').prop('checked')){
        $('#main').show();

    }
    else {
        $('#main').hide();
    }
}

application.prototype.addApiBlock = function(){
    var values =[],
        value,
        n=0,
        html_div;
    this.np_key = app.dbResult['NP_API_KEY'];
    values = (app.dbResult['NP_API_KEY']) ? app.dbResult['NP_API_KEY'].split("|") : [""] ;
   // console.log(app.dbResult['NP_API_KEY']," app.dbResult['NP_API_KEY']", values, "values");
    values.forEach ( function (value) {

            n=n+1;
            if (n == 1) {
                html_div = '<div id="add1" class="add">' +
                    '<label>№1</label>' +
                    '<input type="text" class="add" width="120" name="val1" id="val'+n+'" onblur="writeFieldsVlues();"  value="'+value+'"/>' +
                    '</div>';
            } else {
                html_div = html_div +
                    '<div id="add' + n + '" class="add">' +
                    '<label>№' + n + ' </label>' +
                    '<input type="text" class="add" width="120" name="val' + n + '" id="val'+n+'" onblur="writeFieldsVlues();"  value="' + value + '"/>' +
                    '<div class="deletebutton" onclick="deleteField(' + n + ');"></div>' +
                    '</div>';
            }
           // console.log(html_div);
    }
    );

    $("#add_field_area").html(html_div).append(
        '<div onclick="addField();" class="addbutton">Додати ще  один  Ключ</div>'+
        '<input type="hidden" name="values" id="values"  value="<?=$array?>"/>'
    );


}

application.prototype.setApiKeys = function(){
    var time;
    time = $("#add_field_area").html();
  //  console.log("time ",time);
    $("#values").val(app.dbResult['NP_API_KEY']);
    $("#add_field_area").empty().append(time);

}

application.prototype.displayErrorMessage = function(message, arSelectors) {
    for (selector in arSelectors) {
        $(arSelectors[selector]).html(message);
        $(arSelectors[selector]).removeClass('hidden');
    }
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
              //  console.dir(result.data());
                return true;
            }

        }
    );

}

application.prototype.DysplayUserFeild = function(UserField,field_array, db_field){
    //alert(UserField);
    //console.log(app.arDealFields);

    var deal_field = document.getElementById(UserField);
    var array = field_array;// app.arDealFields;
    var selectList = document.createElement("select");
    selectList.id = "selector_" + UserField;
    //selectList.onchange="alert('privet')";
    deal_field.appendChild(selectList);
    var option = document.createElement("option");
    option.value = "NO";
    option.text = "не обрано - СТВОРИТИ НОВЕ ('" + UserField+"')";
    selectList.appendChild(option);
    var ar=[];
    ar.push("NO");
 //   $("#"+ UserField + "  select").val("NO");
    $("#" + UserField + " select").val("NO");
   // console.log( array.length,"Длина массива");
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
    //console.log("GetDealField select ",select);
   // console.log(" app.dbResult[db_field] ", db_field," ",app.dbResult[db_field]);
  //  console.log("field_array.some(field_array => field_array.FIELD_NAME == app.dbResult[db_field])" , field_array.some(field_array => field_array.FIELD_NAME == app.dbResult[db_field]),);
    if (app.dbResult[db_field] && field_array.some(field_array => field_array.FIELD_NAME == app.dbResult[db_field]) ) {
        //console.log("Истина app.dbResult[db_field] && field_array.includes(app.dbResult[db_field])");
        $("#"+ UserField+ "  select").val(app.dbResult[db_field]);
    }

    $("#"+UserField).append(selectList);

     app.SetArDealFields(ar);

    // *** *********************
    //

}

application.prototype.ChangeCategoryBox = function(id){
    var key= id.split('_')[2];
    this.dealCategoryFlags[key] = $('#'+id).prop('checked');
     //console.log(id,key,this.dealCategoryFlags);

}


application.prototype.AddCollumnTitle = function(table, id_category, name_category){


  /*  var rows = document.getElementById(table).tBodies[0].rows;

        var row = rows[0];
        var newCell = row.insertCell(-1);
        var title ;

        title= name_category+ '<input type="checkbox" name="checkbox_category_'+ id_category+'" id="checkbox_category_'+ id_category+ '"  value = name_category   onchange = "app.ChangeCategoryBox(id)"><span id="name_category'+ id_category +'"></span>';

    newCell.innerHTML = title;

*/


}
application.prototype.AddCollumn = function(table, category_id, array, select_from_DB){

    var rows = document.getElementById(table).tBodies[0].rows;

    var row = rows[0];
    var newCell = row.insertCell(-1);
    var title ,
        name_category = app.arB24DealCategory[category_id]['name'];


    title= name_category+ '<input type="checkbox" name="checkbox_category_'+ category_id+'" id="checkbox_category_'+ category_id+ '"  value = name_category   onchange = "app.ChangeCategoryBox(id)"><span id="name_category'+ category_id +'"></span>';

    newCell.innerHTML = title;

        var rows = document.getElementById(table).tBodies[0].rows;
        for (var i = 1, l = rows.length; i < l; i++) {
            var row = rows[i];
            var newCell ;
            var np_id;
            np_id = document.getElementById("table-stage-body").rows[i].cells[0].innerHTML;

            newCell = row.insertCell(-1);
            newCell.id = "td-"+np_id+"-DEAL_STAGE_"+category_id;
            //console.log( newCell.id);


            var td = document.getElementById(newCell.id);


            //Create and append select list
            var selectList = document.createElement("select");
            selectList.id = "selector-"+np_id+"-"+category_id;
            td.appendChild(selectList);

           //  console.log("selectList ", selectList, "td ", td);
            // selectList.onchange="alert('privet')";



            var option = document.createElement("option");
            option.value = "NO";
            option.text = "  --";
            selectList.appendChild(option);

            //var sel = this.dbResult;

            array.forEach(function(item, i, array) {
                //alert( i + ": " + array[i]["STATUS_ID"] + " " + array[i]["NAME"] );

                var option = document.createElement("option");
                option.value = array[i]["STATUS_ID"];
                option.text = array[i]["NAME"];
                selectList.appendChild(option);
            });


            //*********** Выбор из списка стадий ту, котроая в БД - если категорий сделок 1 = category_id убираем
            select_from_DB = category_id+"_"+np_id;
            //console.log("select_from_DB = ", select_from_DB);
           if ((!app.dbResult[select_from_DB]) & (category_id == 0)){
                select_from_DB = np_id;


            }

            if (app.dbResult[select_from_DB]){
                //alert(app.dbResult[select_from_DB]);
                $('#selector-'+np_id+'-'+category_id).val(app.dbResult[select_from_DB]);
                $('#checkbox_category_'+category_id).prop('checked', true);


                app.ChangeCategoryBox("checkbox_category_"+category_id);
            }
        }


}

application.prototype.DisplayTable = function(){

    var cat_ammount = 2;//app.arB24DealCategory.length;




    var content = ' <thead>' +
                    '        <tr  h1>' +
                    '            <th class="text-center" width="50%" font-weight="bold" colspan="2" >Нова Пошта</th>' +
                    '            <th class="text-center" colspan="'+ cat_ammount + '" id="th1"> Бітрікс24 (Напрямки/Стадії)</th>'+
                    '        </tr>'+
                 ' </thead>';


    var table_stage='<tbody id="table-stage-body"><tr>' +
                 '            <th class=\"text-center\">Код НП</th>' +
                            ' <th class=\"text-center\" font-weight=\"bold\"  >Статус Нової Пошти</th>'+
                            '</tr>' ;

    for (var i = 0; i < app.arStatusNovaPoshta.length; i++) {
        table_stage +=
           //create row
            "<tr>" +
            "<td align='center'>" + app.arStatusNovaPoshta[i][0] + "</td>" +
            "<td >" + app.arStatusNovaPoshta[i][1] + "</td>" +
            "</tr>";
    }

    content=content+table_stage;
    $('#MainTable').append(content);
}





application.prototype.GetCategoryStage = function( id){

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

                if(result.more())
                    result.next();

               // console.log(result.data(),"Статусы категорий сделок", entity_id );
                app.arB24DealCategory[id]['stage']= result.data();
                app.AddCollumn("MainTable", id, result.data());

            }
        }
    );

}

application.prototype.CreateArUserDealStage = function(){
    var verify = false,
        np_key;
    this.arUserDealStage = {};

   // console.log("this.dealCategoryFlags",this.dealCategoryFlags);
    // console.log("this.dealCategoryFlags",this.dealCategoryFlags);
    for ( let category_id in this.dealCategoryFlags){

        // console.log("dealCategoryFlags", category_id);
        if (this.dealCategoryFlags[category_id]){
            this.arUserDealStage[category_id]={};

            for ( let i in this.arStatusNovaPoshta) {
                np_key = this.arStatusNovaPoshta[i][0];
               // console.log(np_key, category_id, " np key, category_id");

                var teg= "selector-" +np_key+ "-"+category_id;
                var znak =  document.getElementById(teg);
               // console.log(teg, znak.value,"znak.value");
                this.arUserDealStage[category_id][np_key] =  znak.value;
               // console.log(val," значение селект");
            }
            verify = true;
        }
    }
  //  console.log("this.arUserDealStage после цикла", this.arUserDealStage);
    return verify;
}

application.prototype.checkField = function(){

    var result = true;
    var res = app.CreateArUserDealStage();//создаем массив из выбранных по чек боксу категорий (категория - стадии по статусам НП)
    if(!res) {
        alert("Оберіть хоча б одне направлення угод!");

        return res;
    }
    writeFieldsVlues ();
    if(! $("input#values").val()){
        alert("Введіть хоча б один API ключ Нової Пошти!");
        //alert($('#val').val());
        return false;
    }
   // console.log($("#selector_TTN_FIELD").prop('selectedIndex'), $("#selector_TTN_FIELD").prop('value'), " TTN _FIELD");
    if (($("#selector_TTN_FIELD").prop('selectedIndex')>0) && ($("#selector_TTN_FIELD").prop('value')!="NO")) {
        this.deal_TTN_FIELD_id = $("#selector_TTN_FIELD").prop('value');

    } else  {
        // есть ли уже такое поле?
        this.deal_TTN_FIELD_id = "UF_CRM_TTN_FIELD";
     //   console.log(this.arDealFields, this.arDealFields.includes("UF_CRM_TTN_FIELD" ));
        if(!this.arDealFields.includes("UF_CRM_TTN_FIELD")) {

            var field = "TTN_FIELD";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_TTN_FIELD");
            } else{
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }


        }
    }

   // поле для стоимости доставки
    if (($("#selector_DEL_COST").prop('selectedIndex')>0) && ($("#selector_DEL_COST").prop('value')!="NO")) {
        this.deal_del_cost_id = $("#selector_DEL_COST").prop('value');
    }
    else {
        // есть ли уже такое поле?
        this.deal_del_cost_id = "UF_CRM_DEL_COST";

        if (!this.arDealFields.includes("UF_CRM_DEL_COST")) {

            var field = "DEL_COST";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_DEL_COST");
            } else {
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }
        }
    }
    // поле для даті доставки
    if (($("#selector_DEL_DATE").prop('selectedIndex')>0) && ($("#selector_DEL_DATE").prop('value')!="NO")) {
        this.deal_del_date_id = $("#selector_DEL_DATE").prop('value');


    }
    else {
        // есть ли уже такое поле?
        this.deal_del_date_id = "UF_CRM_DEL_DATE";

        if (!this.arDealFields.includes("UF_CRM_DEL_DATE")) {

            var field = "DEL_DATE";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_DEL_DATE");
            } else {
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }
        }
    }

    // поле для статусу доставки
    if (($("#selector_NP_STATUS").prop('selectedIndex')>0) && ($("#selector_NP_STATUS").prop('value')!="NO")) {
        this.deal_field_novaposhta_status = $("#selector_NP_STATUS").prop('value');


    }
    else {
        // есть ли уже такое поле?
        this.deal_field_novaposhta_status = "UF_CRM_NP_STATUS";

        if (!this.arDealFields.includes("UF_CRM_NP_STATUS")) {

            var field = "UF_CRM_NP_STATUS";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_NP_STATUS");
            } else {
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }
        }
    }

    // поле для коду доставки
    if (($("#selector_NP_CODE").prop('selectedIndex')>0) && ($("#selector_NP_CODE").prop('value')!="NO")) {
        this.deal_field_novaposhta_code = $("#selector_NP_CODE").prop('value');


    }
    else {
        // есть ли уже такое поле?
        this.deal_field_novaposhta_code = "UF_CRM_NP_CODE";

        if (!this.arDealFields.includes("UF_CRM_NP_CODE")) {

            var field = "DEL_DATE";
            var field_result = this.CreateDealField(field);
            if (field_result == false) {
                console.log("Ошибка создания поля Сделки UF_CRM_NP_CODE");
            } else {
                // console.log("Поле создано  UF_CRM_DEAL_LIQPAY");
            }
        }
    }


    return result;

}

application.prototype.finishInstallation = function(){
    // console.log("finishInstall", this);


    if (!app.checkField()) {
        console.log("Не пройдена проверка полей");

    }

    else{
        curapp = this;
        var api_keys = document.getElementById("values").value;
       // console.log (api_keys, "api");
        //  $('#save-btn').find('i').removeClass('fa-check').addClass('fa-spinner').addClass('fa-spin');
        var authParams = BX24.getAuth(),
            params= {},
            operation = {'operation':'install'},
            us={},
            data={},
            api_keys;
        data['deal_ttn_field_id'] = this.deal_TTN_FIELD_id;
        data['deal_del_cost_id'] = this.deal_del_cost_id;
        data['deal_del_date_id'] = this.deal_del_date_id;
        data['deal_field_novaposhta_status_id'] = this.deal_field_novaposhta_status;
        data['deal_field_novaposhta_code_id'] = this.deal_field_novaposhta_code;
        data['arStatusNovaPoshta'] =this.arStatusNovaPoshta;

        data['arUserDealStage'] = this.arUserDealStage;

        data['api_keys'] = api_keys;

       // console.log(data,'data');


        BX24.callMethod('user.current', {}, function(result) {
                var user = result.data();

                params = {authParams, user, 'operation':'install',data};
              //   console.log('params', params);
                $.post(
                    "application.php",
                    params,
                    function (data)
                    {
                        var answer = JSON.parse(data);
                        if (answer.status == 'error') {
                            console.log('error', answer.result);
                            curapp.displayErrorMessage('К сожалению, произошла ошибка сохранения настроек приложения. Попробуйте перезапустить приложение');
                        }
                        else {
                           // console.log(answer.result);
                            var db= answer.result;


                            //BX24.callBind('ONAPPUNINSTALL', 'http://www.b24go.com/rating/application.php?operation=uninstall');
                            if (!app.bx24DealEvent){
                                BX24.callBind('onCrmDealUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/np2/event.php?item=deal');
                                // var bind_deal_result1 = BX24.('onCrmDealAdd',   'https://cremaprodotti.com.ua/Bitrix24/skk/liqpay/event.php?item=deal');

                            }
                            if (app.bx24DealEvent_V1) {
                              //  console.log(" Внутри условия", app.bx24DealEvent_V1);
                                BX24.callUnbind('onCrmDealUpdate', 'https://cremaprodotti.com.ua/Bitrix24/skk/NovaPoshta/event.php');
                            }


                            BX24.installFinish();
                            $('#main').hide();
                            $('#change').prop('checked',false);

                            app.change();
                            //$('#pay').show();
                           // $('#main').hide();



                        }
                    }

                )
            }
        );

    };
}

application.prototype.GetDealCategory = function(){
   // var currapp = this;
    var category_count=0;

    BX24.callMethod(
        "crm.dealcategory.default.get",
        {},
        function(result)
        {
            if(result.error())
                console.error(result.error());


            else{
                //console.log(result.data(), "result.data");
                //app.AddCollumnTitle("MainTable",result.data()['ID'] ,result.data()['NAME']);
                app.arB24DealCategory[category_count] = {
                    id : result.data()['ID'],
                    name : result.data()['NAME'],
                  //  stage : app.GetCategoryStage( result.data()['ID'])
                };
                // console.log(result.data(), "result.data");



                //app.DisplayCategory(result.data()['ID'] ,result.data()['NAME']);
                // console.log(result.data()['ID'], " result.data()['ID']");

                app.GetCategoryStage( result.data()['ID']);//  название стадий  катергории

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

                            if(result1.more())
                                result1.next();
                           // console.log(result1.data(),"crm.dealcategory.list дополнительные category");

                            for ( let i=0; i<result1.data().length; i++){
                                //console.log(result1.data()[i]['ID']," ID новой категории");
                                category_count =category_count+1;
                                app.arB24DealCategory[result1.data()[i]['ID']] = {       //т.к 0 элемент заполнился из категории сделок по умолчанию

                                    id  : result1.data()[i]['ID'],
                                    name: result1.data()[i]['NAME'],
                                    //stage : app.GetCategoryStage( result1.data()[i]['ID'])
                                };
                               // console.log("цикл перебора массива категорий", n);
                                //app.AddCollumnTitle("MainTable",result1.data()[i]['ID'] ,result1.data()[i]['NAME']);

                                app.GetCategoryStage( result1.data()[i]['ID']);//  название стадий  катергории

                            }
                           // console.log(app.arB24DealCategory, "собранный массив категрий и статусов");
                            //app.DisplayTable();
                            $('#th1').attr('colspan',category_count+1);
                            $('#th2').attr('colspan',category_count+1);


                        }
                    }
                );
            }




        }
    );


    // console.log(this.arB24DealCategory, "Итог");
    return  app.arB24DealCategory;


}

application.prototype.SetArDealFields = function(array){
    this.arDealFields = array;
   // console.log( this.arDealFields, "из SetArDealFields");
}

application.prototype.GetDealField = function() {
    BX24.callMethod(
        "crm.deal.userfield.list",
        {
            order: { "SORT": "ASC" },
            filter: { "MANDATORY": "N" }
        },
        function(result) {
            if (result.error()){
                console.error(result.error());

            }

            else {
                if (result.more())
                    result.next();
                //app.SetArDealFields (result.data());

               // console.log(this.arDealFields,"из функции GetDealField");
                app.DysplayUserFeild("TTN_FIELD",result.data(),"TTN_FIELD_ID");
                app.DysplayUserFeild("DEL_COST",result.data(),"DEL_COST_ID");
                app.DysplayUserFeild("DEL_DATE",result.data(),"DEL_DATA_ID");
                app.DysplayUserFeild("NP_STATUS",result.data(),"DEAL_FIELD_NOVAPOSHTA_STATUS");
                app.DysplayUserFeild("NP_CODE",result.data(),"DEAL_FIELD_NOVAPOSHTA_STATUS_CODE");


            }
        }
    );
}

application.prototype.GetDBData = function(){
    var authParams = BX24.getAuth(),
        params= {},
        db_re;
    curapp = this;
    params = {authParams, 'operation':'preset'};
    // console.log("params GetDBDate", params);
    $.post(
        "application.php",
        params,
        function (data) {
            var answer = JSON.parse(data);
           // console.log (answer);
            if (answer.status == 'error' || answer.result == null) {
                console.log('error - ошибка предварительной установки значений. Возможно устанавливается впервые', answer);
                curapp.displayErrorMessage('К сожалению, предварительная установка значений невозможна. Возможно устанавливается впервые');

                //app.GetDealCategory();

                //app.GetDealField("DEAL_LIQ_PAY");

            }
            else {
                app.dbResult = answer.result;


            }
            app.Start();
        });
}



application.prototype.Start = function(){

    app.DisplayTable();
    app.GetDealCategory();
    app.GetDealField();
    //app.checkEvent();
    app.addApiBlock();
    app.checkEvent();




   // console.log(app.arDealFields," the end");

}

app = new application();
