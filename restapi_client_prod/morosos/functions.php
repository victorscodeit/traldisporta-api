<?php

require_once("db_connection.php");

//I hem rebut una trucada a traves de javascript demanant informacio
/*if (isset($_REQUEST['action'])) {

    switch ($_REQUEST['action']) {
        case 'all_incidences':
            getIncidencias($_REQUEST['customerCode'], $_REQUEST['dateInit'], $_REQUEST['dateEnd']);
            break;
        case 'all_gestions':
            getGestionList($_REQUEST['incCode']);
            break;            
        case 'detail_incidence':
            getGestionInfo($_REQUEST['incCode'], $_REQUEST['gestioNum']);
            break;
    }
}*/

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
    AND r.HolCod = 0 AND r.EmpCod in (1,2) AND r.RegCtrCod in (8,25,80) AND r.RegTip = 'V' 

    ";

    $invoices = sqlsrv_query($conn, $select_invoices);

    $invoicesList = [];
    while ($fila = sqlsrv_fetch_array($invoices, SQLSRV_FETCH_ASSOC)) {
        $dPay = '';
        if ($fila['RgVtoVal'] != null) {
            $dPay = $fila['RgVtoVal']->format("d/m/Y");
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
                    if ($dPayAux < $dPay) {
                        $invoicesList[$companyCode][$customerCode]["lastPaymentDate"] = $dPay;
                    }
                }
            }
        }
    }

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


    $select_incidencias = "SELECT c1.GinAsiUse, c1.GinRegDat, c1.CinCod, c.CinDes1 
    FROM CINCIDEN c 
    INNER JOIN CINCIDE1 c1 ON c.CinCod = c1.CinCod 
    WHERE AnoCod = 410 AND c.HolCod = 0 AND c.EmpCod in (1,2) AND c.CtrCod in (8,25,80) 
    AND GinCliCod = '" . $customerCode . "' " . $dateFilter." 
    ORDER BY c.CinRegDat desc";


    $incidents = sqlsrv_query($conn, $select_incidencias);

    if ($incidents) {
        $rows = sqlsrv_has_rows($incidents);
        if ($rows === true){
            while ($fila = sqlsrv_fetch_array($incidents, SQLSRV_FETCH_ASSOC)) {
                #com que fem un return, nomes s'executara 1 vegada, donant-nos la primera fila del select
                #que representa que es el resultat mes recent.
                return $fila;
            }
        }
        else{
            return false;
        }
    }
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

?>