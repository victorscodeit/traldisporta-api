<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Nov-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

require '../class/external/Carga.php';
require '../class/external/Contrato.php';
require '../class/external/Expedicion.php';
//require '../class/external/HojaDistribucion.php';
require '../class/external/Recogida.php';
require '../class/external/Entrega.php';

date_default_timezone_set("Europe/Madrid");

class Movertis
{
    private $conn;
    private $connLocal;

    function __construct()
    {
        require_once '../include/DbConnectExternal.php';
        require_once '../include/DbConnect.php';
        // opening db connection
        $db = new DbConnectExternal();
        $this->conn = $db->connect();

        $dbLocal = new DbConnect();
        $this->connLocal = $dbLocal->connect();
    }
	public function getExpeditions24()
    {
		$response = array();
		$fechaHoy = date("Y-m-d");
		//$fechaHoy = date("2024-07-28");
		$fechaHoy2 = date("2024-10-17");
		$fechaFin = date("2024-10-22");
		
		$sql0 = "
        SELECT 
        e.".Expedicion::CODE." as codigoExpedicion,
        e.".Expedicion::CENTROCODE." as codigoCentro,
        e.".Expedicion::CODIGOEXPEDICIONTERCERO." as codigoExpedicionTercero,
		e.".Expedicion::CODIGOORDENANTE." as codigoOrdenante
        FROM ".Expedicion::TABLE." e  ".
       "WHERE  CONVERT(date, e.ExpDatLle) = '".$fechaHoy."' and MerCod in (1,2,8) and e.".Expedicion::CENTROCODE." in (8,25)  and e.ExpCod>39000";
		//"WHERE  CONVERT(date, e.ExpDatLle) > '".$fechaHoy2."' and CONVERT(date, e.ExpDatLle) < '".$fechaFin."' and MerCod in (1,2,8) and e.".Expedicion::CENTROCODE." in (8,25)  and e.ExpCod>39000";
		//"WHERE e.".Expedicion::CODE."=1089068";
        //print($sql0);

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll(); 
		
        return $rows0;
		
	}
	public function getExpeditionFromOrder($expeditionOrder)
    {
        $response = array();
		
		$sql0 = "
        SELECT 
        e.".Expedicion::CODE." as codigoExpedicion,
        e.".Expedicion::CENTROCODE." as codigoCentro,
        e.".Expedicion::CODIGOEXPEDICIONTERCERO." as codigoExpedicionTercero,
		e.".Expedicion::CODIGOORDENANTE." as codigoOrdenante
        FROM ".Expedicion::TABLE." e  
        WHERE e.".Expedicion::CODIGOEXPEDICIONTERCERO." = '".$expeditionOrder."' and MerCod in (8,1,2)";

        //print($sql0);
//die();
        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		
        return $rows0;

    }
    private function createCheckreportHandle($idVehicle, $initial_date, $end_date)
    {
		$payload = json_encode(array(
			"idVehicle" => array((int) $idVehicle),
			"idReport" => array(16),
			"initial_date" => (int) $initial_date,
			"end_date" => (int) $end_date
		));

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://devapi.hellomovertis.com/report/checkreport?Authorization=9a0ab49ab29426b087f8e68638760a13AB1DCFAB8F41E44A2C456FA153B313766AD32E7E&Content-Type=application%2Fjson',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_CONNECTTIMEOUT => 5,
		  CURLOPT_TIMEOUT => 20,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $payload,
		  CURLOPT_HTTPHEADER => array(
			'Authorization: 9a0ab49ab29426b087f8e68638760a13AB1DCFAB8F41E44A2C456FA153B313766AD32E7E',
			'Content-Type: application/json'
		  ),
		  CURLOPT_VERBOSE => false,
		  CURLOPT_FAILONERROR => true,
		  CURLOPT_HEADER => false,
		  CURLOPT_SSL_VERIFYPEER => false,
		  CURLOPT_SSL_VERIFYHOST => false,
		));

		return $curl;
    }

    public function checkreport($idVehicle,$initial_date,$end_date){
		$curl = $this->createCheckreportHandle($idVehicle, $initial_date, $end_date);
		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			curl_close($curl);
			return array();
		}

		curl_close($curl);

		$data = json_decode($response, true);
		return is_array($data) ? $data : array();
	}

	public function checkreportMulti($requests)
	{
		if (!is_array($requests) || count($requests) === 0) {
			return array();
		}

		$multiHandle = curl_multi_init();
		$handles = array();

		foreach ($requests as $request) {
			if (!isset($request['key'], $request['idVehicle'], $request['initial_date'], $request['end_date'])) {
				continue;
			}

			$curl = $this->createCheckreportHandle($request['idVehicle'], $request['initial_date'], $request['end_date']);
			$handleId = (int) $curl;
			$handles[$handleId] = array(
				'handle' => $curl,
				'key' => $request['key']
			);

			curl_multi_add_handle($multiHandle, $curl);
		}

		do {
			$multiExec = curl_multi_exec($multiHandle, $running);

			if ($running && $multiExec === CURLM_OK) {
				$selected = curl_multi_select($multiHandle, 1.0);
				if ($selected === -1) {
					usleep(100000);
				}
			}
		} while ($running && $multiExec === CURLM_OK);

		$responses = array();
		foreach ($handles as $info) {
			$response = curl_multi_getcontent($info['handle']);
			$data = array();

			if (!curl_errno($info['handle']) && $response !== false) {
				$decoded = json_decode($response, true);
				if (is_array($decoded)) {
					$data = $decoded;
				}
			}

			$responses[$info['key']] = $data;

			curl_multi_remove_handle($multiHandle, $info['handle']);
			curl_close($info['handle']);
		}

		curl_multi_close($multiHandle);

		return $responses;
	}
	
	private function ensureShowvehiclesCacheTable()
	{
		if (!$this->connLocal) {
			return;
		}

		$sql = "CREATE TABLE IF NOT EXISTS movertis_showvehicles_cache (
			id TINYINT NOT NULL PRIMARY KEY,
			payload LONGTEXT NOT NULL,
			updated_at DATETIME NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

		$this->connLocal->exec($sql);
	}

	private function getShowvehiclesFromDb($maxAgeMinutes = 30)
	{
		if (!$this->connLocal) {
			return false;
		}

		$this->ensureShowvehiclesCacheTable();

		$sql = "SELECT payload
				FROM movertis_showvehicles_cache
				WHERE id = 1
				  AND updated_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
				LIMIT 1";
		$stmt = $this->connLocal->prepare($sql);
		$stmt->bindValue(':minutes', (int)$maxAgeMinutes, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row || empty($row['payload'])) {
			return false;
		}

		$data = json_decode($row['payload'], true);
		return is_array($data) ? $data : false;
	}

	private function saveShowvehiclesToDb($payload)
	{
		if (!$this->connLocal || !is_array($payload)) {
			return;
		}

		$this->ensureShowvehiclesCacheTable();

		$jsonPayload = json_encode($payload);
		if ($jsonPayload === false) {
			return;
		}

		$sql = "INSERT INTO movertis_showvehicles_cache (id, payload, updated_at)
				VALUES (1, :payload, NOW())
				ON DUPLICATE KEY UPDATE
					payload = VALUES(payload),
					updated_at = VALUES(updated_at)";
		$stmt = $this->connLocal->prepare($sql);
		$stmt->bindValue(':payload', $jsonPayload, PDO::PARAM_STR);
		$stmt->execute();
	}

	private function getShowvehiclesFromDbAnyAge()
	{
		if (!$this->connLocal) {
			return false;
		}

		$this->ensureShowvehiclesCacheTable();
		$sql = "SELECT payload
				FROM movertis_showvehicles_cache
				WHERE id = 1
				LIMIT 1";
		$stmt = $this->connLocal->query($sql);
		$row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
		if (!$row || empty($row['payload'])) {
			return false;
		}

		$data = json_decode($row['payload'], true);
		return is_array($data) ? $data : false;
	}

	private function fetchShowvehiclesRemote()
	{
		$cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'movertis_showvehicles_cache.json';

		// Intentamos servir desde cache para evitar una llamada externa en cada request.
		if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
			$cachedContent = @file_get_contents($cacheFile);
			if ($cachedContent !== false) {
				$cachedData = json_decode($cachedContent, true);
				if (is_array($cachedData)) {
					return $cachedData;
				}
			}
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://devapi.hellomovertis.com/vehicle/showvehicles',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_CONNECTTIMEOUT => 5,
		  CURLOPT_TIMEOUT => 20,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
			"id": [],
			"flags": {
				"basicData": true,
				"sensors": true
			}
		  }',
		  CURLOPT_HTTPHEADER => array(
			'Authorization: 9a0ab49ab29426b087f8e68638760a13AB1DCFAB8F41E44A2C456FA153B313766AD32E7E',
			'Content-Type: application/json'
		  ),
		  CURLOPT_VERBOSE => false,
		  CURLOPT_FAILONERROR => true,
		  CURLOPT_HEADER => false,
		  CURLOPT_SSL_VERIFYPEER => false,
		  CURLOPT_SSL_VERIFYHOST => false,
		));

		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			echo 'Error:' . curl_error($curl);
		} else {
			/*$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			echo "HTTP Status Code: " . $http_code . "\n";
			echo "Response: " . $response;*/
		}

		curl_close($curl);
		$data = json_decode($response, true);

		if (is_array($data)) {
			@file_put_contents($cacheFile, json_encode($data));
			return $data;
		}

		return false;
	}

	public function showvehicles($forceRefresh = false){
		try {
			if (!$forceRefresh) {
				$data = $this->getShowvehiclesFromDb(30);
				if ($data !== false) {
					return $data;
				}
			}

			$data = $this->fetchShowvehiclesRemote();
			if ($data !== false) {
				$this->saveShowvehiclesToDb($data);
				return $data;
			}

			// Fallback: devolver último valor de BD aunque sea antiguo.
			$staleData = $this->getShowvehiclesFromDbAnyAge();
			if ($staleData !== false) {
				return $staleData;
			}
		} catch (Exception $e) {
			$staleData = $this->getShowvehiclesFromDbAnyAge();
			if ($staleData !== false) {
				return $staleData;
			}
		}

		return array();
	}
    public function getTrucksExpedition($expeditionCode, $centerCode)
    {
        $response = array();
		
		//SALES AMB EL SEU ID y NOM: EL primer numero es el tipus de mercaderia, seguit del numero de centre.
		$salas=array();
		// Sales FARMA
		$salas[8][80]["name"]= "AND SALA FARMA";
		$salas[8][80]["idVehicle"]=27938978;
		$salas[8][8]["name"]= "BCN SALA FARMA";
		$salas[8][8]["idVehicle"]=27938977;
		$salas[8][25]["name"]= "CAU SALA FARMA";
		$salas[8][25]["idVehicle"]=27938974;
		// Sales FRED
		$salas[1][80]["name"]= "AND SALA FRED";
		$salas[1][80]["idVehicle"]=27938973;
		$salas[1][8]["name"]= "BCN SALA FRED";
		$salas[1][8]["idVehicle"]=27938979;
		$salas[1][25]["name"]= "CAU SALA FRED";
		$salas[1][25]["idVehicle"]=27938972;
		// Sales CONGELAT
		$salas[2][80]["name"]= "AND SALA CONGELAT";
		$salas[2][80]["idVehicle"]=27938975;
		


        $sql0 = "
        SELECT 
        r.".Recogida::CODE." as codigoRecogida,
        r.".Recogida::CENTROCODIGO." as codigoCentro,
        r.".Recogida::EMPRESARECODIGO." codigoEmpresaRecogida,
        RTRIM(r.".Recogida::EMPRESA.") as empresaRecogida,
        RTRIM(r.".Recogida::DIRECCION.") as direccionRecogida,
        r.".Recogida::CODIGOPAIS." as codigoPaisRecogida,
        r.".Recogida::CODIGOPAISISO." as codigoPaisISORecogida,
        r.".Recogida::CODIGOPOSTAL." as codigoPostalRecogida,
        RTRIM(r.".Recogida::POBLACION.") as poblacionRecogida,
        r.".Recogida::TRANSPOSTISTACODIGO." as codigoTransportista,
        RTRIM(r.".Recogida::TRANSPORTISTANOMBRE.") as nombreTransportista,
        RTRIM(r.".Recogida::VEHICULOMATRICULA.") as matriculaVehiculo,
        RTRIM(r.".Recogida::VEHICULODESCRPCION.") as descripcionVehiculo,
        r.".Recogida::FECHASALIDA." as fechaSalida,
        r.".Recogida::HORASALIDA." as horaSalida,
        r.".Recogida::FECHALLEGADA." fechaLlegada,
        r.".Recogida::HORALLEGADA." as horaLlegada
        FROM ".Recogida::TABLE." r
        INNER JOIN ".Expedicion::TABLE." e ON r.".Recogida::CODE." = e.".Expedicion::RECOGIDACODE."
        WHERE e.".Expedicion::CODE." = ".$expeditionCode." AND e.".Expedicion::CENTROCODE." = ".$centerCode." AND r.".Recogida::CENTROCODIGO." = ".$centerCode;

 //       print($sql0);

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();  
		//Agafem codi de centre de destí de recollida
		$centroRecogida=8;

		$response["recogidas"] = array();
        foreach ($rows0 as $row) {
			$centroRecogida=$row['codigoCentro'];
            array_push($response["recogidas"], $row);
        }

        //Busquem la informació de l'expedició
        /*$sql1 = "SELECT
        e." . Expedicion::CODE . " as codigoExpedicion,
		e." . Expedicion::CENTROCODE . " as codigoCentro,
        RTRIM(e." . Expedicion::ORDENANTE . ") as ordenante,
        RTRIM(e." . Expedicion::REMITENTE . ") as remitente,
        RTRIM(e." . Expedicion::DIRECCIONREMITENTE . ") as direccionRemitente,
        e." . Expedicion::PAISREMITENTECODE . " as paisRemitenteCodigo,
        e." . Expedicion::PAISREMITENTEISO . " as paisRemitenteIniciales,
        RTRIM(e." . Expedicion::CPREMITENTE . ") as codigoPostalRemitente,
        RTRIM(e." . Expedicion::POBLACIONREMITENTE . ") as poblacionRemitente,
        RTRIM(e." . Expedicion::DESTINATARIO . ") as destinatario,
        RTRIM(e." . Expedicion::DIRECCIONDESTINATARIO . ") as direccionDestinatario,
        e." . Expedicion::PAISDESTINATARIOCODE . " as paisDestinatarioCodigo,
        e." . Expedicion::PAISDESTINATARIOISO . " as paisDestinatarioIniciales,
        RTRIM(e." . Expedicion::CPDESTINATARIO . ") as codigoPostalDestinatario,
        RTRIM(e." . Expedicion::POBLACIONDESTINACION . ") as poblacionDestinatario
        FROM " . Expedicion::TABLE . " as e
        WHERE e." . Expedicion::CODE . " = " . $expeditionCode." AND e.".Expedicion::CENTROCODE." = ". $centerCode;*/

        //print($sql1);

        /*$stm1 = $this->conn->query($sql1, PDO::FETCH_ASSOC);
        $rows1 = $stm1->fetchAll();

		$response["expedicion"] = array();
        foreach ($rows1 as $row) {
            array_push($response["expedicion"], $row);
        }*/

        //Busquem la informacio del trasllat entre magatzems
        /*$sql2 = "SELECT 
        RTRIM(co.".Contrato::POBLACIONORIGEN.") as poblacionOrigen,
        co.".Contrato::PAISORIGENCODE." as paisOrigenCodigo,
        RTRIM(co.".Contrato::POBLACIONDESTINO.") as poblacionDestino,
        co.".Contrato::PAISDESTINOCODE." as paisDestinoCodigo,
        co.".Contrato::FECHASALIDA." as fechaCarga,
        co.".Contrato::HORASALIDA." as horaCarga,
        co.".Contrato::FECHALLEGADA." as fechaLlegadaCarga,
        co.".Contrato::HORALLEGADA." as horaLlegadaCarga,
        RTRIM(co.".Contrato::MATRICAMION.") as matriculaCamion,
        RTRIM(co.".Contrato::MATRIREMOLQ.") as matriculaRemolque,
        co.".Contrato::CONDUCTORCODE." as conductorCodigo,
        co.".Contrato::TRANSPCODE." as transportistaCodigo,
        RTRIM(co.".Contrato::TRANSPNOMBRE.") as transportistaNombre
        FROM " . Carga::TABLE . " as c1
        INNER JOIN " . CargaDetalle::TABLE . " as c ON c1." . Carga::CODE . " = c." . CargaDetalle::CODE . " 
            AND c1." . Carga::CENTROCODE . " = c." . CargaDetalle::CENTROCODE . "
        INNER JOIN " . Contrato::TABLE . " co ON co.".Contrato::CODE." = c.".CargaDetalle::CONTRATOCODE." 
            AND c.".CargaDetalle::FECHAALTA." = co.".Contrato::FECHAALTA."
        WHERE c1.".Carga::EXPCODE." = " . $expeditionCode. " AND c1.".Carga::CENTROCODE." = ". $centerCode;*/

        $sql2 = "
        SELECT 
            RTRIM(co.".Contrato::POBLACIONORIGEN.") as poblacionOrigen,
            co.".Contrato::PAISORIGENCODE." as paisOrigenCodigo,
            RTRIM(co.".Contrato::POBLACIONDESTINO.") as poblacionDestino,
            co.".Contrato::PAISDESTINOCODE." as paisDestinoCodigo,
            co.".Contrato::FECHASALIDA." as fechaCarga,
            co.".Contrato::HORASALIDA." as horaCarga,
            c.".Carga::FECHALLEGADA." as fechaLlegadaCarga,
            c.".Carga::HORALLEGADA." as horaLlegadaCarga,
            RTRIM(co.".Contrato::MATRICAMION.") as matriculaCamion,
            RTRIM(co.".Contrato::MATRIREMOLQ.") as matriculaRemolque,
            co.".Contrato::CONDUCTORCODE." as conductorCodigo,
            co.".Contrato::TRANSPCODE." as transportistaCodigo,
            RTRIM(co.".Contrato::TRANSPNOMBRE.") as transportistaNombre,
			".Expedicion::CODIGOMERCANCIA." as tipusMercaderia,
			".Expedicion::FECHAHORAREGISTRO." as dataHoraRegistre,
			".Expedicion::CENTROCODEDESTI." as centreDesti,
			c.".CargaDetalle::CENTROCODE." as centreOrigen
			
			
        FROM " . Carga::TABLE . " as c1
        INNER JOIN " . CargaDetalle::TABLE . " as c ON c1." . Carga::CODE . " = c." . CargaDetalle::CODE . " 
        INNER JOIN " . Contrato::TABLE . " co ON co.".Contrato::CODE." = c.".CargaDetalle::CONTRATOCODE." 
        INNER JOIN ".Expedicion::TABLE." e ON c1.".Carga::EXPCODE." = e.".Expedicion::CODE."
        WHERE c1.".Carga::EXPCODE." = " . $expeditionCode." 
            AND c1.".Carga::CENTROCODE." = ".$centerCode." 
            AND e.".Expedicion::CENTROCODE." = ".$centerCode." 
            AND c.".CargaDetalle::FECHAALTA." >= e.".Expedicion::FECHAREGISTRO." 
            AND co.".Contrato::FECHASALIDA." >= e.".Expedicion::FECHAREGISTRO;
        
//		print($sql2);
        $stm2 = $this->conn->query($sql2, PDO::FETCH_ASSOC);
        $rows2 = $stm2->fetchAll();

		$response["almacenes"] = array();
		$response["salas"] = array();
		$rowsala=array();
		$merCod=0;
		$count=0;
		//Per defecte posem el 80 de centre de destí (pel moment) si no es troba el correcte.
		$centroDestino=80;
        foreach ($rows2 as $row) {
			//Estamos en la primera carga, vamos a introducir el valor de la sala done se ha conservado la mercancía en Origen
			$merCod=$row['tipusMercaderia'];
			
			if(($row['tipusMercaderia']==1 OR $row['tipusMercaderia']==2 OR $row['tipusMercaderia']==8 )){				
				$dateClean = explode(" ",$row['fechaCarga']); 
				$hourclean = explode(" ",$row['horaCarga']); 
				$horafi=$dateClean[0]." ".$hourclean[1];
				$centroOR=$row['centreOrigen'];
				//echo "Tipo: ".$row['tipusMercaderia']." CENRTRO : ".(int)$centroRecogida." ".print_r($salas);
				if(isset($salas[(int)$row['tipusMercaderia']][(int)$centroOR]["name"])) {
				$rowsala['inicial']['nomSala']=$salas[(int)$row['tipusMercaderia']][(int)$centroOR]["name"];
				$rowsala['inicial']['idVehicle']=$salas[(int)$row['tipusMercaderia']][(int)$centroOR]["idVehicle"];
				if($count>0){
			    $rowsala['inicial']['horaInici']=$horaDescarrega;
				$rowsala['inicial']['horaFi']= $horafi;			
				}else{
			    $rowsala['inicial']['horaInici']=$row['dataHoraRegistre'];
				$rowsala['inicial']['horaFi']= $horafi;			
				}
				array_push($response["salas"], $rowsala);	
				}							
			}
			//Guardem ultima hora de descarrega
			$dateDescarrega = explode(" ",$row['fechaLlegadaCarga']); 
			$hourDescarrega = explode(" ",$row['horaLlegadaCarga']); 
			$horaDescarrega=$dateDescarrega[0]." ".$hourDescarrega[1];
			//Guardem ultim centre de destí
			$centroDestino=$row['centreDesti'];
            array_push($response["almacenes"], $row);			
			$count++;
        }


        //Busquem informacio de les entregues
        /*$sql3 = "SELECT 
            hi." . HojaDistribucionDetail::HOJDISTCODE . " as codigoHoja,
            hi." . HojaDistribucionDetail::TRANSPCODE . " as transportistaCodigo,
            RTRIM(a." . Agenda::NOMBRE . ") as transportistaNombre,
            RTRIM(hi." . HojaDistribucionDetail::MATRIVEH . ") as matriculaVehiculo,
            RTRIM(hi." . HojaDistribucionDetail::DESCRVEH . ") as descripcionVehiculo,
            hi." . HojaDistribucionDetail::FECHASALIDA . " as fechaSalida,
            hi." . HojaDistribucionDetail::HORASALIDA . " as horaSalida,
            hi." . HojaDistribucionDetail::FECHALLEGADA . " as fechaLlegada,
            hi." . HojaDistribucionDetail::HORALLEGADA . " as horaLlegada ".
			//hi." . HojaDistribucionDetail::TOTALHORAS . " horas,
            //hi." . HojaDistribucionDetail::KMSAL . " as kmsSalida,            
            //hi." . HojaDistribucionDetail::KMLLE . " as kmsLlegada,
			//hi." . HojaDistribucionDetail::TOTALKMS . " kms 
        "FROM " . HojaDistribucion::TABLE . " h
        INNER JOIN " . HojaDistribucionDetail::TABLE . " as hi ON h." . HojaDistribucion::HOJDISTCODE . " = hi." . HojaDistribucionDetail::HOJDISTCODE . " 
        INNER JOIN " . Agenda::TABLE . " as a ON hi." . HojaDistribucionDetail::TRANSPCODE . " = a." . Agenda::ID . " 
        WHERE h." . HojaDistribucion::EXPCODE . " = " . $expeditionCode;*/
        //WHERE h.HdsExpCod = ".$expeditionCode." AND h.CtrCod = ".$centerCode." AND hi.CtrCod = ".$centerCode;
         //. " AND h.".HojaDistribucion::CODIGOCENTRO." = ". $centerCode ." AND h.".HojaDistribucion::CODIGOCENTROHOJDIST." = ". $centerCode;
        $sql3 = "
        SELECT 
        e.".Entrega::TRANSPOSTISTACODIGO." as transportistaCodigo,
        RTRIM(e.".Entrega::TRANSPORTISTANOMBRE.") as transportistaNombre,
        RTRIM(e.".Entrega::MATRICULAVEHICULO.") as matriculaVehiculo,
        RTRIM(e.".Entrega::VEHICULO.") as descripcionVehiculo,
        e.".Entrega::FECHASALIDA." as fechaSalida,
        e.".Entrega::HORASALIDA." as horaSalida,
        e.".Entrega::FECHAENTREGA." as fechaEntrega,
        e.".Entrega::HORAENTREGA." as horaEntrega,
		e.".Entrega::FECHAHORAASIGNABLE." as fechaHoraAsignable
        FROM ".Entrega::TABLE." e 
        WHERE e.".Entrega::EXPCODE." = ".$expeditionCode."  AND e.".Entrega::CENTROCODIGO." = " .$centerCode;

//        print($sql3);

        $stm3 = $this->conn->query($sql3, PDO::FETCH_ASSOC);
        $rows3 = $stm3->fetchAll();

		$response["repartos"] = array();
		$rowsala=array();
		$count=0;
        foreach ($rows3 as $row) {
			//Guardem la sala on s'ha conservat abans de fer la distribució.
			if($count==0 and ($merCod==1 OR $merCod==2 OR $merCod==8) ){
				$dateClean = explode(" ",$row['fechaSalida']); 
				$hourclean = explode(" ",$row['horaSalida']); 
				$horaInici=$dateClean[0]." ".$hourclean[1];
				if(isset($salas[(int)$merCod][(int)$centroDestino])) {
				$rowsala['reparto']['nomSala']=$salas[$merCod][$centroDestino]["name"];
				$rowsala['reparto']['idVehicle']=$salas[$merCod][$centroDestino]["idVehicle"];
				$rowsala['reparto']['horaInici']=$horaDescarrega;
				$rowsala['reparto']['horaFi']=$horaInici;	
				array_push($response["salas"], $rowsala);	
				}				
			}
            array_push($response["repartos"], $row);
			$count++;
        }
		//print_r($response);
        return $response;

    }
public function getExpeditionsData_old($year, $month, $centerCode, $startDate = null, $endDate = null)
{
    $responseFinal = [];

 // ------------------------------
    // 1) Obtener rango de fechas
    // ------------------------------
    // Si se proporcionan fechas específicas, usarlas
    // Si no, usar los primeros 7 días del mes (comportamiento por defecto)
 if ($startDate && $endDate) {
        $start = $startDate;
        $end = $endDate;
    } else {
        // Comportamiento original: primeros 7 días del mes
        $start = "$year-$month-01";
        $end = date("Y-m-d", strtotime("$start +7 days"));
    }

    // Validar que el rango no sea mayor a 7 días
    $startTimestamp = strtotime($start);
    $endTimestamp = strtotime($end);
    $daysDiff = ($endTimestamp - $startTimestamp) / (60 * 60 * 24);
    
    if ($daysDiff > 7) {
        // Limitar a 7 días desde la fecha de inicio
        $end = date("Y-m-d", strtotime("$start +6 days"));
    }

    // ------------------------------
    // 2) Consultar todos los ExpCod
    // ------------------------------
    $sqlExp = "
        SELECT ExpCod
        FROM trans.dbo.EXPEDIC4
        WHERE ISDATE(ExpAltFec) = 1
          AND CONVERT(date, ExpAltFec, 103) >= '$start'
          AND CONVERT(date, ExpAltFec, 103) <= '$end'
          AND ExpDsCtCd =80
          AND ExpCtrCod = $centerCode
          AND ExpSit = 7
        ORDER BY CONVERT(date, ExpAltFec, 103) DESC;
    ";
   
    $stmExp = $this->conn->query($sqlExp, PDO::FETCH_ASSOC);
    $expCodes = $stmExp->fetchAll(PDO::FETCH_COLUMN);

    // Si no hay expediciones
    if (empty($expCodes)) {
        return [];
    }

    // -------------------------------------------------------------
    // 3) Recorrer cada ExpCod y ejecutar TODA TU LÓGICA INTERNA
    // -------------------------------------------------------------
    foreach ($expCodes as $expeditionCode) 
    {
		
		
		// 3A) Obtener datos principales de la expedición
$sqlExpInfo = "
    SELECT
        ".Expedicion::CODE." as ExpCod,
        ".Expedicion::HOLDINGCODE." as HolCod,
        ".Expedicion::CONTRATOCODE." as ExpCntCod,
        ".Expedicion::SECCIONCODE." as SecCod,
        ".Expedicion::CENTROCODE." as ExpCtrCod,
        ".Expedicion::FECHSAL." as ExpAltFec,
        ".Expedicion::FECHALLE." as ExpHorLle,
        ".Expedicion::CIUDADSAL." as ExpOloPob,
        ".Expedicion::CIUDADLLE." as ExpDesPob,
        ".Expedicion::ORDENANTE." as ExpOrdDes,
        ".Expedicion::REMITENTE." as ExpRemDes,
        ".Expedicion::DIRECCIONREMITENTE." as ExpRemDom,
        ".Expedicion::PAISREMITENTECODE." as ExpRemPai,
        ".Expedicion::PAISREMITENTEISO." as ExpRemNem,
        ".Expedicion::CPREMITENTE." as ExpRemPos,
        ".Expedicion::POBLACIONREMITENTE." as ExpRemPob,
        ".Expedicion::DESTINATARIO." as ExpDesDes,
        ".Expedicion::DIRECCIONDESTINATARIO." as ExpDesDom,
        ".Expedicion::PAISDESTINATARIOCODE." as ExpDesPai,
        ".Expedicion::PAISDESTINATARIOISO." as ExpDesNem,
        ".Expedicion::CPDESTINATARIO." as ExpDesPos,
        ".Expedicion::POBLACIONDESTINACION." as ExpDesPob,
        ".Expedicion::RECOGIDACODE." as ExpRecNum,
        ".Expedicion::FECHAREGISTRO." as ExpDatReg,
        ".Expedicion::HORAREGISTRO." as ExpHorReg,
        ".Expedicion::FECHAHORAREGISTRO." as ExpAltFecHora,
        ".Expedicion::CODIGOMERCANCIA." as MerCod,
        ".Expedicion::CENTROCODEDESTI." as ExpDsCtCd,
        ".Expedicion::CODIGOEXPEDICIONTERCERO." as ExpAlbOrd,
        ".Expedicion::CODIGOORDENANTE." as ExpOrdCod,
		ExpSit
    FROM ".Expedicion::TABLE." as ex
	LEFT JOIN CINCIDEN as ci on  ex.ExpCod=ci.CinRef and ci.CtrCod=ex.ExpCtrCod
    WHERE ".Expedicion::CODE." = $expeditionCode
      AND ".Expedicion::CENTROCODE." = $centerCode
";

$stmInfo = $this->conn->query($sqlExpInfo, PDO::FETCH_ASSOC);
$expInfo = $stmInfo->fetch(PDO::FETCH_ASSOC);

// Crear estructura base del response
$response = $expInfo;
$response["recogidas"] = [];
$response["almacenes"] = [];
$response["salas"] = [];
$response["repartos"] = [];

        // ----------------------------------------
        // 3A) Consulta de RECOGIDAS
        // ----------------------------------------
        $sql0 = "
            SELECT 
                r.".Recogida::CODE." as codigoRecogida,
                r.".Recogida::CENTROCODIGO." as codigoCentro,
                r.".Recogida::EMPRESARECODIGO." codigoEmpresaRecogida,
                RTRIM(r.".Recogida::EMPRESA.") as empresaRecogida,
                RTRIM(r.".Recogida::DIRECCION.") as direccionRecogida,
                r.".Recogida::CODIGOPAIS." as codigoPaisRecogida,
                r.".Recogida::CODIGOPAISISO." as codigoPaisISORecogida,
                r.".Recogida::CODIGOPOSTAL." as codigoPostalRecogida,
                RTRIM(r.".Recogida::POBLACION.") as poblacionRecogida,
                r.".Recogida::TRANSPOSTISTACODIGO." as codigoTransportista,
                RTRIM(r.".Recogida::TRANSPORTISTANOMBRE.") as nombreTransportista,
                RTRIM(r.".Recogida::VEHICULOMATRICULA.") as matriculaVehiculo,
                RTRIM(r.".Recogida::VEHICULODESCRPCION.") as descripcionVehiculo,
                r.".Recogida::FECHASALIDA." as fechaSalida,
                r.".Recogida::HORASALIDA." as horaSalida,
                r.".Recogida::FECHALLEGADA." fechaLlegada,
                r.".Recogida::HORALLEGADA." as horaLlegada
            FROM ".Recogida::TABLE." r
            INNER JOIN ".Expedicion::TABLE." e 
                ON r.".Recogida::CODE." = e.".Expedicion::RECOGIDACODE."
            WHERE e.".Expedicion::CODE." = $expeditionCode
              AND e.".Expedicion::CENTROCODE." = $centerCode
              AND r.".Recogida::CENTROCODIGO." = $centerCode
        ";

        $stm0 = $this->conn->query($sql0, PDO::FETCH_ASSOC);
        $rows0 = $stm0->fetchAll();
        foreach ($rows0 as $row) {
            $response["recogidas"][] = $row;
        }

        // ----------------------------------------
        // 3B) Consulta de ALMACENES / CONTRATO
        // (tu SQL 2 tal cual)
        // ----------------------------------------
        $sql2 = "
            SELECT 
                RTRIM(co.".Contrato::POBLACIONORIGEN.") as poblacionOrigen,
                co.".Contrato::PAISORIGENCODE." as paisOrigenCodigo,
                RTRIM(co.".Contrato::POBLACIONDESTINO.") as poblacionDestino,
                co.".Contrato::PAISDESTINOCODE." as paisDestinoCodigo,
                co.".Contrato::FECHASALIDA." as fechaCarga,
                co.".Contrato::HORASALIDA." as horaCarga,
                c.".Carga::FECHALLEGADA." as fechaLlegadaCarga,
                c.".Carga::HORALLEGADA." as horaLlegadaCarga,
                RTRIM(co.".Contrato::MATRICAMION.") as matriculaCamion,
                RTRIM(co.".Contrato::MATRIREMOLQ.") as matriculaRemolque,
                co.".Contrato::CONDUCTORCODE." as conductorCodigo,
                co.".Contrato::TRANSPCODE." as transportistaCodigo,
                RTRIM(co.".Contrato::TRANSPNOMBRE.") as transportistaNombre,
                e.".Expedicion::CODIGOMERCANCIA." as tipusMercaderia,
                e.".Expedicion::FECHAHORAREGISTRO." as dataHoraRegistre,
                e.".Expedicion::CENTROCODEDESTI." as centreDesti,
                c.".CargaDetalle::CENTROCODE." as centreOrigen
            FROM ".Carga::TABLE." c1
            INNER JOIN ".CargaDetalle::TABLE." c 
                ON c1.".Carga::CODE." = c.".CargaDetalle::CODE." AND c1.CrgCtrCod=c.CrgCtrCod
            INNER JOIN ".Contrato::TABLE." co 
                ON co.".Contrato::CODE." = c.".CargaDetalle::CONTRATOCODE."
            INNER JOIN ".Expedicion::TABLE." e 
                ON c1.".Carga::EXPCODE." = e.".Expedicion::CODE."
            WHERE c1.".Carga::EXPCODE." = $expeditionCode
              AND c1.".Carga::CENTROCODE." = $centerCode
              AND e.".Expedicion::CENTROCODE." = $centerCode
              AND c.".CargaDetalle::FECHAALTA." >= e.".Expedicion::FECHAREGISTRO."
              AND co.".Contrato::FECHASALIDA." >= e.".Expedicion::FECHAREGISTRO."
        ";
       
        $stm2 = $this->conn->query($sql2, PDO::FETCH_ASSOC);
        $rows2 = $stm2->fetchAll();
        foreach ($rows2 as $row) {
            $response["almacenes"][] = $row;
        }

        // ----------------------------------------
        // 3C) Consulta de REPARTOS
        // (tu SQL 3 tal cual)
        // ----------------------------------------
        $sql3 = "
            SELECT 
                e.".Entrega::TRANSPOSTISTACODIGO." as transportistaCodigo,
                RTRIM(e.".Entrega::TRANSPORTISTANOMBRE.") as transportistaNombre,
                RTRIM(e.".Entrega::MATRICULAVEHICULO.") as matriculaVehiculo,
                RTRIM(e.".Entrega::VEHICULO.") as descripcionVehiculo,
                e.".Entrega::FECHASALIDA." as fechaSalida,
                e.".Entrega::HORASALIDA." as horaSalida,
                e.".Entrega::FECHAENTREGA." as fechaEntrega,
                e.".Entrega::HORAENTREGA." as horaEntrega,
                e.".Entrega::FECHAHORAASIGNABLE." as fechaHoraAsignable
            FROM ".Entrega::TABLE." e
            WHERE e.".Entrega::EXPCODE." = $expeditionCode
              AND e.".Entrega::CENTROCODIGO." = $centerCode
        ";

        $stm3 = $this->conn->query($sql3, PDO::FETCH_ASSOC);
        $rows3 = $stm3->fetchAll();
        foreach ($rows3 as $row) {
            $response["repartos"][] = $row;
        }

        // ------------------------------
        // 3D) Guardar resultado final
        // ------------------------------
        $responseFinal[$expeditionCode] = $response;

    } // Fin del foreach

    // ------------------------------
    // 4) Devolver todo agrupado
    // ------------------------------
    return $responseFinal;
}
public function getExpeditionsData($year, $month, $centerCode, $startDate = null, $endDate = null)
{
    $responseFinal = [];

    // -----------------------------------------
    // Centros definidos
    // -----------------------------------------
    $allCenters = [8, 25, 80];

    // Iteramos todos 
    $centersToProcess = $allCenters;

    // -----------------------------------------
    // Cálculo del rango de fechas
    // -----------------------------------------
    if ($startDate && $endDate) {
        $start = $startDate;
        $end = $endDate;
    } else {
        $start = "$year-$month-01";
        $end = date("Y-m-d", strtotime("$start +1 days"));
    }

    // Validación de máximo 7 días
    

    
        $end = date("Y-m-d", strtotime("$start +1 days"));
   

    // ===================================================================================
    // 🔁 ITERACIÓN POR TODOS LOS CENTER CODE EXCEPTO EL QUE SE RECIBE
    // ===================================================================================
    foreach ($centersToProcess as $ctr) {

        // ------------------------------------------------------------
        // 1) Consultar todos los códigos de expedición (ExpCod)
        // ------------------------------------------------------------
        $sqlExp = "
            SELECT ExpCod
            FROM trans.dbo.EXPEDIC4
            WHERE ISDATE(ExpAltFec) = 1
              AND CONVERT(date, ExpAltFec, 103) >= '$start'
              AND CONVERT(date, ExpAltFec, 103) <= '$end'
              AND ExpDsCtCd IN (8,25,80)
              AND ExpCtrCod = $ctr
			  AND ExpDsCtCd!= $ctr
              AND ExpSit = 7
            ORDER BY CONVERT(date, ExpAltFec, 103) DESC;
        ";
        
        $stmExp = $this->conn->query($sqlExp, PDO::FETCH_ASSOC);
        $expCodes = $stmExp->fetchAll(PDO::FETCH_COLUMN);

        // Si no hay expediciones en este centro: agregar vacío y continuar
        if (empty($expCodes)) {
            $responseFinal[$ctr] = [];
            continue;
        }

        // Array para este centro
        //$responseFinal[$ctr] = [];

        // -------------------------------------------------------------
        // 2) Recorrer cada EXPEDICIÓN Y EJECUTAR TODA TU LÓGICA
        // -------------------------------------------------------------
        foreach ($expCodes as $expeditionCode) {

            // ----------------------------
            // 2A) Datos principales
            // ----------------------------
            $sqlExpInfo = "
                SELECT
                    ex.".Expedicion::CODE." as ExpCod,
                    ex.".Expedicion::HOLDINGCODE." as HolCod,
                    ex.".Expedicion::CONTRATOCODE." as ExpCntCod,
                    ex.".Expedicion::SECCIONCODE." as SecCod,
                    ex.".Expedicion::CENTROCODE." as ExpCtrCod,
                    ex.".Expedicion::FECHSAL." as ExpAltFec,
                    ex.".Expedicion::FECHALLE." as ExpHorLle,
                    ex.".Expedicion::CIUDADSAL." as ExpOloPob,
                    ex.".Expedicion::CIUDADLLE." as ExpDesPob,
                    ex.".Expedicion::ORDENANTE." as ExpOrdDes,
                    ex.".Expedicion::REMITENTE." as ExpRemDes,
                    ex.".Expedicion::DIRECCIONREMITENTE." as ExpRemDom,
                    ex.".Expedicion::PAISREMITENTECODE." as ExpRemPai,
                    ex.".Expedicion::PAISREMITENTEISO." as ExpRemNem,
                    ex.".Expedicion::CPREMITENTE." as ExpRemPos,
                    ex.".Expedicion::POBLACIONREMITENTE." as ExpRemPob,
                    ex.".Expedicion::DESTINATARIO." as ExpDesDes,
                    ex.".Expedicion::DIRECCIONDESTINATARIO." as ExpDesDom,
                    ex.".Expedicion::PAISDESTINATARIOCODE." as ExpDesPai,
                    ex.".Expedicion::PAISDESTINATARIOISO." as ExpDesNem,
                    ex.".Expedicion::CPDESTINATARIO." as ExpDesPos,
                    ex.".Expedicion::POBLACIONDESTINACION." as ExpDesPob,
                    ex.".Expedicion::RECOGIDACODE." as ExpRecNum,
                    ex.".Expedicion::FECHAREGISTRO." as ExpDatReg,
                    ex.".Expedicion::HORAREGISTRO." as ExpHorReg,
                    ex.".Expedicion::FECHAHORAREGISTRO." as ExpAltFecHora,
                    ex.".Expedicion::CODIGOMERCANCIA." as MerCod,
                    ex.".Expedicion::CENTROCODEDESTI." as ExpDsCtCd,
                    ex.".Expedicion::CODIGOEXPEDICIONTERCERO." as ExpAlbOrd,
                    ex.".Expedicion::CODIGOORDENANTE." as ExpOrdCod,
                    ex.ExpSit,
                ci.AnoCod
    FROM ".Expedicion::TABLE." as ex
	LEFT JOIN CINCIDEN as ci on  ex.ExpCod=ci.CinRef and ci.CtrCod=ex.ExpCtrCod
                WHERE ex.".Expedicion::CODE." = $expeditionCode
                  AND ex.".Expedicion::CENTROCODE." = $ctr
            ";

            $stmInfo = $this->conn->query($sqlExpInfo, PDO::FETCH_ASSOC);
            $expInfo = $stmInfo->fetch(PDO::FETCH_ASSOC);

            // Estructura del response
            $response = $expInfo;
            $response["recogidas"] = [];
            $response["almacenes"] = [];
            $response["salas"] = [];
            $response["repartos"] = [];

            // ----------------------------------------
            // 2B) Consulta de RECOGIDAS
            // ----------------------------------------
            $sql0 = "
                SELECT 
                    r.".Recogida::CODE." as codigoRecogida,
                    r.".Recogida::CENTROCODIGO." as codigoCentro,
                    r.".Recogida::EMPRESARECODIGO." codigoEmpresaRecogida,
                    RTRIM(r.".Recogida::EMPRESA.") as empresaRecogida,
                    RTRIM(r.".Recogida::DIRECCION.") as direccionRecogida,
                    r.".Recogida::CODIGOPAIS." as codigoPaisRecogida,
                    r.".Recogida::CODIGOPAISISO." as codigoPaisISORecogida,
                    r.".Recogida::CODIGOPOSTAL." as codigoPostalRecogida,
                    RTRIM(r.".Recogida::POBLACION.") as poblacionRecogida,
                    r.".Recogida::TRANSPOSTISTACODIGO." as codigoTransportista,
                    RTRIM(r.".Recogida::TRANSPORTISTANOMBRE.") as nombreTransportista,
                    RTRIM(r.".Recogida::VEHICULOMATRICULA.") as matriculaVehiculo,
                    RTRIM(r.".Recogida::VEHICULODESCRPCION.") as descripcionVehiculo,
                    r.".Recogida::FECHASALIDA." as fechaSalida,
                    r.".Recogida::HORASALIDA." as horaSalida,
                    r.".Recogida::FECHALLEGADA." fechaLlegada,
                    r.".Recogida::HORALLEGADA." as horaLlegada
                FROM ".Recogida::TABLE." r
                INNER JOIN ".Expedicion::TABLE." e 
                    ON r.".Recogida::CODE." = e.".Expedicion::RECOGIDACODE."
                WHERE e.".Expedicion::CODE." = $expeditionCode
                  AND e.".Expedicion::CENTROCODE." = $ctr
                  AND r.".Recogida::CENTROCODIGO." = $ctr
            ";

            $rows0 = $this->conn->query($sql0)->fetchAll(PDO::FETCH_ASSOC);
            $response["recogidas"] = $rows0;

            // ----------------------------------------
            // 2C) Consulta de ALMACENES
            // (SQL 2 igual, sustituyendo $centerCode por $ctr)
            // ----------------------------------------
            $sql2 = "
                SELECT 
                    RTRIM(co.".Contrato::POBLACIONORIGEN.") as poblacionOrigen,
                    co.".Contrato::PAISORIGENCODE." as paisOrigenCodigo,
                    RTRIM(co.".Contrato::POBLACIONDESTINO.") as poblacionDestino,
                    co.".Contrato::PAISDESTINOCODE." as paisDestinoCodigo,
                    co.".Contrato::FECHASALIDA." as fechaCarga,
                    co.".Contrato::HORASALIDA." as horaCarga,
                    c.".Carga::FECHALLEGADA." as fechaLlegadaCarga,
                    c.".Carga::HORALLEGADA." as horaLlegadaCarga,
                    RTRIM(co.".Contrato::MATRICAMION.") as matriculaCamion,
                    RTRIM(co.".Contrato::MATRIREMOLQ.") as matriculaRemolque,
                    co.".Contrato::CONDUCTORCODE." as conductorCodigo,
                    co.".Contrato::TRANSPCODE." as transportistaCodigo,
                    RTRIM(co.".Contrato::TRANSPNOMBRE.") as transportistaNombre,
                    e.".Expedicion::CODIGOMERCANCIA." as tipusMercaderia,
                    e.".Expedicion::FECHAHORAREGISTRO." as dataHoraRegistre,
                    e.".Expedicion::CENTROCODEDESTI." as centreDesti,
                    c.".CargaDetalle::CENTROCODE." as centreOrigen
                FROM ".Carga::TABLE." c1
                INNER JOIN ".CargaDetalle::TABLE." c 
                    ON c1.".Carga::CODE." = c.".CargaDetalle::CODE." AND c1.CrgCtrCod=c.CrgCtrCod
                INNER JOIN ".Contrato::TABLE." co 
                    ON co.".Contrato::CODE." = c.".CargaDetalle::CONTRATOCODE."
                INNER JOIN ".Expedicion::TABLE." e 
                    ON c1.".Carga::EXPCODE." = e.".Expedicion::CODE."
                WHERE c1.".Carga::EXPCODE." = $expeditionCode
                  AND c1.".Carga::CENTROCODE." = $ctr
                  AND e.".Expedicion::CENTROCODE." = $ctr
                  AND c.".CargaDetalle::FECHAALTA." >= e.".Expedicion::FECHAREGISTRO."
                  AND co.".Contrato::FECHASALIDA." >= e.".Expedicion::FECHAREGISTRO."
            ";

            $rows2 = $this->conn->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
            $response["almacenes"] = $rows2;

            // ----------------------------------------
            // 2D) Consulta de REPARTOS
            // ----------------------------------------
            $sql3 = "
                SELECT 
                    e.".Entrega::TRANSPOSTISTACODIGO." as transportistaCodigo,
                    RTRIM(e.".Entrega::TRANSPORTISTANOMBRE.") as transportistaNombre,
                    RTRIM(e.".Entrega::MATRICULAVEHICULO.") as matriculaVehiculo,
                    RTRIM(e.".Entrega::VEHICULO.") as descripcionVehiculo,
                    e.".Entrega::FECHASALIDA." as fechaSalida,
                    e.".Entrega::HORASALIDA." as horaSalida,
                    e.".Entrega::FECHAENTREGA." as fechaEntrega,
                    e.".Entrega::HORAENTREGA." as horaEntrega,
                    e.".Entrega::FECHAHORAASIGNABLE." as fechaHoraAsignable
                FROM ".Entrega::TABLE." e
                WHERE e.".Entrega::EXPCODE." = $expeditionCode
                  AND e.".Entrega::CENTROCODIGO." = $ctr
            ";

            $rows3 = $this->conn->query($sql3)->fetchAll(PDO::FETCH_ASSOC);
            $response["repartos"] = $rows3;

            // ----------------------------------------
            // 2E) Guardar resultado de la expedición
            // ----------------------------------------
            $responseFinal[$expeditionCode] = $response;

        } // foreach expedición

    } // foreach centro

    return $responseFinal;
}

	
}


?>