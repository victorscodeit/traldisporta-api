<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Nov-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/
// require_once("db_connection.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

date_default_timezone_set("Europe/Madrid");

class morosos
{

    private $conn;

    function __construct()
    {
       

    }


//Ens permet convertir un string a utf8
function utf8ize($d)
{
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string($d)) {
        return utf8_encode($d);
    }
    return $d;
}


//Ens converteix l'string d'una data al string d'un datetime
function parseDate($dateOrigin)
{
    if ($dateOrigin != false) {
        return $dateOrigin . "T00:00:00.000";
    } else {
        return $dateOrigin;
    }
}

//A partir d'un codi de client, ens torna un llistat del numeros de factures d'aquest client
function getInvoicesCustomer($customerCode)
{
    $invoices = array();
    $conn = connectionDb();

    $select_invoices = "
    SELECT distinct(r.RegNum) 
    FROM REGISTRO r 
    WHERE r.CliCod = '" . $customerCode . "' 
    AND r.HolCod = 0 AND r.EmpCod in (1,2) AND r.RegCtrCod in (8,25,80) AND r.RegTip = 'V' ";

    $invoices_result = sqlsrv_query($conn, $select_invoices);

    while ($fila = sqlsrv_fetch_array($invoices_result, SQLSRV_FETCH_ASSOC)) {
        array_push($invoices, $fila['RegNum']);
    }

    closeDb($conn);

    return $invoices;

}

//A partir d'un codi de client, retorna la data de l'ultim pagament que ha fet en qualsevol de les seves factures
/*function getLastPaymentCustomer($customerCode)
{
    $lastPaymentDate = '';
    $conn = connectionDb();

    $invoicesNumList = getInvoicesCustomer($customerCode);

    if (count($invoicesNumList) > 0) {
        $list = implode(",", $invoicesNumList);

        $select_payment = "
        SELECT TOP 1 o1.RgVtoVal
        FROM OPERACI1 o1 
        WHERE  o1.RegNum in (" . $list . ")

        ORDER BY o1.RgVtoVal DESC";

        $payment_result = sqlsrv_query($conn, $select_payment);

        if ($payment_result != false) {
            while ($fila = sqlsrv_fetch_array($payment_result, SQLSRV_FETCH_ASSOC)) {
                $lastPaymentDate = $fila['RgVtoVal'];
            }
        }

        closeDb($conn);
    }

    return $lastPaymentDate;

}*/

#Busquem les factures pendents (estat 1) i impagades (estat 3)
function getFacturasPendientes($dateInit = false, $dateEnd = false, $salesman = false, $lastPaymentData = true)
{
    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);

    $conn = connectionDb();

    $dateFilter = '';
    $dateFilter1 = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = " AND v.RgVtoPre BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
        $dateFilter1 = " AND c.CinRegDat BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    } else {
        if ($dateInit == false && $dateEnd != false) {
            $dateFilter = " AND v.RgVtoPre < '" . $dateEnd . "' ";
            $dateFilter1 = " AND c.CinRegDat < '" . $dateEnd . "' ";
        } else {
            if ($dateInit != false && $dateEnd == false) {
                $dateFilter = " AND v.RgVtoPre > '" . $dateInit . "' ";
                $dateFilter1 = " AND c.CinRegDat > '" . $dateEnd . "' ";
            }

        }
    }

    $salesmanFilter = '';
    if ($salesman != false || $salesman != '') {
        $salesmanFilter = ' AND u.UseCod like \'%' . $salesman . '%\' ';
    }

    #Holding. HoldCod = 0 Sempre -> (Grup Porta)
    #Empreses. EmpCod in (1,2) -> 1 = Traldisporta Express (ESP), 2 = Transports i Distribucions
    #Centres. RegCtrCod in (8,25,80) -> 8 = Barcelona (Empresa 1), 25 = Montferrer (Empresa 1), 80 = Andorra (Empresa 2)
    #Tipus de factura. RegTip = V = Factures de venta
    ####Estat del venciment. RgVtoEst in (1,3) -> Pendent i Impagat
	#Estat del venciment. RgVtoEst = 1 -> Pendent i Impagat
    #Taula registro -> Factures
    #Taula vencimie -> Vencimientos
    #Taula DEPARTAM -> Depertaments
    #Taula USUARIOS -> Usuaris
    $select_invoices = "
    SELECT distinct v.RegNum, 
        v.EmpCod, 
        v.RegCtrCod, 
        v.RegTip, 
        v.RegSer, 
        v.RgVtoNum, 
        v.RgVtoDat, 
        v.RgVtoPre, 
        v.RgVtoImp, 
        v.RgVtoEst, 
        r.CliCod, 
        r.RegCliNom, 
        r.RegCliNif, 
        d.DepoComiDe, 
        u.UseNet, 
        o.RgVtoVal
    FROM VENCIMIE v 
    INNER JOIN REGISTRO r ON v.RegNum = r.RegNum AND v.EmpCod = r.EmpCod AND v.RegCtrCod = r.RegCtrCod AND v.RegTip = r.RegTip AND v.RegSer = r.RegSer 
    INNER JOIN DEPARTAM d ON r.CliCod = d.CliCod 
    LEFT JOIN USUARIOS u ON d.DepoComiDe = u.UseCod 
    LEFT JOIN OPERACI1 o on r.regnum = o.RegNum and r.HolCod = o.HolCod and r.empCod = o.empCod and r.RegCtrCod = o.RegCtrCod and r.RegSer = o.RegSer AND o.RegTip = r.RegTip 
    WHERE 1=1 AND v.RgVtoEst = 1 " . $dateFilter . " " . $salesmanFilter . " 
    AND r.HolCod = 0 AND r.EmpCod in (1,2) AND r.RegCtrCod in (8,25,80) AND r.RegTip = 'V' AND v.RgVtoDat < DATEADD(DAY, -15, GETDATE()) 

    ";
    $select_invoices="SELECT
distinct v.RegNum, 
        v.EmpCod, 
        v.RegCtrCod, 
        v.RegTip, 
        v.RegSer, 
        v.RgVtoNum, 
        v.RgVtoDat, 
        v.RgVtoPre, 
        v.RgVtoImp, 
        v.RgVtoEst, 
        r.CliCod, 
        r.RegCliNom, 
        r.RegCliNif, 
        d.DepoComiDe, 
        u.UseNet,
    oa.RgVtoVal AS RgVtoVal
FROM VENCIMIE v
INNER JOIN REGISTRO r
    ON v.RegNum = r.RegNum
   AND v.EmpCod = r.EmpCod
   AND v.RegCtrCod = r.RegCtrCod
   AND v.RegTip = r.RegTip
   AND v.RegSer = r.RegSer
OUTER APPLY (
    SELECT TOP 1 d.DepoComiDe
    FROM DEPARTAM d
    WHERE d.CliCod = r.CliCod
      AND d.DepCod = 0
    ORDER BY d.DepoComiDe DESC
) d
LEFT JOIN USUARIOS u
    ON d.DepoComiDe = u.UseCod
OUTER APPLY (
    SELECT TOP 1 o1.RgVtoVal
    FROM OPERACI1 o1
    WHERE o1.RegNum   = r.RegNum
      AND o1.HolCod   = r.HolCod
      AND o1.EmpCod   = r.EmpCod
      AND o1.RegCtrCod= r.RegCtrCod
      AND o1.RegSer   = r.RegSer
      AND o1.RegTip   = r.RegTip
    ORDER BY o1.RgVtoVal ASC   -- p.ej. o1.RgVtoDat
) oa
WHERE v.RgVtoEst = 1
 
  AND r.HolCod = 0
  AND r.EmpCod IN (1,2)
  AND r.RegCtrCod IN (8,25,80)
  AND r.RegTip = 'V'
  AND v.RgVtoPre < DATEADD(DAY, -15, GETDATE())
  ORDER BY v.RgVtoImp ASC;
";

    $invoices = sqlsrv_query($conn, $select_invoices);


    $invoicesList = [];
    while ($fila = sqlsrv_fetch_array($invoices, SQLSRV_FETCH_ASSOC)) {
        $dPay = '';
        if ($fila['RgVtoPre'] != null) {
            $dPay = $fila['RgVtoPre']->format("d/m/Y");
        }

        //Comprovem si la linia correpon a una factura que te estat impagat
        $isUnpaid = false;
        if ($fila['RgVtoEst'] == 3) {
            $isUnpaid = true;
        }

        $companyCode = $fila['EmpCod'];
        $customerCode = $fila['CliCod'];

        if (!isset($invoicesList[$companyCode])) {
            $invoicesList[$companyCode] = array();
        }

        if (!isset($invoicesList[$companyCode][$customerCode])) {
            $invoicesList[$companyCode][$customerCode] = [
                "EmpCod" => $companyCode,
                "RegCtrCod" => $fila['RegCtrCod'],
                "RegTip" => $fila['RegTip'],
                "RegSer" => $fila['RegSer'],
                //"RegNum" => $fila['RegNum'],
                "RgVtoNum" => $fila['RgVtoNum'],
                //"RgVtoDat" => $fila['RgVtoDat'],
                //"RgVtoPre" => $fila['RgVtoPre'],
                //"RgVtoEst" => $fila['RgVtoEst'],
                "amountUnpaid" => $fila['RgVtoImp'],
                "customerCode" => $fila['CliCod'],
                "customerName" => utf8_encode($fila['RegCliNom']),
                "customerNif" => $fila['RegCliNif'],
                "userResp" => utf8_encode($fila['DepoComiDe']),
                "userRespMail" => utf8_encode($fila['UseNet']),
                "invoiceNums" => $fila['RegNum'],
                "unpaid" => $isUnpaid,
                "lastPaymentDate" => $dPay,
                "CinRegDat" => '',
                "CinSitUse" => '',
            ];
        } else {
            $invoicesList[$companyCode][$customerCode]["amountUnpaid"] += $fila['RgVtoImp'];
            $invoicesList[$companyCode][$customerCode]["RgVtoNum"] += $fila['RgVtoNum'];
            $invoicesList[$companyCode][$customerCode]["invoiceNums"] .= ',' . $fila['RegNum'];

            //En cas que sigui una factura d'estat impagada
            if ($isUnpaid == true) {
                //comprovem que el camp del dict si encara no contempa que aquest client tingui impagaments
                if ($invoicesList[$companyCode][$customerCode]["unpaid"] == false) {
                    //Actualitzem el camp dient que si que te impagaments aquest client
                    $invoicesList[$companyCode][$customerCode]["unpaid"] = true;
                }
            }

            $dPayAux = $invoicesList[$companyCode][$customerCode]["lastPaymentDate"];
            
            if ($dPay != '') {
                if ($dPayAux == '') {
					
                    $invoicesList[$companyCode][$customerCode]["lastPaymentDate"] = $dPay;
                } else {
                    
					$dateAux = strtotime(str_replace("/", "-", $dPayAux));
					$dateNew = strtotime(str_replace("/", "-", $dPay));
					//if( $fila['CliCod']==3707) echo  "$dPayAux < $dPay </br>";
					if ($dateNew < $dateAux) {
						
						$invoicesList[$companyCode][$customerCode]["lastPaymentDate"] = $dPay;
						//if( $fila['CliCod']==3707) echo  "= ".$dPay." </br>";
					}
                }
            }
			//if( $fila['CliCod']==3707) echo  " FINAL ".$invoicesList[$companyCode][$customerCode]["lastPaymentDate"]." </br>";
			
        }
    }
    // die();
    closeDb($conn);

    return $invoicesList;
}

#A partir d'un codi de client et diu si te alguna incidencia o no de tipo de gestion de cobro.
function hasIncidence($customerCode, $dateInit = false, $dateEnd = false)
{

    $conn = connectionDb();

    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);

    $dateFilter = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = " AND CinRegDat BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    } else {
        if ($dateInit == false && $dateEnd != false) {
            $dateFilter = " AND CinRegDat< '" . $dateEnd . "' ";
        } else {
            if ($dateInit != false && $dateEnd == false) {
                $dateFilter = " AND CinRegDat > '" . $dateInit . "' ";
            }

        }
    }


    $select_incidencias = "SELECT
        c1.GinAsiUse,
        c1.GinRegDat,
        c1.CinCod,
        LTRIM(RTRIM(ISNULL(c1.GinIns1, ''))) + ' ' +
        LTRIM(RTRIM(ISNULL(c1.GinIns2, ''))) + ' ' +
        LTRIM(RTRIM(ISNULL(c1.GinIns3, ''))) + ' ' +
        LTRIM(RTRIM(ISNULL(c1.GinIns4, ''))) AS CinDes1
    FROM CINCIDEN c
    LEFT JOIN CINCIDE1 c1 ON c.CinCod = c1.CinCod
    WHERE c.CinRef = '" . $customerCode . "'
      AND c.AnoCod = 410
      AND c.EmpCod IN (1,2)
      AND c.CinSit = 2
    ORDER BY c1.GinModDat DESC";
    /*if($customerCode=='2697'){
    print_r($select_incidencias);
	die();
	}*/

    $incidents = sqlsrv_query($conn, $select_incidencias);

    if ($incidents) {
        $rows = sqlsrv_has_rows($incidents);
        if ($rows === true){
            while ($fila = sqlsrv_fetch_array($incidents, SQLSRV_FETCH_ASSOC)) {
                # Amb LEFT JOIN pot haver-hi fila de CINCIDEN sense CINCIDE1: GinRegDat null. Saltem.
                if (!empty($fila['GinRegDat']) && $fila['GinRegDat'] instanceof \DateTimeInterface) {
                    return $fila;
                }
            }
        }
        return false;
    }
    return false;
}

/** Formata GinRegDat (sqlsrv DateTime) per a sortida; buit si és null. */
function formatMorosoGinRegDat($ginRegDat)
{
    if ($ginRegDat instanceof \DateTimeInterface) {
        return $ginRegDat->format('d/m/Y');
    }
    return '';
}

#A partir d'un codi de client retorna el llistat d'incidencies de tipo gestion de cobro
#AnoCod = 410 => Gestiones de cobro
function getIncidencias($customerCode, $dateInit = false, $dateEnd = false)
{

    $incidencias = [];
    $conn = connectionDb();

    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);

    $dateFilter = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = " AND CinRegDat BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    } else {
        if ($dateInit == false && $dateEnd != false) {
            $dateFilter = " AND CinRegDat< '" . $dateEnd . "' ";
        } else {
            if ($dateInit != false && $dateEnd == false) {
                $dateFilter = " AND CinRegDat > '" . $dateInit . "' ";
            }

        }
    }

    $select_incidencias = "SELECT distinct(c1.CinCod), c.AnoCod, CinRegDat, CinSit, c1.CtrCod 
    FROM CINCIDEN c 
    INNER JOIN CINCIDE1 c1 ON c.CinCod = c1.CinCod 
    WHERE AnoCod = 410 AND GinCliCod = '" . $customerCode . "' " . $dateFilter . " 
    AND c.HolCod = 0 AND c.EmpCod in (1,2) AND c.CtrCod in (8,25,80) 
    ORDER BY CinRegDat ASC";

    $incidents = sqlsrv_query($conn, $select_incidencias);

    while ($fila = sqlsrv_fetch_array($incidents, SQLSRV_FETCH_ASSOC)) {
        array_push($incidencias, $fila);

    }

    closeDb($conn);

    echo json_encode(utf8ize($incidencias));

}


function getGestionList($incidenceCode)
{
    $conn = connectionDb();

    $select_incidencia = "SELECT c.CinCod,
    c1.GinAsiUse,
    c1.GinCod,
    c1.GinRegDat,
    c1.GinSit
    FROM CINCIDEN c 
    INNER JOIN CINCIDE1 c1 ON c.CinCod = c1.CinCod
    INNER JOIN CAUSA ca ON ca.CauCod = c.CauCod 
    WHERE c.CinCod = '" . $incidenceCode . "'  AND AnoCod = 410 
    AND c.HolCod = 0 AND c.EmpCod in (1,2) AND c.CtrCod in (8,25,80) ";

    $incidents = sqlsrv_query($conn, $select_incidencia);

    $incidenceData = [];
    while ($fila = sqlsrv_fetch_array($incidents, SQLSRV_FETCH_ASSOC)) {
        array_push($incidenceData, $fila);

    }

    closeDb($conn);

    //ATENCIO!!! QUAN APAREIX UN RESULTAT DE MES D'UNA LINIA, DESPRES QUAN INTENTES IMPRIMIR EL DETALL
    //NO CARREGA LA INFO... PER TANT HO HE CAPAT PERQUE NOMES SURTI 1 RESULTAT
    //PER TANT, REVISAR!!
    if (count($incidenceData) > 0){
        echo json_encode(utf8ize(array($incidenceData[0])));
    }
    else{
        echo json_encode([]);
    }

}

//A partir d'el codi d'incidencia, i el numero de gestio, ens retorna el detall de la gestio
function getGestionInfo($incidenceCode, $gestionNum = false)
{
    
    $conn = connectionDb();

    $gNum = '';
    if ($gestionNum != false and $gestionNum != ''){
        $gNum = ' AND c1.GinCod = '.$gestionNum." ";
    }

    $select_incidencia = "SELECT c.CinRegDat,
    c.CinSitDat,
    c.CinPIR,
    c.CinSit,
    c.AnoCod,
    c.HolCod,
    c.EmpCod,
    c.CtrCod,
    c.CinCod,
    c.CinRegUse,
    c.CinSitUse,
    c1.GinAsiUse,
    c1.GinCod,
    c1.GinRegDat,
    c1.GinSit,
    ca.CauDes,
    c.CinDes1,
    c.CinDes2,
    c.CinDes3,
    c.CinSoPro1,
    c.CinSoPro2,
    c.CinSoPro3,
    c.CinSoAdo1,
    c.CinSoAdo2,
    c.CinSoAdo3
    FROM CINCIDEN c 
    INNER JOIN CINCIDE1 c1 ON c.CinCod = c1.CinCod
    INNER JOIN CAUSA ca ON ca.CauCod = c.CauCod 
    WHERE c.CinCod = '" . $incidenceCode . "' ".$gNum." AND AnoCod = 410 
    AND c.HolCod = 0 AND c.EmpCod in (1,2) AND c.CtrCod in (8,25,80) ";

    $incidents = sqlsrv_query($conn, $select_incidencia);

    $incidenceData = [];
    while ($fila = sqlsrv_fetch_array($incidents, SQLSRV_FETCH_ASSOC)) {
        array_push($incidenceData, $fila);

    }

    closeDb($conn);

    echo json_encode(utf8ize($incidenceData));

}

function sendReportMorosospdf(){

		$emailList = array(
	"carles@porta.ad",
	"comptabilitat@traldisporta.com",
	"admin1@porta.ad",
	"admin@traldisporta.com",
	"jordi.olle@porta.ad",
	"victor@openmindsystems.com.es"
	);
	$emailList = array(
		"victor.sancho.coma@gmail.com"
	);

	$emailDefault = 'ruben@porta.ad';
	$emailDefault = 'victor.sancho.coma@gmail.com';


	$listBySalesmanEmpty = array();
	$listBySalesman = array();

	$today = date("Y-m-d");
	$lastYear = date("Y-m-d", strtotime($today . "- 15 day"));

	$path = 'http://localhost:8080/oms/morosos/';


	//Cridem a la funcio que ens recupera totes les factures pendents de pagament
	$list = $this->getFacturasPendientes($lastYear, $today, false, false);
	$list = $list[1];

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
		foreach ($listBySalesmanEmpty as $line) {
			
			//foreach ($item as $line) {
				$customerCod = $line['customerCode'];
				$customerName = $line['customerName'];
				$amountUnpaid = $line['amountUnpaid'];

				$unpaidTerms = $line['RgVtoNum'];
				$manager = '';
				$lastManagementDate = '';
				$lastComment = '';

				$hasIncidence = $this->hasIncidence($customerCod, $lastYear, $today);
				if ($hasIncidence != false) {
					$lastManagementDate = formatMorosoGinRegDat($hasIncidence['GinRegDat'] ?? null);
					$manager = utf8_encode($hasIncidence['GinAsiUse'] ?? '');
					$lastComment = utf8_encode($hasIncidence['CinDes1'] ?? '');
				}
				$dateLastPayment="";	
				if ($line["lastPaymentDate"] != '') {
					$dateLastPayment = $line["lastPaymentDate"];
				}
				$manager=$line['userResp'];
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
			//}
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
		//$link = $path . 'main.php?fechaInit=' . $lastYear . '&fechaEnd=' . $today . '&salesman=' . $salesManName . '&action_page=fsdgr5g';
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
		
				$hasIncidence = $this->hasIncidence($customerCod, $lastYear, $today);
				if ($hasIncidence != false) {
					$lastManagementDate = formatMorosoGinRegDat($hasIncidence['GinRegDat'] ?? null);
					$manager = utf8_encode($hasIncidence['GinAsiUse'] ?? '');
					$lastComment = utf8_encode($hasIncidence['CinDes1'] ?? '');
				}
		
				if ($line["lastPaymentDate"] != '') {
					$dateLastPayment = $line["lastPaymentDate"];
				}
				$manager=$line['userResp'];
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
			//$mail->addCC($salesmanMail);

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
}
function sendReportMorosos(){


	$emailList = array(
	/*"carles@porta.ad",
	"comptabilitat@traldisporta.com",
	"admin@traldisporta.com",
	"jordi.olle@porta.ad",
	"victor@openmindsystems.com.es",
	"ruben@porta.ad"*/
    'victor.sancho.coma@gmail.com'
	);

    $emailDefault = 'victor.sancho.coma@gmail.com';

    $listBySalesmanEmpty = array();
    $listBySalesman = array();

    $today = date("Y-m-d");
    $lastYear = date("Y-m-d", strtotime($today . "- 15 day"));

    $path = 'http://localhost:8080/oms/morosos/';

    // Recupera facturas pendientes
    $list = $this->getFacturasPendientes($lastYear, $today, false, false);
    $list_comercial = $list[1];

    // Reparto por comercial asignado o sin comercial
    foreach ($list_comercial as $cliCod => $line) {

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

    // Sólo enviamos si hay clientes sin comercial
    if (count($listBySalesmanEmpty) > 0) {

        // ---------- Mailer ----------
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

        // Asegurar carpeta de salida
        $outDir = __DIR__ . '/pdf';
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        // ---------- Definiciones comunes ----------
        $archivos_generados = array();
        $headersGeneral = ['Empresa','Codi','Nom','Import','Venciments Impagats','Primer venciment impagat','Gestor','Data última gestió','Última gestió'];
        $headersCompany  = ['Codi','Nom','Import','Venciments Impagats','Primer venciment impagat','Gestor','Data última gestió','Última gestió'];

        // ARRAY PARA LÍNEAS DEL GENERAL (lo ordenaremos después)
        $generalRows = [];

        // ---------- Generar Excel GENERAL ----------
        $xlsxPathGeneral = $outDir . '/morosos_general_' . $lastYear . "_" . $today . '.xlsx';
        $spreadsheetGeneral = new Spreadsheet();
        $sheetGeneral = $spreadsheetGeneral->getActiveSheet();
        $sheetGeneral->setTitle('General');

        // Escribir cabeceras
        $col = 1;
        foreach ($headersGeneral as $head) {
            $sheetGeneral->setCellValueByColumnAndRow($col, 1, $head);
            $col++;
        }
        // Negrita cabecera
        $sheetGeneral->getStyle('A1:I1')->getFont()->setBold(true);

        // ---------- Generar Excel por compañía + alimentar GENERAL ----------
        foreach ($list as $codCompany => $company) {
            $nomCompany = ($codCompany == 1) ? "Traldisporta_Express" : "Traldisporta_Distribucions";

            // ORDENAR $company POR amountUnpaid DE MAYOR A MENOR
            $rowsCompany = array_values($company);
            usort($rowsCompany, function($a, $b) {
                $aImp = (float)str_replace([',','.',' '], ['','.',''], $a['amountUnpaid'] ?? 0);
                $bImp = (float)str_replace([',','.',' '], ['','.',''], $b['amountUnpaid'] ?? 0);
                // Descendente
                if ($aImp == $bImp) return 0;
                return ($aImp < $bImp) ? 1 : -1;
            });

            $xlsxPath = $outDir . '/morosos_' . $nomCompany . '_' . $lastYear . "_" . $today . '.xlsx';
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($nomCompany);

            // Cabeceras por compañía
            $col = 1;
            foreach ($headersCompany as $head) {
                $sheet->setCellValueByColumnAndRow($col, 1, $head);
                $col++;
            }
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);

            $rowCompany = 2;

            // USAR $rowsCompany YA ORDENADO
            foreach ($rowsCompany as $cliCod => $line) {
                $customerCod = $line['customerCode'];
                $customerName = $line['customerName'];
                $amountUnpaid = $line['amountUnpaid'];
                $unpaidTerms = $line['RgVtoNum'];

                $manager = '';
                $lastManagementDate = '';
                $lastComment = '';

                $hasIncidence = $this->hasIncidence($customerCod, $lastYear, $today);
                if ($hasIncidence != false) {
                    $lastManagementDate = formatMorosoGinRegDat($hasIncidence['GinRegDat'] ?? null);
                    $manager = utf8_encode($hasIncidence['GinAsiUse'] ?? '');
                    $lastComment = $this->sanitizeComment(utf8_encode($hasIncidence['CinDes1'] ?? ''));
                }
                $dateLastPayment = '';
                if (!empty($line["lastPaymentDate"])) {
                    $dateLastPayment = $line["lastPaymentDate"];
                }
                $manager = $line['userResp'];

                // Normalizar importe a número
                $importe = (float)str_replace([',','.',' '], ['','.',''], $amountUnpaid ?? 0);

                // ---- Fila en Excel de compañía ----
                $sheet->setCellValueByColumnAndRow(1, $rowCompany, $customerCod ?? '');
                $sheet->setCellValueByColumnAndRow(2, $rowCompany, $customerName ?? '');
                $sheet->setCellValueByColumnAndRow(3, $rowCompany, $importe);
                $sheet->getStyleByColumnAndRow(3, $rowCompany)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->setCellValueByColumnAndRow(4, $rowCompany, (int)($unpaidTerms ?? 0));
                $sheet->setCellValueByColumnAndRow(5, $rowCompany, $dateLastPayment ?? '');
                $sheet->setCellValueByColumnAndRow(6, $rowCompany, $manager ?? '');
                $sheet->setCellValueByColumnAndRow(7, $rowCompany, $lastManagementDate ?? '');
                $sheet->setCellValueByColumnAndRow(8, $rowCompany, $lastComment ?? '');

                $rowCompany++;

                // ---- Añadir fila al ARRAY GENERAL (no escribir aún) ----
                $generalRows[] = [
                    'company'            => $nomCompany ?? '',
                    'customerCod'        => $customerCod ?? '',
                    'customerName'       => $customerName ?? '',
                    'importe'            => $importe,
                    'unpaidTerms'        => (int)($unpaidTerms ?? 0),
                    'dateLastPayment'    => $dateLastPayment ?? '',
                    'manager'            => $manager ?? '',
                    'lastManagementDate' => $lastManagementDate ?? '',
                    'lastComment'        => $lastComment ?? '',
                ];
            }

            // Autosize columnas compañía
            foreach (range('A','H') as $colL) {
                $sheet->getColumnDimension($colL)->setAutoSize(true);
            }

            // Guardar fichero compañía
            $writer = new Xlsx($spreadsheet);
            $writer->save($xlsxPath);
            $archivos_generados[] = $xlsxPath;
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        // ORDENAR GENERAL POR IMPORTE DE MAYOR A MENOR
        usort($generalRows, function($a, $b) {
            if ($a['importe'] == $b['importe']) return 0;
            return ($a['importe'] < $b['importe']) ? 1 : -1;
        });

        // Volcar filas ordenadas al Excel GENERAL
        $row = 2;
        foreach ($generalRows as $g) {
            $sheetGeneral->setCellValueByColumnAndRow(1, $row, $g['company']);
            $sheetGeneral->setCellValueByColumnAndRow(2, $row, $g['customerCod']);
            $sheetGeneral->setCellValueByColumnAndRow(3, $row, $g['customerName']);

            $sheetGeneral->setCellValueByColumnAndRow(4, $row, $g['importe']);
            $sheetGeneral->getStyleByColumnAndRow(4, $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            $sheetGeneral->setCellValueByColumnAndRow(5, $row, $g['unpaidTerms']);
            $sheetGeneral->setCellValueByColumnAndRow(6, $row, $g['dateLastPayment']);
            $sheetGeneral->setCellValueByColumnAndRow(7, $row, $g['manager']);
            $sheetGeneral->setCellValueByColumnAndRow(8, $row, $g['lastManagementDate']);
            $sheetGeneral->setCellValueByColumnAndRow(9, $row, $g['lastComment']);
            $row++;
        }

        // Autosize columnas general
        foreach (range('A','I') as $colL) {
            $sheetGeneral->getColumnDimension($colL)->setAutoSize(true);
        }

        // Guardar GENERAL
        $writerGeneral = new Xlsx($spreadsheetGeneral);
        $writerGeneral->save($xlsxPathGeneral);
        $archivos_generados[] = $xlsxPathGeneral;
        $spreadsheetGeneral->disconnectWorksheets();
        unset($spreadsheetGeneral);

        // ---------- Contenido y envío ----------
        $subject = "Llistat de clients morosos " . $lastYear . " / " . $today . " sense comercial assignat.";
        $body = "<span>Hola! </span></br>";
        $body .= "<span> En aquest email hi ha adjuntat el llistat de clients morosos que no tenen un comercial assignat.</span></br>";

        foreach ($emailList as $email) {
            $mail->AddAddress($email);
        }

        $mail->Subject = $subject;
        $mail->MsgHTML($body);

        foreach ($archivos_generados as $archivo) {
            $mail->addAttachment($archivo); // .xlsx adjuntos
        }

        if (!$mail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            echo 'Message has been sent';
        }

        // Limpieza de archivos temporales
        foreach ($archivos_generados as $archivo) {
            @unlink($archivo);
        }
    }
}

function sendReportMorosos_csv(){

		$emailList = array(
	"carles@porta.ad",
	"comptabilitat@traldisporta.com",
	"admin1@porta.ad",
	"admin@traldisporta.com",
	"jordi.olle@porta.ad",
	"victor@openmindsystems.com.es"
	);
	$emailList = array(
		"victor.sancho.coma@gmail.com"
	);

	$emailDefault = 'ruben@porta.ad';
	$emailDefault = 'victor.sancho.coma@gmail.com';


	$listBySalesmanEmpty = array();
	$listBySalesman = array();

	$today = date("Y-m-d");
	$lastYear = date("Y-m-d", strtotime($today . "- 15 day"));

	$path = 'http://localhost:8080/oms/morosos/';


	//Cridem a la funcio que ens recupera totes les factures pendents de pagament
	$list = $this->getFacturasPendientes($lastYear, $today, false, false);
	$list_comercial = $list[1];

	//Recorrem els resultats per tal guardar-los en 2 dicts, segons si tenen comercial assignat o no.
	foreach ($list_comercial as $cliCod => $line) {
		
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


		

$archivos_generados=array();
$csvPathGeneral   = __DIR__ . '/pdf/morosos_general_'.$lastYear . "_" . $today.'.csv';
	$headers = ['Empresa','Codi','Nom','Import','Venciments Impagats','Primer venciment impagat','Gestor','Data última gestió','Última gestió'];
	$fhgeneral = fopen($csvPathGeneral, 'w');
	fputs($fhgeneral, "\xEF\xBB\xBF"); // BOM UTF-8 para tildes en Excel
	fputcsv($fhgeneral, $headers, ';');

foreach ($list as $codCompany => $company){
	if($codCompany==1) $nomCompany="Traldisporta_Express";
	else $nomCompany="Traldisporta_Distribucions";
	$csvPath   = __DIR__ . '/pdf/morosos_'.$nomCompany.'_'.$lastYear . "_" . $today.'.csv';
	$headers = ['Codi','Nom','Import','Venciments Impagats','Primer venciment impagat','Gestor','Data última gestió','Última gestió'];
	$fh = fopen($csvPath, 'w');
	fputs($fh, "\xEF\xBB\xBF"); // BOM UTF-8 para tildes en Excel
	fputcsv($fh, $headers, ';');
	foreach ($company as $cliCod => $line) {
					$customerCod = $line['customerCode'];
					$customerName = $line['customerName'];
					$amountUnpaid = $line['amountUnpaid'];

					$unpaidTerms = $line['RgVtoNum'];
					$manager = '';
					$lastManagementDate = '';
					$lastComment = '';

					$hasIncidence = $this->hasIncidence($customerCod, $lastYear, $today);
					if ($hasIncidence != false) {
						$lastManagementDate = formatMorosoGinRegDat($hasIncidence['GinRegDat'] ?? null);
						$manager = utf8_encode($hasIncidence['GinAsiUse'] ?? '');
						$lastComment = $this->sanitizeComment(utf8_encode($hasIncidence['CinDes1'] ?? ''));
					}
					$dateLastPayment="";	
					if ($line["lastPaymentDate"] != '') {
						$dateLastPayment = $line["lastPaymentDate"];
					}
					$manager=$line['userResp'];

		fputcsv($fh, [
			$customerCod ?? '',
			$customerName ?? '',
			number_format((float)str_replace([',','.',' '], ['','.',''], $amountUnpaid ?? 0), 2, ',', ''),
			(int)($unpaidTerms ?? 0),
			$dateLastPayment ?? '',
			$manager ?? '',
			$lastManagementDate ?? '',
			$lastComment ?? '',
		], ';');
		
		fputcsv($fhgeneral, [
		    $nomCompany ?? '',
			$customerCod ?? '',
			$customerName ?? '',
			number_format((float)str_replace([',','.',' '], ['','.',''], $amountUnpaid ?? 0), 2, ',', ''),
			(int)($unpaidTerms ?? 0),
			$dateLastPayment ?? '',
			$manager ?? '',
			$lastManagementDate ?? '',
			$lastComment ?? '',
		], ';');

	}
	$archivos_generados[]=$csvPath;
	fclose($fh);
}
$archivos_generados[]=$csvPathGeneral;
fclose($fhgeneral);

		//Preparem el missatge que mostrarem en el mail
		//$link = $path . 'main.php?fechaInit=' . $lastYear . '&fechaEnd=' . $today . '&salesman=' . $salesManName . '&action_page=fsdgr5g';
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
		foreach($archivos_generados as $archivo){
		//Afegim l'arxiu adjunt al mail
		$mail->addAttachment($archivo);
		}

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
		foreach($archivos_generados as $archivo){
		//Afegim l'arxiu adjunt al mail
		unlink(archivo);
		}
	}

		
	
}
	
/**
 * Limpia comentarios: quita saltos de línea, tabs, control chars y cambia ';' por coma.
 */
function sanitizeComment($v): string {
    $v = (string)$v;

    // Normaliza saltos de línea a espacio (CRLF, CR, LF)
    $v = preg_replace("/\r\n|\r|\n/u", " ", $v);

    // Cambia delimitadores problemáticos
    $v = str_replace([';', "\t"], [',', ' '], $v);

    // Elimina caracteres de control ASCII (excepto espacio)
    $v = preg_replace('/[[:cntrl:]]/u', ' ', $v);

    // Colapsa espacios múltiples y recorta
    $v = preg_replace('/\s{2,}/u', ' ', $v);
    return trim($v);
}

}

?>