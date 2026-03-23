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

if (
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
}

?>

<html>

<head>
    <title>Factures impagades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="lib/DataTables/datatables.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="lib/DataTables/datatables.min.js"></script>
    <script src='https://kit.fontawesome.com/a076d05399.js' crossorigin='anonymous'></script>
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
        #logoContainer {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 30px;
            margin-left: 10px;
        }

        .logo {
            width: 300px;
        }
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
                <!--<a href="index.php"><img src="img/logo.png" class="logo"></a>-->
                <a href="index.php"><img src="img/logo.png" class="logo"></a>
            </div>
        </div>


        <!--<form action="index.php" method="get">-->
        <form action="main.php" method="get">    
            <div id="formInputsDiv">
                <table>
                    <tr>
                        <td>
                            <span>Fecha inicial</span>
                        </td>
                        <td>
                            <input type="date" name="fechaInit" value="<?php echo $fechaInit; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td><span>Fecha final</span></td>
                        <td><input type="date" name="fechaEnd" value="<?php echo $fechaEnd; ?>" /></td>
                    </tr>
                    <tr>
                        <td><span>Tipus de factura</span></td>
                        <td>
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
                        </td>
                    </tr>
                    <tr>
                        <td><span>Núm. Factura</span></td>
                        <td><input type="text" name="invoiceNum" value="<?php echo $invoiceNum; ?>" /></td>
                        <input type="hidden" name="action_page" value="fsdgr5g" />
                    </tr>
                    <tr>
                        <td><span>ID client</span></td>
                        <td><input type="text" name="customerId" value="<?php echo $customerId; ?>" /></td>
                    </tr>
                    <tr>
                        <td><span>Nom client</span></td>
                        <td><input type="text" name="customerName" value="<?php echo $customerName; ?>" /></td>
                    </tr>
                    <tr>
                        <td><span>Centre</span></td>
                        <td><input type="text" name="center" value="<?php echo $center; ?>" /></td>
                    </tr>
                    <tr>
                        <td><span>Serie</span></td>
                        <td><input type="text" name="serie" value="<?php echo $serie; ?>" /></td>
                    </tr>
                    <tr>
                        <td>
                            <div id="fechaSearch">
                                <button type="submit">Buscar</button>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                </table>
            </div>
        </form>
        <p></p>
        <div id="invoicesDiv">
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