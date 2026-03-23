<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
//require_once("functions.php");
require './lib/PHPMailer/src/PHPMailer.php';
require './lib/PHPMailer/src/SMTP.php';
require_once('./lib/TCPDF/tcpdf.php');


$emailList = array(
/*"carles@porta.ad",
"comptabilitat@traldisporta.com",
"admin1@porta.ad",
"admin@traldisporta.com",
"support@openmindsystems.com.es"*/
"victor.sancho.coma@gmail.com"
);
/*$emailList = array(
    "kadett2dev@gmail.com"
);*/

$emailDefault = 'victor.sancho.coma@gmail.com';
//$emailDefault = 'christian@openmindsystems.com.es';


$listBySalesmanEmpty = array();
$listBySalesman = array();

$today = date("Y-m-d");
$lastYear = date("Y-m-d", strtotime($today . "- 1 year"));

$path = 'http://localhost:8080/oms/morosos/';


//Cridem a la funcio que ens recupera totes les factures pendents de pagament
//$list = getFacturasPendientes($lastYear, $today, false, false);


    $fechaInit = $lastYear;
    $fechaEnd = $today;
    $salesman = false;

    $list = callApiGetFacturas($fechaInit, $fechaEnd, $salesman);
   


//Recorrem els resultats per tal guardar-los en 2 dicts, segons si tenen comercial assignat o no.
foreach ($list as $cliCod => $line) {
    $comercial = trim($line['userResp']);
    if (!empty($comercial)) {
        if (!isset($listBySalesman[$comercial])) {
            $listBySalesman[$comercial] = array();

            $listBySalesman[$comercial]["email"] = $line['userRespMail'];
            $listBySalesman[$comercial]["name"] = $line['userResp'];
            $listBySalesman[$comercial]["customers"] = array();

            array_push($listBySalesman[$comercial]["customers"], $line);
        } else {
            array_push($listBySalesman[$comercial]["customers"], $line);
        }
    } else {
        array_push($listBySalesmanEmpty, $line);
    }
}

//Comprovem la llista de clients que NO tenen un comercial assignat
if (count($listBySalesmanEmpty) > 0) {
    //Preparem l'objecte que ens permetra enviar el mail
    $mail = new PHPMailer();
    $mail->CharSet = 'UTF-8';
    $mail->IsSMTP();
    $mail->Host = 'smtp.serviciodecorreo.es';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->SMTPDebug = 2;
    $mail->SMTPAuth = true;
    $mail->Username = 'bot@porta.ad';
    $mail->Password = 'Vityaro2';
    $mail->SetFrom('bot@porta.ad');
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(PDF_MARGIN_LEFT, 15, PDF_MARGIN_RIGHT);
    $pdf->SetTitle('Llistat de morosos sense comercial assignat');
    $pdf->AddPage();

    //Estabin el separador de columnes
    $delimiter = ";";
    //Establim el nom i ruta on es guardara l'arxiu temporalment
    $filename = __DIR__ . '/pdf/morosos.pdf';

    //Titol de la pagina
    $title = "<p>Llistat de morosos</p>";
    //Establim aquesta tipus de font al titol
    $pdf->SetFont('times', 'B', 12);
    //Ho escribim al document
    $pdf->writeHTML($title, true, false, true, false, '');

    //La llibreria TCPDF no agafa els style del html, per tant, no em deixa posar un tipus de lletra en la capcalera de la tabla
    //Ni tampoc em deixa escriure el html de la taula a trocos
    //Per tant muntem 2 taules, una que tindra els titols de les columnes en negreta i lletra 12 i despres la taula amb
    //la informació amb un altre tipus de lletra
    $pdf->SetFont('times', 'B', 12);
    $tableHeader = "
    <table>
        <tr>
            <th >C&oacute;di</th>
            <th>Nom</th>
            <th>Import</th>
            <th>Venciments impagats</th>
            <th>data &uacute;tim pagament</th>
            <th>Gestor</th>
            <th>&Uacute;ltima gesti&oacute;</th>
            <th>&Uacute;ltim comentari</th>
        </tr>
    </table>";
    $pdf->writeHTML($tableHeader, true, false, true, false, '');

    $lines = '';

    //No se perque... pero a vegades m'ho posa dins de posicions diferents, quan hauria de ser sempre el mateix...
    foreach ($listBySalesmanEmpty as $item) {
        foreach ($item as $line) {
            $customerCod = $line['customerCode'];
            $customerName = $line['customerName'];
            $amountUnpaid = $line['amountUnpaid'];

            $unpaidTerms = $line['RgVtoNum'];
            $manager = '';
            $lastManagementDate = '';
            $lastComment = '';

            $hasIncidence = callApiHasIncidence($customerCod, $lastYear, $today);
            
			if ($hasIncidence != false) {
				if (!empty($hasIncidence['GinRegDat']['date'])) {
					$date = new DateTime($hasIncidence['GinRegDat']['date']);
					$lastManagementDate = $date->format("d/m/Y");
				}
                //$lastManagementDate = $hasIncidence['GinRegDat']->format("d/m/Y");
                $manager = utf8_encode($hasIncidence['GinAsiUse']);
                $lastComment = utf8_encode($hasIncidence['CinDes1']);
            }

            if ($line["lastPaymentDate"] != '') {
                $dateLastPayment = $line["lastPaymentDate"];
            }

            $lines .= "
        <tr>
            <td>" . $customerCod . "</td>
            <td>" . $customerName . "</td>
            <td>" . $amountUnpaid . "</td>
            <td>" . $unpaidTerms . "</td>
            <td>" . $dateLastPayment . "</td>
            <td>" . $manager . "</td>
            <td>" . $lastManagementDate . "</td>
            <td>" . $lastComment . "</td>
        </tr>
        ";
        }
    }

    //Generem la segona taula que contindra les linies amb l'informacio de morosos
    $t = "<table>";
    $t .= $lines;
    $t .= "
    </table>";

    //Aquesta taula li diem que la lletra sigui normal i de tamany 10
    $pdf->SetFont('times', '', 10);
    $pdf->writeHTML($t, true, false, true, false, '');
    $pdf->Output($filename, 'F');

    //Preparem el missatge que mostrarem en el mail
    $link = $path . 'main.php?fechaInit=' . $lastYear . '&fechaEnd=' . $today . '&salesman=' . $salesManName . '&action_page=fsdgr5g';
    $subject = "Llistat de clients morosos " . $lastYear . " / " . $today . " sense comercial assignat.";
    $body = "<span>Hola! </span></br>";
    $body .= "<span> En aquest email hi ha adjuntat el llistat de clients morosos que no tenen un comercial assignat.</span></br>";
    //$body .= "<span> Per m&eacute;s informaci&oacute; accedeix al <a href='" . $link . "'>link</a>.</span>";

    //Preparem els destinataris
    foreach ($emailList as $email) {
        $mail->AddAddress($email);
    }

    //Afegim l'assumpte al mail
    $mail->Subject = $subject;
    //Afegim el cos del missatge al mail
    $mail->MsgHTML($body);
    //Afegim l'arxiu adjunt al mail
    $mail->addAttachment($filename);

    //Enviem el mail
    if (!$mail->send()) {
        //Si hi ha hagut algun problema, saltara error
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
        //Si tot ha anat be, mostra el missatge de tot correcte
        echo 'Message has been sent';
    }

    //Eliminem fisicament l'arxiu csv que hem guardat temporalment
    unlink($filename);
}

//Comprovem la llista de clients que tenen un comercial assignat
if (count($listBySalesman) > 0) {
    foreach ($listBySalesman as $salesManName => $data) {
        //Preparem l'objecte que ens permetra enviar el mail
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->IsSMTP();
        $mail->Host = 'smtp.serviciodecorreo.es';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->SMTPDebug  = 2;
        $mail->SMTPAuth = true;
        $mail->Username = 'bot@porta.ad';
        $mail->Password = 'Vityaro2';
        $mail->SetFrom('bot@porta.ad');
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 15, PDF_MARGIN_RIGHT);
        $pdf->SetTitle('Llistat de morosos');
        $pdf->AddPage();

        //Estabin el separador de columnes
        $delimiter = ";";
        //Establim el nom i ruta on es guardara l'arxiu temporalment
        $filename = __DIR__ .'/pdf'.'/'.$salesManName.'.pdf';

        //Titol de la pagina
        $title = "<p>Llistat de morosos</p>";
        //Establim aquesta tipus de font al titol
        $pdf->SetFont('times', 'B', 12);
        //Ho escribim al document
        $pdf->writeHTML($title, true, false, true, false, '');

        //La llibreria TCPDF no agafa els style del html, per tant, no em deixa posar un tipus de lletra en la capcalera de la tabla
        //Ni tampoc em deixa escriure el html de la taula a trocos
        //Per tant muntem 2 taules, una que tindra els titols de les columnes en negreta i lletra 12 i despres la taula amb
        //la informació amb un altre tipus de lletra
        $pdf->SetFont('times', 'B', 12);
        $tableHeader = "
        <table>
            <tr>
                <th >C&oacute;di</th>
                <th>Nom</th>
                <th>Import</th>
                <th>Venciments impagats</th>
                <th>data &uacute;tim pagament</th>
                <th>Gestor</th>
                <th>&Uacute;ltima gesti&oacute;</th>
                <th>&Uacute;ltim comentari</th>
            </tr>
        </table>";  
        $pdf->writeHTML($tableHeader, true, false, true, false, '');          

        //Recuperem el mail del comercial, aquest el posarem en copia
        $salesmanMail = trim($data['email']);

        if (strlen($salesmanMail) == 0) {
            $salesmanMail = $emailDefault;
        }
        if ($salesManName == 'Manel Mila' || $salesManName == 'Ferran Aguilar') {
            $salesmanMail = $emailDefault;
        }

        $lines = '';
        //Recorrem les linies per recopilar la informacio necessaria
        foreach ($data['customers'] as $line) {
            $customerCod = $line['customerCode'];
            $customerName = $line['customerName'];
            $amountUnpaid = $line['amountUnpaid'];

            $unpaidTerms = $line['RgVtoNum'];
            $manager = '';
            $lastManagementDate = '';
            $lastComment = '';
    
            $hasIncidence = callApiHasIncidence($customerCod, $lastYear, $today);
			
            if ($hasIncidence != false) {
				if (!empty($hasIncidence['GinRegDat']['date'])) {
					$date = new DateTime($hasIncidence['GinRegDat']['date']);
					$lastManagementDate = $date->format("d/m/Y");
				}           
                $manager = utf8_encode($hasIncidence['GinAsiUse']);
                $lastComment = utf8_encode($hasIncidence['CinDes1']);
            }
    
            if ($line["lastPaymentDate"] != '') {
                $dateLastPayment = $line["lastPaymentDate"];
            }

            $lines .= "
            <tr>
                <td>" . $customerCod . "</td>
                <td>" . $customerName . "</td>
                <td>" . $amountUnpaid . "</td>
                <td>" . $unpaidTerms . "</td>
                <td>" . $dateLastPayment . "</td>
                <td>" . $manager . "</td>
                <td>" . $lastManagementDate . "</td>
                <td>" . $lastComment . "</td>
            </tr>
            ";             
        }

        //Generem la segona taula que contindra les linies amb l'informacio de morosos
        $t = "<table>";
        $t .= $lines;
        $t .= "
        </table>";    
    
        //Aquesta taula li diem que la lletra sigui normal i de tamany 10
        $pdf->SetFont('times', '', 10);
        $pdf->writeHTML($t, true, false, true, false, '');
        $pdf->Output($filename, 'F');      

        //Preparem el missatge que mostrarem en el mail
        $link = $path . 'main.php?fechaInit=' . $lastYear . '&fechaEnd=' . $today . '&salesman=' . $salesManName.'&action_page=fsdgr5g';
        $subject = "Llistat de clients morosos " . $lastYear . " / " . $today;
        $body = "<span>Hola " . $salesManName . "! </span></br>";
        $body .= "<span> Tens adjuntat el llistat de clients morosos.</span></br>";
        //$body .= "<span> Per m&eacute;s informaci&oacute; accedeix al <a href='" . $link . "'>link</a>.</span>";

        //Preparem els destinataris
        foreach ($emailList as $email) {
            $mail->AddAddress($email);
        }

        //Posem el comercial en copia
        $mail->addCC($salesmanMail);

        //Afegim l'assumpte al mail
        $mail->Subject = $subject;
        //Afegim el cos del missatge al mail
        $mail->MsgHTML($body);
        //Afegim l'arxiu adjunt al mail
        $mail->addAttachment($filename);

        //Enviem el mail
        if (!$mail->send()) {
            //Si hi ha hagut algun problema, saltara error
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            //Si tot ha anat be, mostra el missatge de tot correcte
            echo 'Message has been sent';
        }

        //Eliminem fisicament l'arxiu csv que hem guardat temporalment
        unlink($filename);  
    }
}

function callApiGetFacturas($fechaInit, $fechaEnd, $salesman) {
	//echo "$fechaInit, $fechaEnd, $salesman";
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

?>