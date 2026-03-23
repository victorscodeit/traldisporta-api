<?php
require_once("functions.php");
require_once("functionsLogin.php");


$facturas = [];
$incidencias = [];
$fechaInit = '';
$fechaEnd = '';
$salesman = '';

if (isset($_REQUEST['fechaInit']) || isset($_REQUEST['fechaEnd']) || isset($_REQUEST['salesman'])) {
    $fechaInit = $_REQUEST['fechaInit'];
    $fechaEnd = $_REQUEST['fechaEnd'];
    $salesman = $_REQUEST['salesman'];

    $facturas = callApiGetFacturas($_REQUEST['fechaInit'], $_REQUEST['fechaEnd'], $_REQUEST['salesman']);

}


?>

<html>

<head>
    <title>Morosos</title>

    <!--llibreries cloud-->
    <!--<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>  -->
    <style>
        #page {
            margin: 10px;
        }

        /*STYLE DEL POPUP*/
        .overlay {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            transition: opacity 500ms;
            visibility: hidden;
            opacity: 0;
        }

        .overlay:target {
            visibility: visible;
            opacity: 1;
        }

        .popup {
            margin: 70px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            width: 90%;
            position: relative;
            transition: all 5s ease-in-out;
        }

        .popup h2 {
            margin-top: 0;
            color: #333;
            font-family: Tahoma, Arial, sans-serif;
        }

        .popup .close {
            position: absolute;
            top: 20px;
            right: 30px;
            transition: all 200ms;
            font-size: 30px;
            font-weight: bold;
            text-decoration: none;
            color: #333;
        }

        .popup .close:hover {
            color: #06D85F;
        }

        .popup .content {
            /*max-height: 30%;*/
            overflow: auto;
            padding: 5px;
        }

        @media screen and (max-width: 700px) {
            .box {
                width: 70%;
            }

            .popup {
                width: 70%;
            }
        }

        /*STYLE DEL POPUP*/

        .separacion {
            border: 1px solid black;
        }

        .borderDiv {
            border-top: 1px solid grey;
            border-bottom: 1px solid grey;
            border-right: 1px solid grey;
            border-left: 1px solid grey;
            padding: 5px;
        }

        #logoContainer {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 30px;
            //margin-left: 10px;
        }

        .logo {
            width: 300px;
        }

        /*Contingut popup*/
        body {
            margin: 0;
            padding: 0;
        }

        .container {
            display: grid;
            grid-template-rows: auto 1fr auto;
            grid-template-columns: 1fr;
        }

        .top-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .block {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 10px;
            box-sizing: border-box;
        }

        .bottom-block {
            margin-top: 5px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 10px;
            box-sizing: border-box;
        }

        /*Contingut popup*/

        /*Formulari*/
        #formDiv {
            width: 50%;
            text-align: left;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-right: 20px;
        }

        #formInputsDiv {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
        }

        #formInputsDiv span {
            color: #333;
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        #formInputsDiv input[type="date"],
        #formInputsDiv input[type="text"] {
            padding: 10px;
            border: none;
            outline: none;
            border-bottom: 1px solid #ccc;
            /* Línea en condiciones normales */
        }

        #formInputsDiv input[type="date"]:not(:focus),
        #formInputsDiv input[type="text"]:not(:focus) {
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #formInputsDiv button[type="submit"] {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 10px;
            /* Añade un padding de 10px al botón */
        }

        /*Formulari FI*/

        #divContainerIncidenciaDetail {
            max-height: 300px;
            /* Altura máxima a partir de la cual se activará el desplazamiento */
            overflow-y: auto;
            /* Activa la barra de desplazamiento vertical cuando el contenido excede la altura máxima */
        }
    </style>
    <!--llibreries localhost-->
    <!--DOCUMENTATION https://datatables.net/examples/index-->
    <link rel="stylesheet" href="lib/DataTables/datatables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="lib/DataTables/datatables.min.js"></script>
    <!--<script src='https://kit.fontawesome.com/a076d05399.js' crossorigin='anonymous'></script>-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>

        $(document).ready(function () {
            $('#morososTable').DataTable({
                //Per mostrar els elements en castella
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json',
                },
            });

        });

        function cleanPopup() {
            detailsContentDiv = document.getElementById('incidenciaDetalleContentDiv');
            detailsContentDiv.innerHTML = '';

            gestionsDiv = document.getElementById('gestionsListContentDiv');
            gestionsDiv.innerHTML = '';

            window.location.href = '#';

        }

        //La funcio ens permet generar html del detall d'una incidencia amb les dades que hem rebut del json
        function renderIncidenceDetail(jsonData) {
            jsonData = jsonData[0]

            //Eliminem els 0 dels milisegons
            fechaCreacion_str = jsonData['CinRegDat']['date'].split(".")[0];
            fechaCreacion = formatDate(fechaCreacion_str);
            fechaAsignacion_str = jsonData['CinSitDat']['date'].split(".")[0];
            fechaAsignacion = formatDate(fechaAsignacion_str);
            if (jsonData['CinSitDat']['date'] == '1753-01-01 00:00:00.000000') {
                fechaAsignacion = '';
            }

            //Segons el codi que rebem, mostrem un text o un altre
            ambito = jsonData['CinPIR'];
            switch (jsonData['CinPIR']) {
                case 'I':
                    ambito = 'Interna';
                    break;
                case 'P':
                    ambito = 'Pública';
                    break;
                case 'R':
                    ambito = 'Reclamación';
                    break;
                case 'G':
                    ambito = 'Gestión';
                    break;
                case 'C':
                    ambito = 'Sit. Conforme';
                    break;
                case 'A':
                    ambito = 'Sit. Asignado';
                    break;
            }

            //Segons el codi que rebem, mostrem un text o un altre
            situacion = jsonData['GinSit'];
            switch (jsonData['GinSit']) {
                case 1:
                    situacion = 'Pendiente';
                    break;
                case 2:
                    situacion = 'Gestión';
                    break;
                case 3:
                    situacion = 'Resuelta';
                    break;
                case 9:
                    situacion = 'Anulada';
                    break;
            }

            tipo = jsonData['AnoCod']; //sempre rebrem el codi 410 perque son el tipus d'incidencies que estem buscant
            switch (jsonData['AnoCod']) {
                case 410:
                    tipo = 'Gestion de cobro';
                    break;
            }

            //Generem el html amb les dades
            html = '<span>Detalle incidencia</span>';
            html += ' <div name="divTableIncidenciaDetail" id="divContainerIncidenciaDetail">';
            html += '   <table id="tableDetallesIncidencia">';
            html += '    <tbody>';
            html += '        <tr>';
            html += '            <td>Holding<br/>';
            html += '                <input type="text" id="inputHolding" name="inputHolding" readonly value="' + jsonData['HolCod'] + '"/>';
            html += '            </td>';
            html += '            <td>Empresa<br/>';
            html += '                <input type="text" id="inputEmpresa" name="inputEmpresa" readonly value="' + jsonData['EmpCod'] + '"/>';
            html += '            </td>';
            html += '            <td>Centro<br/>';
            html += '                <input type="text" id="inputCentro" name="inputCentro" readonly value="' + jsonData['CtrCod'] + '"/>';
            html += '            </td>';
            html += '            <td>Incidencia<br/>';
            html += '                <input type="text" id="inputIncidencia" name="inputIncidencia" readonly value="' + jsonData['CinCod'] + '"/>';
            html += '            </td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td>Fecha creación<br/>';
            html += '                <input type="text" id="inputFechaCreacion" name="inputFechaCreacion" readonly value="' + fechaCreacion + '"/>';
            html += '            </td>';
            html += '            <td>Fecha asignación<br/>';
            html += '                <input type="text" id="inputFechaAsignacion" name="inputFechaAsignacion" readonly value="' + fechaAsignacion + '"/>';
            html += '            </td>';
            html += '            <td>Usuario<br/>';
            html += '                <input type="text" id="inputUsuario" name="inputUsuario" readonly value="' + jsonData['CinRegUse'] + '"/>';
            html += '            </td>';
            html += '            <td>Asignado a<br/>';
            html += '                <input type="text" id="inputAsignado" name="inputAsignado" readonly value="' + jsonData['CinSitUse'] + '"/>';
            html += '            </td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td>Ambito</td>';
            html += '            <td colspan="3"><input type="text" id="inputAmbito" name="inputAmbito" readonly value="' + ambito + '"/></td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td>Situación</td>';
            html += '            <td colspan="3"><input type="text" id="inputSituacion" name="inputSituacion" readonly value="' + situacion + '"/></td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td>Tipo/Clase</td>';
            html += '            <td colspan="3"><input type="text" id="inputTipo" name="inputTipo" readonly value="' + tipo + '"/></td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td>Causa/Origen</td>';
            html += '            <td colspan="3"><input type="text" id="inputCausa" name="inputCausa" readonly value="' + jsonData['CauDes'] + '"/></td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td colspan="4">Descripción';
            html += '                <br/>';
            html += '                <input type="text" id="inputDesc1" name="inputDesc1" style="width: 100%" readonly value="' + jsonData['CinDes1'] + '"/><br/>';
            html += '                <input type="text" id="inputDesc2" name="inputDesc2" style="width: 100%" readonly value="' + jsonData['CinDes2'] + '"/><br/>';
            html += '                <input type="text" id="inputDesc3" name="inputDesc3" style="width: 100%" readonly value="' + jsonData['CinDes3'] + '"/>';
            html += '            </td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td colspan="4">Solución propuesta';
            html += '                <br/>';
            html += '                <input type="text" id="inputSolPropuesta1" name="inputSolPropuesta1" style="width: 100%" readonly value="' + jsonData['CinSoPro1'] + '"/>';
            html += '                <br/>';
            html += '                <input type="text" id="inputSolPropuesta2" name="inputSolPropuesta2" style="width: 100%" readonly value="' + jsonData['CinSoPro2'] + '"/>';
            html += '                <br/>';
            html += '                <input type="text" id="inputSolPropuesta3" name="inputSolPropuesta3" style="width: 100%" readonly value="' + jsonData['CinSoPro3'] + '"/>';
            html += '            </td>';
            html += '        </tr>';
            html += '        <tr>';
            html += '            <td colspan="4">Solución adoptada';
            html += '                <br/>';
            html += '                <input type="text" id="inputSolAdoptada1" name="inputSolAdoptada1" style="width: 100%" readonly value="' + jsonData['CinSoAdo1'] + '"/>';
            html += '                <br/>';
            html += '                <input type="text" id="inputSolAdoptada2" name="inputSolAdoptada2" style="width: 100%" readonly value="' + jsonData['CinSoAdo2'] + '"/>';
            html += '                <br/>';
            html += '                <input type="text" id="inputSolAdoptada3" name="inputSolAdoptada3" style="width: 100%" readonly value="' + jsonData['CinSoAdo3'] + '"/>';
            html += '            </td>';
            html += '        </tr>';
            html += '    </tbody>';
            html += ' </table>';
            html += '</div>';

            return html;
        }

        //Funcio ajax que crida al servidor per demanar el detall d'una incidencia
        function getGestioData(incidenceCode, gestioNum) {
			$.ajax({
				type: "POST",
				url: 'http://91.187.69.73:8080/restapi/v1/detail_incidence',
				contentType: "application/json",
				data: JSON.stringify({ incCode: incidenceCode, gestioNum: gestioNum }),
				dataType: "json",
				success: function (html) {
                    incidenceTableHtml = renderIncidenceDetail(html);

                    divContainer = document.getElementById("incidenciaDetalleContentDiv");
                    divContainer.innerHTML = incidenceTableHtml;
                }

            });
        }

        //Funcio que ens permet generar el html amb el llistat d'incidencies que hem rebut del json
        function renderIncidencesList(jsonData) {
            html = '<p>Listado de incidencias<p>';
            html += '<table id="incidenciasTable" class="display" >';
            html += '   <thead>';
            html += '        <tr>';
            html += '            <th>N&uacute;m</th>';
            html += '            <th>Fecha</th>';
            html += '            <th>Situaci&oacute;n</th>';
            html += '            <th></th>';
            html += '        </tr>';
            html += '    </thead>';
            html += '    <tbody>';
            for (line of jsonData) {
                date_incident_str = line['CinRegDat']['date'].split('.')[0];
                date_incident = formatDate(date_incident_str);

                estado = '';
                background_color = '';
                switch (line['CinSit']) {
                    case 1:
                        estado = 'Pendiente';
                        break;
                    case 2:
                        estado = 'En gestión';
                        background_color = " style='background-color: orange' ";
                        break;
                    case 3:
                        estado = 'Resuelta';
                        background_color = " style='background-color: green' ";
                        break;
                    case 4:
                        estado = 'Anulada';
                        break;
                }

                html += '        <tr>';
                html += '          <td>' + line['CinCod'] + '</td>';
                html += '          <td>' + date_incident + '</td>';
                html += '          <td ' + background_color + '>' + estado + '</td>';
                html += '          <td><a href="javascript:getGestionsList(' + line['CinCod'] + ')"><span class="material-symbols-outlined">zoom_in</span></a></td>';
                html += '        </tr>';
            }
            html += '    </tbody>';
            html += '</table>';

            return html;

        }

        //Aquesta funcio ens permet fer la crida al servidor per obtenir el llistat d'incidencies del client
        //i quan ho te, obra el popup on mostra aquesta informacio
        function openPopupIncidencias(customerCode) {
            //Recuperem els paremetres de la url
            const valores = window.location.search;
            const urlParams = new URLSearchParams(valores);
            var fechaInit = urlParams.get('fechaInit');
            var fechaEnd = urlParams.get('fechaEnd');

            $.ajax({
				type: "POST",
				url: 'functions.php',
				data: { action: 'all_incidences', customerCode: customerCode, dateInit: fechaInit, dateEnd: fechaEnd },
				dataType: "json",
				success: function (html) {
                    incidencesTableHtml = renderIncidencesList(html);

                    divContainer = document.getElementById("incidenciasListContentDiv");
                    divContainer.innerHTML = incidencesTableHtml;

                    //Configurem la taula perque carregui les llibreries js i css
                    $('#incidenciasTable').DataTable({
                        paging: false,
                        ordering: false,
                        info: false,
                        //Per mostrar els elements en castella
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json',
                        },
                    });

                    //Fem visible el popup
                    window.location = '#popup1';
                }

            });
        }

        function formatDate(date_str) {
            var fecha = new Date(date_str);
            var dia = fecha.getDate();
            var mes = fecha.getMonth() + 1;
            var any = fecha.getFullYear();
            var horas = fecha.getHours();
            var minutos = fecha.getMinutes();
            var segundos = fecha.getSeconds();

            if (dia < 10) {
                dia = '0' + dia;
            }

            if (mes < 10) {
                mes = '0' + mes;
            }

            if (horas < 10) {
                horas = '0' + horas;
            }

            if (minutos < 10) {
                minutos = '0' + minutos;
            }

            if (segundos < 10) {
                segundos = '0' + segundos;
            }

            var fechaFormateada = dia + '/' + mes + '/' + any + ' ' + horas + ':' + minutos + ':' + segundos;

            return fechaFormateada;
        }

        function renderGestionsList(jsonData) {
            html = '<p>Listado de gestiones<p>';
            html += '<table id="gestionsTable" class="display" >';
            html += '   <thead>';
            html += '        <tr>';
            html += '            <th>N&uacute;m</th>';
            html += '            <th>Fecha</th>';
            html += '            <th>Responsable</th>';
            html += '            <th>Situaci&oacute;n</th>';
            html += '            <th></th>';
            html += '        </tr>';
            html += '    </thead>';
            html += '    <tbody>';
            for (line of jsonData) {
                date_incident_str = line['GinRegDat']['date'].split('.')[0];
                date_incident = formatDate(date_incident_str);

                estado = '';
                switch (line['GinSit']) {
                    case 1:
                        estado = 'Pendiente';
                        break;
                    case 2:
                        estado = 'En gestión';
                        break;
                    case 3:
                        estado = 'Resuelta';
                        break;
                    case 4:
                        estado = 'Anulada';
                        break;
                }

                html += '        <tr>';
                html += '          <td>' + line['GinCod'] + '</td>';
                html += '          <td>' + date_incident + '</td>';
                html += '          <td>' + line['GinAsiUse'] + '</td>';
                html += '          <td>' + estado + '</td>';
                html += '          <td><a href="javascript:getGestioData(' + line['CinCod'] + ',' + line['GinCod'] + ')"><span class="material-symbols-outlined">zoom_in</span></a></td>';
                html += '        </tr>';
            }
            html += '    </tbody>';
            html += '</table>';

            return html
        }

        //Aquesta funcio ens permet llistar les diverses gestions que pot tenir una incidencia
        function getGestionsList(incidenceCode) {
        
				$.ajax({
					type: "POST",
					url: 'http://91.187.69.73:8080/restapi/v1/all_gestions',
					contentType: "application/json",
					data: JSON.stringify({ incCode: incidenceCode }),
					dataType: "json",
					success: function (html) {
						gestionsTableHtml = renderGestionsList(html);

                    divContainer = document.getElementById("gestionsListContentDiv");
                    divContainer.innerHTML = gestionsTableHtml;

                    //Configurem la taula perque carregui les llibreries js i css
                    $('#gestionsTable').DataTable({
                        paging: false,
                        ordering: false,
                        info: false,
                        //Per mostrar els elements en castella
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json',
                        },
                    });
                }

            });
        }

    </script>
</head>

<body>

    <?php

    $username = $_POST['username'];
    $password = $_POST['password'];
    $valid = false;

    //Si existeix aquest camp, vol dir que ens hem pogut loguear anteriorment i estem dins de la sessio
    if (isset($_GET['action_page']) && $_GET['action_page'] == 'fsdgr5g') {
        $valid = true;
    } else {
        //si no existeix el camp, vol dir que venim del index i ens hem de loguejar abans
        $valid = login($username, $password);
    }


    if (!$valid) {
        echo 'Nombre de usuario o contraseña incorrectos.';
        echo '<p>';
        echo '<a href="index.php">Tornar</a>';
        echo '</p>';
    } else {

        ?>


        <div id="page">
            <div id="logoContainer">
                <div id="logoDiv">
                    <a href="index.php"><img src="img/logo.png" class="logo"></a>
                </div>
            </div>


            <div id="formDiv">
                <form action="main.php" method="get">
                    <div id="formInputsDiv">
                        <div class="form-row">
                            <div class="form-group">
                                <span>Fecha inicial</span>
                                <input type="date" name="fechaInit" value="<?php echo $fechaInit; ?>" />
                                <input type="hidden" name="action_page" value="fsdgr5g" />
                            </div>
                            <div class="form-group">
                                <span>Fecha final</span>
                                <input type="date" name="fechaEnd" value="<?php echo $fechaEnd; ?>" />
                            </div>
                            <div class="form-group">
                                <span>Comercial</span>
                                <input type="text" name="salesman" value="<?php echo $salesman; ?>" />

                            </div>
                            <div class="form-group">
                                <div id="fechaSearch">
                                    <button type="submit">Buscar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <p></p>
            <div id="morososDiv">
                <table id="morososTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>C&oacute;digo</th>
                            <th>NIF</th>
                            <th>Nombre</th>
                            <th>Importe</th>
                            <th>Vto</th>
                            <th>a 30 dias</th>
                            <th>a 60 dias</th>
                            <th>a 90 dias</th>
                            <th>m&aacute;s 90 dias</th>
                            <th>Impagado</th>
                            <th>Fecha &uacute;timo pago</th>
                            <th>Comercial</th>
                            <th>Incidencia</th>
                            <th>Resp. gestió</th>
                            <th>Data gestió</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $amountTotal = 0;
                        $vtoTotal = 0;
                        foreach ($facturas as $companyCode => $lines) {
                            foreach ($lines as $customerCode => $line) {
                                //Nomes volem les linies que tenen un deute superior a 0
                                if ($line['amountUnpaid'] > 0) {
                                    $empresa_str = '';
                                    switch ($line['EmpCod']) {
                                        case 1:
                                            $empresa_str = 'TPE';
                                            break;
                                        case 2:
                                            $empresa_str = 'Traldis';
                                            break;
                                    }

                                    $hasIncidence = hasIncidence($line['customerCode'], $fechaInit, $fechaEnd);
                                    $hInc = false;
                                    $dGestio = '';
                                    $respGestio = '';

                                    if ($hasIncidence != false) {
                                        $hInc = true;
                                        $dGestio = $hasIncidence['GinRegDat']->format("d/m/Y");
                                        $respGestio = utf8_encode($hasIncidence['GinAsiUse']);
                                    }

                                    $day30 = '';
                                    $day60 = '';
                                    $day90 = '';
                                    $morethan90 = '';
                                    switch ($line['RgVtoNum']) {
                                        case 0:
                                            $day30 = '';
                                            $day60 = '';
                                            $day90 = '';
                                            $morethan90 = '';
                                            break;
                                        case 1:
                                            $day30 = 'X';
                                            $day60 = '';
                                            $day90 = '';
                                            $morethan90 = '';
                                            break;
                                        case 2:
                                            $day30 = '';
                                            $day60 = 'X';
                                            $day90 = '';
                                            $morethan90 = '';
                                            break;
                                        case 3:
                                            $day30 = '';
                                            $day60 = '';
                                            $day90 = 'X';
                                            $morethan90 = '';
                                            break;
                                        default:
                                            $day30 = '';
                                            $day60 = '';
                                            $day90 = '';
                                            $morethan90 = 'X';
                                            break;
                                    }

                                    $dateLastPayment = '';
                                    if ($line["lastPaymentDate"] != '') {
                                        $dateLastPayment = $line["lastPaymentDate"];
                                    }

                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo $empresa_str; ?>
                                        </td>
                                        <td>
                                            <?php echo $line['customerCode']; ?>
                                        </td>
                                        <td>
                                            <?php echo $line['customerNif']; ?>
                                        </td>
                                        <td>
                                            <?php echo $line['customerName']; ?>
                                        </td>
                                        <td>
                                            <?php echo round($line['amountUnpaid'], 2); ?>
                                        </td>
                                        <td>
                                            <?php echo $line['RgVtoNum']; ?>
                                        </td>
                                        <td>
                                            <?php echo $day30; ?>
                                        </td>
                                        <td>
                                            <?php echo $day60; ?>
                                        </td>
                                        <td>
                                            <?php echo $day90; ?>
                                        </td>
                                        <td>
                                            <?php echo $morethan90; ?>
                                        </td>
                                        <td>
                                            <?php echo ($line["unpaid"] == true) ? 'Si' : ''; ?>
                                        </td>
                                        <td>
                                            <?php echo $dateLastPayment; ?>
                                        </td>
                                        <td>
                                            <?php echo $line['userResp']; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($hInc == true) {
                                                ?>
                                                <a class='button'
                                                    href='javascript:openPopupIncidencias(<?php echo $line['customerCode']; ?>);'>
                                                    <!--<i class='fas fa-exclamation-triangle' style='color:orange'></i>-->
                                                    <img src="https://www.freeiconspng.com/uploads/alert-icon-alert-icon-12.jpg"
                                                        width="20" />
                                                </a>
                                                <?php
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $respGestio; ?>
                                        </td>
                                        <td>
                                            <?php echo $dGestio; ?>
                                        </td>

                                    </tr>
                                    <?php
                                    $amountTotal += $line['amountUnpaid'];
                                    $vtoTotal += $line['RgVtoNum'];
                                }

                            }
                        }


                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <?php echo round($amountTotal, 2); ?>
                            </td>
                            <td>
                                <?php echo $vtoTotal; ?>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>


            </div>

            <div id="popup1" class="overlay">
                <div class="popup">
                    <h2>Incidencias</h2>
                    <a id="closeButton" class="close" href="javascript:void(0)" onclick="cleanPopup();">&times;</a>
                    <div class="container">
                        <div class="top-container">
                            <div class="block">
                                <div id="incidenciasListContentDiv"></div>
                            </div>
                            <div class="block">
                                <div id="gestionsListContentDiv"></div>
                            </div>
                        </div>
                        <div class="bottom-block">
                            <div id="incidenciaDetalleContentDiv">
                                <div name="divTableIncidenciaDetail" id="divContainerIncidenciaDetail"></div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
            <?php
    }
    ?>

    </div>
</body>

</html>
<?php
function callApiHasIncidence($customerCode, $fechaInit, $fechaEnd) {
    $url = 'http://91.187.69.73:8080/restapi_prod/v1/morosos/has_incidence'; // Usa la ruta real de tu API

    $payload = json_encode([
        "customerCode" => $customerCode,
        "fechaInit" => $fechaInit,
        "fechaEnd" => $fechaEnd
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
function callApiGetFacturas($fechaInit, $fechaEnd, $salesman) {
    $url = 'http://91.187.69.73:8080/restapi_prod/v1/morosos/facturas_pendientes';
    
    $payload = json_encode([
        "fechaInit" => $fechaInit,
        "fechaEnd" => $fechaEnd,
        "salesman" => $salesman
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true); // O false si falla
}
?>