<?
require_once ("tools.php");
require_once("log.php");
require_once("pay.php");

?>

<!doctype html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NovaPoshta</title>

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

    <style>
        input {
        //height: 20px;
            margin: 5px;
            width:280px;
        }
        .addbutton {
            text-align: center;
            vertical-align:middle;
            font-size: 13px;
            width: 283px;
            border: 1px solid #70A9FD;
            -webkit-border-radius: 7px;
            -moz-border-radius: 7px;
            border-radius: 7px;
            cursor: pointer;
            margin: 2px 0 0 110px;
            color: #326DC5;
            padding: 4px;
            background-color:#BED6FF;
        }

        .deletebutton {
            width: 20px;
            height: 22px;
            cursor: pointer;
            margin: 5px;
            display:inline-block;
            background: url(delete.png) repeat;
            background-position: center center;
            background-repeat: no-repeat;
            position:absolute;
            top: 1px;
            left: 480px;
        }

        .add {
            position:relative;
        }
    </style>
</head>
<body>
<div class ="container-fluid" id="app">

    <div class="bs-callout bs-callout">
        <!-- <i class="fa fa-trophy pull-left fa-3x"></i>-->
        <p> <h4>Додаток  "Нова Пошта off." v2 встановлено      </h4> </p>


    </div>
    <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12" id ="pay">

        <p align = "center"> Користуйтесь  на здоров'я! <br>
            До  <? echo $html_data;?> <br>
            Якщо додаток Вам сподобався, Ви можете оплатити його <br>
            двома шляхами <br>
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
    <div class="alert alert-dismissable alert-warning hidden" id="error"></div>



    <div id="main">
        <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12" id="tab_js">
            <p align = "center" id="title"> ТАБЛИЦЯ відповідності статусів 'Нової Пошти' й угод Бітрікс24</p>

            <table width="100%" cellspacing="0" border="1" id="MainTable"></table>
        </div>

        <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12"><br>
            <table  border ="1" width="100%" id="tab_ttn_field">
                <thead id="user-fields" >
                <tr >
                    <th class="text-center"  width="50%">
                        API Ключі кабінету Нової Пошти
                    </th>
                    <th class="text-center" width="50">
                        Додаткові поля для Угоди
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td
                    <div id="add_field_area"></div>
                    </td>
                    <td class="text-center" style="top" >
                        <div id="TTN_FIELD"> Поле для ТТН</div>
                        <div id="DEL_COST"> Вартість доставки</div>
                        <div id="DEL_DATE">Дата доставки </div>
                        <div id="NP_STATUS">Статус доставки </div>
                        <div id="NP_CODE">Код доставки  </div>

                    </td>
                </tr>

                </tbody>
            </table>
        </div>
        <div id="form" class="container-fluid">
            <br>
            <a href="#" style="text-align: center" class="btn btn-primary btn-lg btn-save" onclick="app.finishInstallation();"  ;">Зберегти</a>
        </div>
    </div>






    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//api.bitrix24.com/api/v1/dev/"></script>
    <script type="text/javascript" src="js/application.js?<?php echo sha1(microtime(1))?>"></script>



    <script>

        //$('#pay').show();
        $(document).ready(function () {


            BX24.init(function (){ // вместо init

                console.log("init");
                app.GetDBData();
                $('#change').prop('checked',false);
                app.change();
                //app.Start();
            });

        });


    </script>


</div>
</body>
</html>


