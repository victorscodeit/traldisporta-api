<?php

require_once("db_connection.php");


//Ens converteix l'string d'una data al string d'un datetime
function parseDate($dateOrigin)
{
    if ($dateOrigin != false) {
        return $dateOrigin . "T00:00:00.000";
    } else {
        return $dateOrigin;
    }
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

function getInvoices($dateInit = false, $dateEnd = false, $invoiceNum = false, $customerId = false, $customerName = false, $center = false, $serie = false,
    $documentType ){

    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);

    $conn = connectionDb();

    $dateFilter = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = " AND r.RegDatEmi BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    } else {
        if ($dateInit == false && $dateEnd != false) {
            $dateFilter = " AND r.RegDatEmi < '" . $dateEnd . "' ";
        } else {
            if ($dateInit != false && $dateEnd == false) {
                $dateFilter = " AND r.RegDatEmi > '" . $dateInit . "' ";
            }

        }
    }

    $num = '';
    if ($invoiceNum != false){
        if ($invoiceNum != ''){
            $num = " AND r.regNum = '".$invoiceNum."' ";
        }
    }

    $c_id = '';
    if ($customerId != false && $customerId != ''){
        $c_id = ' AND r.CliCod = '.$customerId.' ';
    }

    $c_name = '';
    if ($customerName != false && $customerName != ''){
        $c_name = " AND r.RegCliNom like '%".$customerName."%' ";
    }    

    $c = '';
    if ($center != false && $center != ''){
        $c = ' AND r.RegCtrCod = '.$center.' ';
    }

    $s = '';
    if ($serie != false && $serie != ''){
        $s = ' AND r.RegSer = '.$serie.' ';
    }  
    
    $docType = '';
    switch ($documentType){
        case "customer":
            $docType = " AND r.RegTip = 'V' ";
            break;
        case "supplier":
            $docType = " AND r.RegTip = 'C' ";
            break;
    }

    #Holding. HoldCod = 0 Sempre -> (Grup Porta)
    #Empreses. EmpCod in (1,2) -> 1 = Traldisporta Express (ESP), 2 = Transports i Distribucions
    #Centres. RegCtrCod in (8,25,80) -> 8 = Barcelona (Empresa 1), 25 = Montferrer (Empresa 1), 80 = Andorra (Empresa 2)
    #Taula registro -> Factures
    #Taula vencimie -> Vencimientos
    #Taula operaci1 -> Pagaments
    $select_invoices = "SELECT 
    r.HolCod, 
    r.EmpCod, 
    r.RegCtrCod, 
    r.RegSer, 
    r.RegNum, 
    r.CliCod, 
    r.RegDatEmi, 
    r.RegEsta, 
    r.RegCliNom, 
    r.RegCliNif, 
    v.RgVtoDat, 
    v.RgVtoImp, 
    v.RgVtoEst, 
    o.RgVtoVal
    FROM registro r 
    INNER JOIN VENCIMIE v ON v.RegNum = r.RegNum AND v.EmpCod = r.EmpCod AND v.RegCtrCod = r.RegCtrCod AND v.RegTip = r.RegTip AND v.RegSer = r.RegSer 
    LEFT JOIN OPERACI1 o on r.regnum = o.RegNum and r.HolCod = o.HolCod and r.empCod = o.empCod and r.RegCtrCod = o.RegCtrCod and r.RegSer = o.RegSer AND o.RegTip = r.RegTip
    WHERE 1=1 ".$num." ".$dateFilter." ".$c." ".$s." ".$c_id." ".$c_name." ".$docType." 
    AND r.HolCod = 0 AND r.EmpCod in (1,2) AND r.RegCtrCod in (8,25,80) 
    ORDER BY r.RegDatEmi ASC";

    $invoices = sqlsrv_query($conn, $select_invoices);
    
    $invoicesList = [];
    while ($fila = sqlsrv_fetch_array($invoices, SQLSRV_FETCH_ASSOC)) {
        array_push($invoicesList, $fila);
    }

    closeDb($conn);

    return $invoicesList;
}

?>