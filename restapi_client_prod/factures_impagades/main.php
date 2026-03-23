<?php
require_once("functions.php");
require_once("functionsLogin.php");

$fechaInit = '';
$fechaEnd = '';
$invoiceNum = '';
$invoices = array();
$lines = '';
$customerId = '';
$customerName = '';
$center = '';
$serie = '';
$documentType = '';

/*if (
    isset($_REQUEST['fechaInit']) || isset($_REQUEST['fechaEnd']) || isset($_REQUEST['invoiceNum'])
    || isset($_REQUEST['customerId']) || isset($_REQUEST['customerName']) || isset($_REQUEST['center']) || isset($_REQUEST['serie'])
    || isset($_REQUEST['documentType'])
) {
    $fechaInit = $_REQUEST['fechaInit'];
    $fechaEnd = $_REQUEST['fechaEnd'];
    $invoiceNum = $_REQUEST['invoiceNum'];
    $customerId = $_REQUEST['customerId'];
    $customerName = $_REQUEST['customerName'];
    $center = $_REQUEST['center'];
    $serie = $_REQUEST['serie'];
    $documentType = $_REQUEST['documentType'];

    $invoices = getInvoices($fechaInit, $fechaEnd, $invoiceNum, $customerId, $customerName, $center, $serie, $documentType);
}*/

if (
    isset($_REQUEST['fechaInit']) || isset($_REQUEST['fechaEnd']) || isset($_REQUEST['invoiceNum'])
    || isset($_REQUEST['customerId']) || isset($_REQUEST['customerName']) || isset($_REQUEST['center']) || isset($_REQUEST['serie'])
    || isset($_REQUEST['documentType'])
) {
    $url = "http://91.187.69.73:8080/restapi_prod/v1/unpaid_bills";

    $params = [
        "token" => "ABC123456", // Pon aquí el token si la API lo necesita
        "dateInit" => $_REQUEST['fechaInit'] ?? null,
        "dateEnd" => $_REQUEST['fechaEnd'] ?? null,
        "invoiceNum" => $_REQUEST['invoiceNum'] ?? null,
        "customerId" => $_REQUEST['customerId'] ?? null,
        "customerName" => $_REQUEST['customerName'] ?? null,
        "center" => $_REQUEST['center'] ?? null,
        "serie" => $_REQUEST['serie'] ?? null,
        "documentType" => $_REQUEST['documentType'] ?? "customer"
    ];

    // Configuración de la llamada HTTP POST
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($params),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === false) {
        echo "❌ Error al conectar con la API.";
        if (isset($http_response_header)) {
            echo "<pre>";
            print_r($http_response_header);
            echo "</pre>";
        }
        exit;
    }

    // Procesamos la respuesta JSON de la API
    $response = json_decode($result, true);

    if ($response && isset($response['data'])) {
        $invoices = $response['data'];

        // Aquí puedes imprimir o usar las facturas como quieras
        echo "<pre>";
        print_r($invoices);
        echo "</pre>";
    } else {
        echo "⚠️ Respuesta inesperada de la API:<br><pre>";
        print_r($response);
        echo "</pre>";
    }
}

?>

<html>

<head>
    <title>Factures impagades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="lib/DataTables/datatables.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="lib/DataTables/datatables.min.js"></script>
    <script src="https://kit.fontawesome.com/138685667d.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>

        $(document).ready(function () {
            $('#invoicesTable').DataTable({
                //Per mostrar els elements en castella
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json',
                },
            });

        });

        function decodeHTMLEntities(text) {
            var entities = [
                ['amp', '&'],
                ['apos', '\''],
                ['#x27', '\''],
                ['#x2F', '/'],
                ['#39', '\''],
                ['#47', '/'],
                ['lt', '<'],
                ['gt', '>'],
                ['nbsp', ' '],
                ['quot', '"']
            ];

            for (var i = 0, max = entities.length; i < max; ++i)
                text = text.replace(new RegExp('&' + entities[i][0] + ';', 'g'), entities[i][1]);

            return text;
        }

        function export_csv() {
            //Hem hagut de crear aquesta taula auxiliar oculta per tal de omplir-la amb les mateixes dades que la principal
            //pero com que hi ha la llibreria javascript que la pagina el llistat, no em deixa recuperar la totalitat, nomes la pagina 1
            var table = document.getElementById('tableCompleteAux');

            let csvContent = "data:text/csv;charset=utf-8,";

            for (var i = 0, row; row = table.rows[i]; i++) {
                for (var j = 0, col; col = row.cells[j]; j++) {
                    csvContent += decodeHTMLEntities(col.innerHTML) + ';';

                    if (row.cells.length == (j + 1)) {
                        csvContent += "\r\n";
                    }
                }
            }

            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "factures.csv");
            document.body.appendChild(link); // Required for FF

            link.click();
        }
    </script>
    <style>
        body {
            padding: 10px;
        }

        #logoContainer {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 30px;
            margin-left: 10px;
        }

        .logo {
            width: 300px;
        }

        /*Formulari*/
        #formDiv {
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

        #formInputsDiv span {
            color: #333;
            font-weight: bold;
        }

        #formInputsDiv input[type="date"],
        #formInputsDiv input[type="text"] {
            padding: 10px;
            margin: 0;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #formInputsDiv select {
            display: block;
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        #formInputsDiv button[type="submit"] {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 10px 20px;
            font-size: 16px;
        }

        /*Formulari fi */
    </style>
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

        <div id="logoContainer">
            <div id="logoDiv">
                <a href="index.php"><img src="img/logo.png" class="logo"></a>
            </div>
        </div>
        <div id="formDiv">
            <form action="main.php" method="get">
                <input type="hidden" name="action_page" value="fsdgr5g" />
                <div id="formInputsDiv">
                    <div class="form-row">
                        <div class="form-group">
                            <span>Fecha inicial</span>
                            <input type="date" name="fechaInit" value="<?php echo $fechaInit; ?>" />
                        </div>
                        <div class="form-group">
                            <span>Fecha final</span>
                            <input type="date" name="fechaEnd" value="<?php echo $fechaEnd; ?>" />
                        </div>
                        <div class="form-group">
                            <span>Tipus de factura</span>
                            <?php
                            $selected1 = '';
                            $selected2 = '';
                            $selected3 = '';
                            switch ($documentType) {
                                case "customer":
                                    $selected1 = 'selected';
                                    $selected2 = '';
                                    $selected3 = '';
                                    break;
                                case "supplier":
                                    $selected1 = '';
                                    $selected2 = 'selected';
                                    $selected3 = '';
                                    break;
                                default:
                                    $selected1 = '';
                                    $selected2 = '';
                                    $selected3 = 'selected';
                                    break;
                            }
                            ?>
                            <select name="documentType">
                                <option value="customer" <?php echo $selected1; ?>>Clients</option>
                                <option value="supplier" <?php echo $selected2; ?>>Proveïdors</option>
                                <option value="all" <?php echo $selected3; ?>>Tots</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <span>Núm. Factura</span>
                            <input type="text" name="invoiceNum" value="<?php echo $invoiceNum; ?>" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <span>ID client</span>
                            <input type="text" name="customerId" value="<?php echo $customerId; ?>" />
                        </div>
                        <div class="form-group">
                            <span>Nom client</span>
                            <input type="text" name="customerName" value="<?php echo $customerName; ?>" />
                        </div>
                        <div class="form-group">
                            <span>Centre</span>
                            <input type="text" name="center" value="<?php echo $center; ?>" />
                        </div>
                        <div class="form-group">
                            <span>Serie</span>
                            <input type="text" name="serie" value="<?php echo $serie; ?>" />
                        </div>
                    </div>
                    <div class="form-row">
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
        <div id="invoicesDiv" style="width:99%">
            <table id="invoicesTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Centre</th>
                        <th>Serie</th>
                        <th>ID client</th>
                        <th>Nom client</th>
                        <th>Núm factura</th>
                        <th>Import</th>
                        <th>Estat</th>
                        <th>Data factura</th>
                        <th>Data venciment</th>
                        <th>Data pagament</th>
                        <th>Dies fora de venciment</th>
                        <th>Dies de pagament</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $today = date('Y-m-d');

                    foreach ($invoices as $line) {
                        $invoiceState = '';
                        $styleLine = '';
                        $diff = 0;
                        $datePayment = '';
                        $diffPayment = 0;

                        #Si tenim data de pagament
                        if ($line['RgVtoVal'] != null) {
                            //Per tal de poder fer la diferencia de dies, cal que la data estigui en aquest format
                            $datePayment = $line['RgVtoVal']->format("Y-m-d");

                            #Vol dir que la factura esta pagada
                            $invoiceState = 'Pagat';
                            $styleLine = ' style = "color: green;" ';
                            $diff = '';


                            $dateInvoiceD = date_create($line['RegDatEmi']->format("Y-m-d"));
                            $datePaymentD = date_create($datePayment);
                            $diffPayment = date_diff($dateInvoiceD, $datePaymentD);
                            $differenceFormat = '%a';
                            $diffPayment = $diffPayment->format($differenceFormat);

                            //Un cop ja hem fet la resta, formategem la data en format europeu
                            $datePayment = $line['RgVtoVal']->format("d/m/Y");
                        } else {
                            $datetime1 = date_create($today);
                            $datetime2 = date_create($line['RgVtoDat']->format("Y-m-d"));
                            $diff = date_diff($datetime1, $datetime2);
                            $differenceFormat = '%a';
                            $diff = $diff->format($differenceFormat);

                            #Si la data de venciment es posterior a la data d'avui
                            #vol dir que encara no s'ha pagat pero esta dins del plaç
                            if ($line['RgVtoDat']->format("Y-m-d") > $today) {
                                $invoiceState = 'Encara dins de plaç';
                                $styleLine = ' style = "color: orange;" ';
                            }
                            #en canvi, si ja hem sobrepassat la data de venciment
                            #vol dir que la factura no ha estat pagada dins del plaç
                            else {
                                $invoiceState = 'Pendent de pagament';
                                $styleLine = ' style = "color: red;" ';
                            }
                        }

                        ?>
                        <tr <?php echo $styleLine; ?>>
                            <td>
                                <?php echo $line['RegCtrCod']; ?>
                            </td>
                            <td>
                                <?php echo $line['RegSer']; ?>
                            </td>
                            <td>
                                <?php echo $line['CliCod']; ?>
                            </td>
                            <td>
                                <?php echo utf8ize($line['RegCliNom']); ?>
                            </td>
                            <td>
                                <?php echo $line['RegNum']; ?>
                            </td>
                            <td>
                                <?php echo round($line['RgVtoImp'], 2); ?>
                            </td>
                            <td>
                                <?php echo $invoiceState; ?>
                            </td>
                            <td>
                                <?php echo $line['RegDatEmi']->format("d/m/Y"); ?>
                            </td>
                            <td>
                                <?php echo $line['RgVtoDat']->format("d/m/Y"); ?>
                            </td>
                            <td>
                                <?php echo $datePayment; ?>
                            </td>
                            <td>
                                <?php echo $diff; ?>
                            </td>
                            <td>
                                <?php echo $diffPayment; ?>
                            </td>
                        </tr>
                        <?php
                        $lines .= '
                    <tr>
                        <td>' . $line['RegCtrCod'] . '</td>
                        <td>' . $line['RegSer'] . '</td>
                        <td>' . $line['CliCod'] . '</td>
                        <td>' . rtrim(utf8ize($line['RegCliNom'])) . '</td>
                        <td>' . $line['RegNum'] . '</td>
                        <td>' . round($line['RgVtoImp'], 2) . '</td>
                        <td>' . $invoiceState . '</td>
                        <td>' . $line['RegDatEmi']->format("d/m/Y") . '</td>
                        <td>' . $line['RgVtoDat']->format("d/m/Y") . '</td>
                        <td>' . $datePayment . '</td>
                        <td>' . $diff . '</td>
                        <td>' . $diffPayment . '</td>
                    </tr>';

                    }

                    ?>
                </tbody>
                <tfoot>
                    <tr>
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
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>


        </div>

        <div id="exportDiv">
            <p><span>Exportar</span></p>
            <div id="csvExportDiv">
                <a href="javascript:export_csv();"><img src="img/csv_icon.png" style="width:50px;" /></a>

            </div>
        </div>

        <table id="tableCompleteAux" name="tableCompleteAux" style="display:none;">
            <tr>
                <td>Centre</td>
                <td>Serie</td>
                <td>ID client</td>
                <td>Nom client</td>
                <td>Núm factura</td>
                <td>Import</td>
                <td>Estat</td>
                <td>Data factura</td>
                <td>Data venciment</td>
                <td>Data pagament</td>
                <td>Dies fora de venciment</td>
                <td>Dies de pagament</td>
            </tr>
            <?php echo $lines; ?>
        </table>
        <?php
    }
    ?>

</body>

</html>