<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Nov-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/


date_default_timezone_set("Europe/Madrid");

class Account
{
    private $conn;

    function __construct()
    {
        require_once '../include/DbConnectExternal.php';
        // opening db connection
        $db = new DbConnectExternal();
        $this->conn = $db->connect();
    }
	
	
		public function getAllSaleAccountInfo($dateInit,$dateEnd)
    {
    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);

   

    $dateFilter = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = " AND RegDatEmi BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    } else {
        if ($dateInit == false && $dateEnd != false) {
            $dateFilter = " AND RegDatEmi < '" . $dateEnd . "' ";
        } else {
            if ($dateInit != false && $dateEnd == false) {
                $dateFilter = " AND RegDatEmi > '" . $dateInit . "' ";
            }

        }
    }
        $response = array();
		
		$sql0 = "
        SELECT 
     EmpCod,
	 CliCod,
	 RegCliNom,
	 RegCliDir,
	 RegCliPos,
	 RegCliPob,	 
	 RegCliNif,
	 RegAsiCod,
	 RegTip,
	 RegCtrCod,
	 RegSer,
	 RegNum,
	 RegDatEmi,
	 RegRenNum,
	 RegIva1I,
	 RegIva2I

  FROM [trans].[dbo].[REGISTRO]
  where HolCod = 0 AND EmpCod in (1,2) AND  RegTip='V' ".$dateFilter." and CONVERT(date, RegRegDat) > '2023-12-31 00:00:00' Order by RegCtrCod,RegSer,RegNum,RegRegDat  ASC
	";
	
		$sql0="SELECT 
     rg.EmpCod,
     rg.CliCod,
     rg.RegCliNom,
     rg.RegCliDir,
     rg.RegCliPos,
     rg.RegCliPob,     
     rg.RegCliNif,
     rg.RegAsiCod,
     rg.RegTip,
     rg.RegCtrCod,
     rg.RegSer,
     rg.RegNum,
     rg.RegDatReg as RegDatEmi,
     rg.RegRenNum,
     rg.RegIva1I,
     rg.RegIva2I,
     SUM(r1.RegImpDiv) as Total,
	 p1.PaiNem,
	 ag.GrpAgeCod

FROM [trans].[dbo].[REGISTRO] as rg
LEFT JOIN [trans].[dbo].[REGISTR1] as r1
    ON rg.RegSer = r1.RegSer 
    AND rg.RegCtrCod = r1.RegCtrCod 
    AND rg.RegNum = r1.RegNum 
    AND rg.RegTip = r1.RegTip
	AND rg.HolCod = r1.HolCod
LEFT JOIN  [trans].[dbo].[POSTAL] as p1
    ON rg.RegCliPai	= p1.PaiCod
LEFT JOIN [trans].[dbo].AGENDA1 AS ag
	ON ag.[CliCod] = rg.CliCod


WHERE 
    rg.HolCod = 0 
    AND rg.EmpCod IN (1,2) 
    AND rg.RegTip = 'V'  
    ".$dateFilter." 
    AND CONVERT(DATE, rg.RegRegDat) > '2023-12-31' 



GROUP BY 
     rg.EmpCod,
     rg.CliCod,
     rg.RegCliNom,
     rg.RegCliDir,
     rg.RegCliPos,
     rg.RegCliPob,     
     rg.RegCliNif,
     rg.RegAsiCod,
     rg.RegTip,
     rg.RegCtrCod,
     rg.RegSer,
     rg.RegNum,
     rg.RegDatEmi,
     rg.RegRenNum,
     rg.RegIva1I,
     rg.RegIva2I,
	 rg.RegRegDat,
	 rg.RegDatReg,
	 p1.PaiNem,
	 ag.GrpAgeCod

ORDER BY 
     rg.RegCtrCod,
     rg.RegSer,
     rg.RegNum,
     rg.RegRegDat ASC;
";

       // print($sql0);

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		
        return $rows0;

    }
	public function getAllMovements($dateInit,$dateEnd)
    {
    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);
	
	$dateFilter = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = "  mv.MvtApuDat BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    }
	
	
	$sql0="SELECT mv.MvtCod,mv.EmpCod, mv.MvtApuVal, mv.CtaCod, mv.MvtImpDeb, mv.MvtImpHab, mv.MvtCon, mv.CtaCodVir
	FROM [trans].[dbo].[MOVIMIE3] AS mv
	WHERE ".$dateFilter."
	  AND NOT EXISTS (
      SELECT rg.RegAsiCod
      FROM [trans].[dbo].[REGISTRO] AS rg
      WHERE rg.RegAsiCod = mv.MvtCod );";  
	  //echo $sql0; 
          $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		
        return $rows0;      
  
	
	
	}
	
			public function getAllBuyAccountInfo($dateInit,$dateEnd)
    {
    $dateInit = parseDate($dateInit);
    $dateEnd = parseDate($dateEnd);

   

    $dateFilter = '';
    if ($dateInit != false && $dateEnd != false) {
        $dateFilter = " AND RegDatReg BETWEEN '" . $dateInit . "' AND '" . $dateEnd . "' ";
    } else {
        if ($dateInit == false && $dateEnd != false) {
            $dateFilter = " AND RegDatReg < '" . $dateEnd . "' ";
        } else {
            if ($dateInit != false && $dateEnd == false) {
                $dateFilter = " AND RegDatReg > '" . $dateInit . "' ";
            }

        }
    }
        $response = array();
		$sql0="SELECT 
    rg.EmpCod,
    rg.CliCod,
    rg.RegCliNom,
    rg.RegCliDir,
    rg.RegCliPos,
    rg.RegCliPob,     
    rg.RegCliNif,
    rg.RegAsiCod,
    rg.RegTip,
    rg.RegCtrCod,
    rg.RegSer,
    rg.RegNum,
    rg.RegDatReg AS RegDatEmi,
    rg.RegRenNum,
    rg.RegIva1I,
    rg.RegIva2I,
    SUM(r1.RegImpDiv) AS Total,
    p1.PaiNem,
    rg.RegBasUe AS ISP,
    rg.RegIvaUe AS IVAUE,


    -- Nueva columna calculada
    CASE 
        WHEN rg.RegBasUe != 0 THEN CAST((rg.RegIvaUe / SUM(r1.RegImpDiv)) * 100 AS INT)
        ELSE NULL
    END AS IVAUECOD,
	ag.GrpAgeCod,
    rg.RegC01Fil AS bien_inversion

FROM 
    [trans].[dbo].[REGISTRO] AS rg
LEFT JOIN 
    [trans].[dbo].[REGISTR1] AS r1 
    ON rg.RegSer = r1.RegSer 
    AND rg.RegCtrCod = r1.RegCtrCod 
    AND rg.RegNum = r1.RegNum 
    AND rg.RegTip = r1.RegTip
    AND rg.HolCod = r1.HolCod
LEFT JOIN  
    [trans].[dbo].[POSTAL] AS p1
    ON rg.RegCliPai = p1.PaiCod
LEFT JOIN
    [trans].[dbo].AGENDA1 AS ag
	ON ag.[CliCod] = rg.CliCod

WHERE 
    rg.HolCod = 0 
    AND rg.EmpCod IN (1, 2)
    AND rg.RegTip = 'C'  
    ".$dateFilter." 
 AND CONVERT(DATE, rg.RegDatReg) > '2023-12-31'


GROUP BY 
    rg.EmpCod,
    rg.CliCod,
    rg.RegCliNom,
    rg.RegCliDir,
    rg.RegCliPos,
    rg.RegCliPob,     
    rg.RegCliNif,
    rg.RegAsiCod,
    rg.RegTip,
    rg.RegCtrCod,
    rg.RegSer,
    rg.RegNum,
    rg.RegDatEmi,
    rg.RegRenNum,
    rg.RegIva1I,
    rg.RegIva2I,
    rg.RegRegDat,
    rg.RegDatReg,
    p1.PaiNem,
    rg.RegBasUe,
    rg.RegIvaUe,
	GrpAgeCod,
	rg.RegC01Fil

ORDER BY 
    rg.RegCtrCod,
    rg.RegSer,
    rg.RegNum,
    rg.RegRegDat ASC;
";

    //    print($sql0);

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		
        return $rows0;

    }
	//Ens converteix l'string d'una data al string d'un datetime
function parseDateAccount($dateOrigin)
{
    if ($dateOrigin != false) {
        return $dateOrigin . "T00:00:00.000";
    } else {
        return $dateOrigin;
    }
}
    public function getDetailInvoice($ImpFraNum, $ImpFraCtr, $ImpFraSer  )
    {
	
        $response = array();
		
		$sql0 = "SELECT 
ImpFraCtr,
ImporTip,
ImpDes,
SUM(CASE 
            WHEN ImpReal > 0 AND ImpReal<=ImpNeto THEN ImpReal 
            ELSE ImpNeto 
        END) AS ImpNeto,
    CASE 
        WHEN c.ConCepBas = 'N' THEN 'S'
        ELSE 'N'
    END AS ImpBas,
BasCod as TIva,
c.ConCepDes as concepto
  FROM [trans].[dbo].[IMPORTES]
  LEFT JOIN CONCEPTO as c on c.ConCepCod=IMPORTES.ConCepCod
  where ImporTip='V' AND ImpFraNum=".$ImpFraNum." and ImpFraCtr=".$ImpFraCtr." and ImpFraSer=".$ImpFraSer."  GROUP by BasCod, ImpDes, ImporTip,ImpFraCtr, c.ConCepDes, c.ConCepBas";

        //print($sql0);

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		if (empty($rows0)) {
		$sql0 = "SELECT 
reg1.RegCtrCod as ImpCtrCod,
reg1.RegTip as ImporTip,
con.ConCepDes as ImpDes,
SUM(reg1.RegImpDiv) as ImpNeto,
    CASE 
        WHEN reg1.RegCtaPgc = 5610000000 THEN 'S'
        ELSE 'N'
    END AS ImpBas,
	reg1.RegCodBas as TIva,
	con.ConCepDes as concepto
  FROM [trans].[dbo].[REGISTR1] as reg1 join [trans].[dbo].CONCEPTO as con on reg1.ConCepCod=con.ConCepCod
  Where reg1.RegTip='V' and reg1.RegNum=".$ImpFraNum." and reg1.RegCtrCod=".$ImpFraCtr." and reg1.RegSer=".$ImpFraSer." group by reg1.RegCtrCod,reg1.RegTip, con.ConCepDes,reg1.RegImpDiv,reg1.RegCtaPgc,reg1.RegCodBas";
       // print($sql0);
		$stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  

		
		
		}

		return $rows0;
	}
	
	    public function getDetailInvoiceBuy($ImpFraNum, $ImpFraCtr, $ImpFraSer  )
    {
		
        $response = array();
		
		$sql0 = "SELECT 
ImpCtrCod,
ImporTip,
ImpDes,
SUM(CASE 
            WHEN ImpReal != 0 THEN ImpReal 
            ELSE ImpNeto 
        END) AS ImpNeto,
    CASE 
        WHEN c.ConCepBas = 'N' THEN 'S'
        ELSE 'N'
    END AS ImpBas,
BasCod as TIva,
c.ConCepDes as concepto
  FROM [trans].[dbo].[IMPORTES]
  LEFT JOIN CONCEPTO as c on c.ConCepCod=IMPORTES.ConCepCod
  where ImporTip='C' AND ImpFraNum=".$ImpFraNum." and ImpFraCtr=".$ImpFraCtr." and ImpFraSer=".$ImpFraSer." GROUP by BasCod, ImpDes, ImporTip,ImpCtrCod, ImpPor1,c.ConCepDes, c.ConCepBas";

        

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		if(empty($rows0)){				
			$sql0 = "SELECT 
reg1.RegCtrCod as ImpCtrCod,
reg1.RegTip as ImporTip,
con.ConCepDes as ImpDes,
SUM(reg1.RegImpDiv) as ImpNeto,
    CASE 
        WHEN con.ConCepBas = 'N' THEN 'S'
        ELSE 'N'
    END AS ImpBas,
	reg1.RegCodBas as TIva,
	con.ConCepDes as concepto
  FROM [trans].[dbo].[REGISTR1] as reg1 join [trans].[dbo].CONCEPTO as con on reg1.ConCepCod=con.ConCepCod
  Where reg1.RegTip='C' and reg1.RegNum=".$ImpFraNum." and reg1.RegCtrCod=".$ImpFraCtr." and reg1.RegSer=".$ImpFraSer." group by reg1.RegCtrCod,reg1.RegTip, con.ConCepDes,reg1.RegImpDiv,reg1.RegCtaPgc,reg1.RegCodBas,con.ConCepBas";
 			$stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
			$rows0 = $stm0->fetchAll();  
		}
		//print($sql0);
		return $rows0;
	}


public function getMonthlyMovements($month, $year)
{
    // Fuerza tipos enteros por seguridad
    $month = (int)$month;
    $year  = (int)$year;

    $sql = "
SELECT 
    LTRIM(STR(M3.MvtCod)) + '/' + LTRIM(STR(M3.MvtApu))+'_'+LTRIM(STR(MONTH(M2.MvtDat)))+'_'+LTRIM(STR(YEAR(M2.MvtDat))) AS id,
	E.EmpCom AS company,
    M2.MvtDat AS date,
    M3.CtaCod AS account,
    LTRIM(STR(M3.MvtCod)) + '/' + LTRIM(STR(M3.MvtApu)) AS movement,
    M3.MvtImp * (CASE WHEN MvtTip = 0 THEN 1 ELSE -1 END) AS amount,
    MONTH(M2.MvtDat) AS period,
    ISNULL(C.CtrDes, 'Desconocido') AS ceco,
    CASE
        WHEN MvtTip = 0 THEN 'D'
        ELSE 'H'
    END AS type,
    CASE M2.MvtOri
        WHEN 1 THEN 'Manual'
        WHEN 2 THEN 'Diario'
        WHEN 3 THEN 'Compras'
        WHEN 4 THEN 'Ventas'
        WHEN 5 THEN 'Cartera'
        WHEN 6 THEN 'Automático'
        WHEN 7 THEN 'Cierre'
        ELSE 'Desconocido'
    END AS movement_type,
    CASE
        WHEN LEFT(CtaCod, 1) IN ('6', '7') THEN RIGHT(CtaCod, 2)
        ELSE ''
    END AS last_two_digits,
    CASE
        WHEN LEFT(CtaCod, 1) IN ('6', '7') THEN
            CASE RIGHT(CtaCod, 2)
                WHEN '00' THEN 'Central'
                WHEN '01' THEN 'FTL'
                WHEN '02' THEN 'Manipulacio'
                WHEN '03' THEN 'Emmagatzematge'
                WHEN '04' THEN 'Duanes'
                WHEN '10' THEN 'Grupatge'
                ELSE ''
            END
        ELSE ''
    END AS section,
YEAR(M2.MvtDat) AS year
FROM
    dbo.MOVIMIE2 AS M2
    INNER JOIN dbo.MOVIMIE3 AS M3
        ON M2.HolCod = M3.HolCod
        AND M2.EmpCod = M3.EmpCod
        AND M2.MvtCla = M3.MvtCla
        AND M2.MvtCod = M3.MvtCod
    LEFT JOIN dbo.CENTRO AS C
        ON M3.HolCod = C.HolCod
        AND M3.EmpCod = C.EmpCod
        AND M3.CtrCod = C.CtrCod
	LEFT JOIN dbo.EMPRESA as E
	    ON E.HolCod=M2.HolCod and E.EmpCod=M3.EmpCod
WHERE
    M2.MvtOri!=7 AND
    M2.HolCod = 0
    AND M2.MvtCla = ' '
    AND M2.MvtSit > 1
AND LEFT(CtaCod, 1) IN ('6', '7')
            AND MONTH(m2.MvtDat) = :month
            AND YEAR(m2.MvtDat)  = :year
        ORDER BY m2.MvtDat, m3.MvtCod, m3.MvtApu
    ";
     
    try {
        $stm = $this->conn->prepare($sql);
        $stm->bindValue(':month', $month, PDO::PARAM_INT);
        $stm->bindValue(':year',  $year,  PDO::PARAM_INT);
        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (PDOException $e) {
        // $this->logger->error('getMonthlyMovements error: '.$e->getMessage());
        return [];
    }
}
public function getYearMovements( $year)
{
    // Fuerza tipos enteros por seguridad
    //$month = (int)$month;
    $year  = (int)$year;

    $sql = "
SELECT 
    LTRIM(STR(M3.MvtCod)) + '/' + LTRIM(STR(M3.MvtApu))+'_'+LTRIM(STR(MONTH(M2.MvtDat)))+'_'+LTRIM(STR(YEAR(M2.MvtDat))) AS id,
	E.EmpCom AS company,
    M2.MvtDat AS date,
    M3.CtaCod AS account,
    LTRIM(STR(M3.MvtCod)) + '/' + LTRIM(STR(M3.MvtApu)) AS movement,
    M3.MvtImp * (CASE WHEN MvtTip = 0 THEN 1 ELSE -1 END) AS amount,
    MONTH(M2.MvtDat) AS period,
    ISNULL(C.CtrDes, 'Desconocido') AS ceco,
    CASE
        WHEN MvtTip = 0 THEN 'D'
        ELSE 'H'
    END AS type,
    CASE M2.MvtOri
        WHEN 1 THEN 'Manual'
        WHEN 2 THEN 'Diario'
        WHEN 3 THEN 'Compras'
        WHEN 4 THEN 'Ventas'
        WHEN 5 THEN 'Cartera'
        WHEN 6 THEN 'Automático'
        WHEN 7 THEN 'Cierre'
        ELSE 'Desconocido'
    END AS movement_type,
    CASE
        WHEN LEFT(CtaCod, 1) IN ('6', '7') THEN RIGHT(CtaCod, 2)
        ELSE ''
    END AS last_two_digits,
    CASE
        WHEN LEFT(CtaCod, 1) IN ('6', '7') THEN
            CASE RIGHT(CtaCod, 2)
                WHEN '00' THEN 'Central'
                WHEN '01' THEN 'FTL'
                WHEN '02' THEN 'Manipulacio'
                WHEN '03' THEN 'Emmagatzematge'
                WHEN '04' THEN 'Duanes'
                WHEN '10' THEN 'Grupatge'
                ELSE ''
            END
        ELSE ''
    END AS section,
YEAR(M2.MvtDat) AS year
FROM
    dbo.MOVIMIE2 AS M2
    INNER JOIN dbo.MOVIMIE3 AS M3
        ON M2.HolCod = M3.HolCod
        AND M2.EmpCod = M3.EmpCod
        AND M2.MvtCla = M3.MvtCla
        AND M2.MvtCod = M3.MvtCod
    LEFT JOIN dbo.CENTRO AS C
        ON M3.HolCod = C.HolCod
        AND M3.EmpCod = C.EmpCod
        AND M3.CtrCod = C.CtrCod
	LEFT JOIN dbo.EMPRESA as E
	    ON E.HolCod=M2.HolCod and E.EmpCod=M3.EmpCod
WHERE
    M2.MvtOri!=7 AND
    M2.HolCod = 0
    AND M2.MvtCla = ' '
    AND M2.MvtSit > 1
AND LEFT(CtaCod, 1) IN ('6', '7')
            AND YEAR(m2.MvtDat)  = :year
        ORDER BY m2.MvtDat, m3.MvtCod, m3.MvtApu
    ";
     
    try {
        $stm = $this->conn->prepare($sql);
        //$stm->bindValue(':month', $month, PDO::PARAM_INT);
        $stm->bindValue(':year',  $year,  PDO::PARAM_INT);
        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (PDOException $e) {
        // $this->logger->error('getMonthlyMovements error: '.$e->getMessage());
        return [];
    }
}
}

?>