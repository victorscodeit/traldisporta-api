<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Jun-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/
error_reporting(E_ALL);
ini_set('display_errors', '1');

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');

//INCLUDES
//Importem les llibreries internes necessàries perque l'aplicació funcioni
include_once '../include/Config.php';
require '../include/functions.php';
require_once '../include/DbHandler.php';
require_once '../include/DbHandlerExternal.php';
require_once '../i18n/en_en.php';
require_once '../include/responseMessage.php';
require_once '../include/errorCode.php';
require_once '../include/responseCode.php';
require '../libs/Slim/Slim.php';
require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
/*require '../libs/PhpSpreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
require '../libs/PhpSpreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php';
require '../libs/PhpSpreadsheet/src/PhpSpreadsheet/Style/NumberFormat.php';*/
require('../libs/TCPDF/tcpdf.php');
require __DIR__ . '/../vendor/autoload.php';

//Importem les llibreries dels moduls personalitzats
require_once '../modules/movertis/movertis.php';
require_once '../modules/account/account.php';
require_once '../modules/facturas_impagadas/impagadas.php';
require_once '../modules/morosos/morosos.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

//El superadmin només pot cridar aquesta petició
//Agafara els camps que li arriben de la capa 1 per tal de 
//guardar aquesta informacio a la base de dades interna
$app->post('/generateUser', 'authenticate2', function () use ($app) {
    //Hem de comprobar que ens arriba els camps necessaris de la capa 1
    $requiredParams = array(
        "user_id",
        "username",
        "name",
        "api_key"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('generateUser', false, $params);

    $db = new DbHandler();
    $result = $db->generateUser($params);

    //Si no hi hagut cap error
    if ($result == OK_TRUE) {
        //Donem resposta d'usuari creat correctament
        $response = responseUserCreated();
        echoResponse(HTTP_OK, $response);
    }
    //Si hi hagut algun tipus d'error
    else {
        //Donem la resposta corresponent
        switch ($result) {
            case ERR_USER_NOT_CREATED:
                $response = responseUserNotCreated();
                echoResponse(HTTP_UNPROCESSABLE_ENTITY, $response);
                break;
            case ERR_USER_ALREADY_EXIST:
                $response = responseUserAlreadyExist();
                echoResponse(HTTP_UNPROCESSABLE_ENTITY, $response);
                break;
            default:
                $response = responseGeneralError();
                echoResponse(HTTP_INTERNAL_ERROR, $response);
                break;
        }
    }

});


//En aquesta trucada ens arribara la informacio del token de la capa 1
//i ens retornara un segon token que retornarem a la capa 1 per tal 
//es puguin fer peticions posteriorment.
/*$app->post('/token', 'authenticate', function () use ($app) {
    //Hem de comprobar que ens arriba els camps necessaris de la capa 1
    $requiredParams = array(
        "token",
        "user_id",
        "create_date",
        "expire_date"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('token', false, $params);

    $db = new DbHandler();

    $result = $db->getToken($params);

    //Si es un array, vol dir que ha operació ha anat correctament
    if (is_array($result)) {
        if ($result['isNew'] == true) {
            $response = responseTokenCreated($result);
            echoResponse(HTTP_OK, $response);
        } else {
            $response = responseTokenFound($result);
            echoResponse(HTTP_OK, $response);
        }
    }
    //Si no es un array, vol dir que ens ha tornat un codi d'error
    else {
        switch ($result) {
            case ERR_TOKEN_NOT_CREATED:
                $response = responseTokenNotCreated();
                echoResponse(HTTP_UNPROCESSABLE_ENTITY, $response);
                break;
            default:
                $response = responseGeneralError();
                echoResponse(HTTP_INTERNAL_ERROR, $response);
                break;
        }
    }

});*/

/**
 * Aquest servei anirà a buscar la informació dels clients a la base de dades de Mtrans (capa 3)
 */
/*$app->post('/customer', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('customer', false, $params);

    $response = array();
    $db = new DbHandlerExternal();
    $customers = $db->getAgenda('S');

    $response["error"] = false;
    $response["message"] = "Clientes cargados: " . count($customers);
    $response["data"] = $customers;

    echoResponse(HTTP_OK, $response);
});*/

/**
 * Aquest servei anirà a buscar la informació dels proveidors a la base de dades de Mtrans (capa 3)
 */
/*$app->post('/supplier', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('supplier', false, $params);

    $response = array();
    $db = new DbHandlerExternal();
    $supplier = $db->getAgenda('N');

    $response["error"] = false;
    $response["message"] = "Proveedores cargados: " . count($supplier);
    $response["data"] = $supplier;

    echoResponse(HTTP_OK, $response);
});*/
$app->post('/getExpeditionsData', 'authenticate', function () use ($app) {

    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "year",
        "month",
        "centerCode"
        //"token"
    );

    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('getExpeditionsData', false, $params);

    $response = array();
    $m = new Movertis();
    
    // Parámetros opcionales para rango de fechas
    $startDate = isset($params['startDate']) ? $params['startDate'] : null;
    $endDate = isset($params['endDate']) ? $params['endDate'] : null;
    
    // Llamar a la función con los parámetros (incluyendo los opcionales)
    $trucks = $m->getExpeditionsData(
        $params['year'],
        $params['month'], 
        $params['centerCode'],
        $startDate,
        $endDate
    );

    $response = responseTrucks($trucks);

    echoResponse(HTTP_OK, $response);
});
$app->post('/getTrucksExpedition', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionCode",
		"centerCode"
        //"token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    //validateToken();

    //Comprovem que el camp checksum sigui correcte
    //checkChecksum();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('getTrucksExpedition', false, $params);

    $response = array();
    $m = new Movertis();
    $trucks = $m->getTrucksExpedition($params['expeditionCode'], $params['centerCode']);  //1049063 //1045884 (exemples)

    $response = responseTrucks($trucks);

    echoResponse(HTTP_OK, $response);
});
$app->post('/getTemperatureReportRedur', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionOrder"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);
	
	//Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);
	$m = new Movertis();
	$order=$m->getExpeditionFromOrder($params['expeditionOrder']);
	//print_r($order[0]['codigoExpedicion']);
	//Obtenermos el report de temperaturas
	
	$datos_finales=array();
	if(count($order)>0){
		// Solo se permite consultar expediciones de Ramoneda (codigoOrdenante = 1376)
		if (!isset($order[0]['codigoOrdenante']) || (string)$order[0]['codigoOrdenante'] !== '1376') {
			echoResponse(HTTP_BAD_REQUEST, array(
				'error' => true,
				'message' => 'Expedicion no encontrada.'
			));
			return;
		}

		if($order[0]['codigoExpedicion']!='' && $order[0]['codigoCentro']!=''){
			$expeditionCode=$order[0]['codigoExpedicion'];
			$centerCode=$order[0]['codigoCentro'];
			/*$expeditionCode=1075590;
			$centerCode=8;*/
			$datos_finales=getTemperatureReport($expeditionCode,$centerCode);

		}
	}
	
	echoResponse(HTTP_OK, $datos_finales);
});
$app->post('/getTemperatureReportRamoneda', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionOrder"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);
	
	//Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);
	$m = new Movertis();
	$order=$m->getExpeditionFromOrder($params['expeditionOrder']);
	//print_r($order[0]['codigoExpedicion']);
	//Obtenermos el report de temperaturas
	
	$datos_finales=array();
	if(count($order)>0){
		if($order[0]['codigoExpedicion']!='' && $order[0]['codigoCentro']!=''){
			$expeditionCode=$order[0]['codigoExpedicion'];
			$centerCode=$order[0]['codigoCentro'];
			/*$expeditionCode=1075590;
			$centerCode=8;*/
			$datos_finales=getTemperatureReport($expeditionCode,$centerCode);

		}
	}
	
	echoResponse(HTTP_OK, $datos_finales);
});
$app->post('/getTemperatureReportTest', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionCode",
		"centerCode"
        //"token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);
	
	//Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);
	$m = new Movertis();
	
	//print_r($order[0]['codigoExpedicion']);
	//Obtenermos el report de temperaturas
	
	$datos_finales=array();
    $datos_finales=getTemperatureReport($params['expeditionCode'], $params['centerCode']);
	//print_r($datos_finales);
	//print_r($datos_finales);
	
	echoResponse(HTTP_OK, $datos_finales);
});

$app->post('/getShowvehicles', 'authenticate', function () use ($app) {
	$m = new Movertis();
	$data = $m->showvehicles(false);
	echoResponse(HTTP_OK, $data);
});

$app->post('/refreshShowvehicles', 'authenticate', function () use ($app) {
	$m = new Movertis();
	$data = $m->showvehicles(true);
	echoResponse(HTTP_OK, $data);
});

$app->post('/getGPSExpeditionExternal', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionCode",
		"centerCode"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);
	
	//Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);
	$m = new Movertis();
	$respuesta=$m->getExpeditionFromOrder($params['expeditionCode']);
	$expeditionCode=$respuesta[0]['codigoExpedicion'];
	$centerCode=$respuesta[0]['codigoCentro'];
	//Obtenemos el report de temperaturas
	$datos_finales=array();
    $datos_finales=getTemperatureReport($expeditionCode, $centerCode);
	
	// Filtrar solo los datos con coordenadas GPS válidas
	$datos_filtrados = filterGPSData($datos_finales);
	
	echoResponse(HTTP_OK, $datos_filtrados);
});

$app->post('/getGPSExpeditionInternal', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionCode",
		"centerCode"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);
	
	//Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);
	$m = new Movertis();
	
	//Obtenemos el report de temperaturas
	$datos_finales=array();
    $datos_finales=getTemperatureReport($params['expeditionCode'], $params['centerCode']);
	
	// Filtrar solo los datos con coordenadas GPS válidas
	$datos_filtrados = filterGPSData($datos_finales);
	
	echoResponse(HTTP_OK, $datos_filtrados);
});

function filterGPSData($datos) {
	$datos_filtrados = array();
	
	// Recorrer cada fase (clave del array)
	foreach ($datos as $fase => $datos_fase) {
		// Verificar que sea un array
		if (is_array($datos_fase)) {
			// Recorrer cada registro de la fase
			foreach ($datos_fase as $registro) {
				// Verificar que ubicacion_y y ubicacion_x estén rellenados
				if (isset($registro['ubicacion_y']) && 
					isset($registro['ubicacion_x']) && 
					trim($registro['ubicacion_y']) != '' && 
					trim($registro['ubicacion_x']) != '') {
					
					// Crear un nuevo registro solo con los campos necesarios
					$registro_filtrado = array(
						"sonda" => isset($registro['sonda']) ? $registro['sonda'] : '',
						"fase" => isset($registro['fase']) ? $registro['fase'] : '',
						"tiempo" => isset($registro['tiempo']) ? $registro['tiempo'] : '',
						"ubicacion_y" => trim($registro['ubicacion_y']),
						"ubicacion_x" => trim($registro['ubicacion_x'])
					);
					
					// Agregar el registro al array plano (sin agrupar por fase)
					$datos_filtrados[] = $registro_filtrado;
				}
			}
		}
	}
	
	return $datos_filtrados;
}


function getTemperatureReport($expeditionCode, $centerCode) {
	 
	 $m = new Movertis();
	
	
	$truckPlates=array();	
	$salas=array();
	$trucks = $m->getTrucksExpedition($expeditionCode, $centerCode);
	//print_r($trucks);
	$recogidas = $trucks['recogidas'];
    $almacenes = $trucks['almacenes'];
    $repartos = $trucks['repartos'];
	$salas = $trucks['salas'];    
	
	

	foreach ($recogidas as  $recogida) {
            if ($recogida['matriculaVehiculo'] != "") {
              $start = dateFusion($recogida['fechaSalida'], $recogida['horaSalida']);
              $finish = dateFusion($recogida['fechaLlegada'], $recogida['horaLlegada']);

              $v = [
                "truck"=> strtoupper($recogida['matriculaVehiculo']),
                "start"=> $start,
                "finish"=> $finish,
                "type"=> "collect"
              ];
              $truckPlates[] = $v;
            }
    }

	 if(count($salas)>0 && isset($salas[0]['inicial'])){
              $start = $salas[0]['inicial']['horaInici'];
              $finish = $salas[0]['inicial']['horaFi'];

              $v = [
                "truck"=> $salas[0]['inicial']['idVehicle'],
                "start"=> $start,
                "finish"=> $finish,
                "type"=> "sala",
				"name"=> $salas[0]['inicial']['nomSala']
              ];
              $truckPlates[] = $v;
	}
	
	      $contador=0;
          foreach($almacenes as $almacen) {
			 //miramos si existe una parada en CAU
			if($contador==1 and isset($salas[1]['inicial'])){
			  $start = $salas[1]['inicial']['horaInici'];
              $finish = $salas[1]['inicial']['horaFi'];

              $v = [
                "truck"=> $salas[1]['inicial']['idVehicle'],
                "start"=> $start,
                "finish"=> $finish,
                "type"=> "sala",
				"name"=> $salas[1]['inicial']['nomSala']
              ];
              $truckPlates[] = $v;
			}
            if ($almacen['matriculaCamion'] != "") {
              $start = dateFusion($almacen['fechaCarga'], $almacen['horaCarga']);
              $finish = dateFusion($almacen['fechaLlegadaCarga'], $almacen['horaLlegadaCarga']);
			  $matricula='';
			  if($almacen['matriculaRemolque'] != ""){
			  $matricula= $almacen['matriculaRemolque'];
			  }else{
			  $matricula= $almacen['matriculaCamion'];
			  }
              $v = [
                "truck"=> strtoupper($matricula),
                "start"=> $start,
                "finish"=> $finish,
                "type"=> "warehouse"
              ];
              $truckPlates[] = $v;
            }
			$contador++;
          }
		  
          if(count($salas)>0){
              if(isset($salas[1]['reparto'])){
			  $numsala=1;
			  }else{
				  if(isset($salas[2]['reparto'])){
					  $numsala=2;
				  }else{
					  $numsala=0;
				  }					  
			  }
              $start = $salas[$numsala]['reparto']['horaInici'];
              $finish =  $salas[$numsala]['reparto']['horaFi'];

              $v = [
                "truck"=>  $salas[$numsala]['reparto']['idVehicle'],
                "start"=> $start,
                "finish"=> $finish,
                "type"=> "sala",
				"name"=> $salas[$numsala]['reparto']['nomSala']
              ];
              $truckPlates[] = $v;
			 }
            
          
          foreach($repartos as $reparto) {
            if ($reparto['matriculaVehiculo'] != "") {
              $start = dateFusion($reparto['fechaSalida'], $reparto['horaSalida']);
              $finish = dateFusion($reparto['fechaEntrega'], $reparto['horaEntrega']);
              $v = [
                "truck"=> strtoupper($reparto['matriculaVehiculo']),
                "start"=> $start,
                "finish"=> $finish,
                "type"=> "delivery"
              ];
              $truckPlates[] = $v;
            }
	}
	
	$sondas=$m->showvehicles();
	
	$truckIds=array();
	foreach($truckPlates as $truck) {
	    if($truck['type']!='sala'){
                //Obtenim l'ID del camió
                $truckIds[]=getTruckIdFromResponse($truck['truck'], $sondas);
		}else{
				$truckIds[]=$truck['truck'];
		}
    }
	
	$i = 0;
	$datos_finales=array();
	
    foreach ($truckIds as $truckId) {
        $label = "";
		
        switch ($truckPlates[$i]['type']) {
            case "collect":
                $label = "Recogida";
                break;
            case "warehouse":
                $label = "Almacén";
                break; 
            case "delivery":
                $label = "Reparto";
                break; 
            case "sala":
                $label = $truckPlates[$i]['name'];
                break;					
        }
		if ($truckId == false) {
                  //$datos_finales[$label] = "Matricula no encontrada en Movertis.";
				  logging('getTrucksExpedition', false, "Matricula no encontrada en Movertis -> Matricula=".$truckPlates[$i]['truck'].", Expedicion: ".$expeditionCode.", Centro: ".$centerCode.", Sonda:".$truckId.", Fecha Inicio:".$truckPlates[$i]['start']." - timestamp:".datetimeStrToTimestamp($truckPlates[$i]['start']).", Fecha fin:".$truckPlates[$i]['finish']." - timestamp".datetimeStrToTimestamp($truckPlates[$i]['finish']) );
		
        }else {
			
			$datos=$m->checkreport($truckId,datetimeStrToTimestamp($truckPlates[$i]['start']),datetimeStrToTimestamp($truckPlates[$i]['finish']));
			//echo $truckPlates[$i]['truck'].",".$truckId.",".datetimeStrToTimestamp($truckPlates[$i]['start']).",".datetimeStrToTimestamp($truckPlates[$i]['finish']);
			
			if (is_array($datos) && isset($datos[$truckId][0]) && is_array($datos[$truckId][0]) && count($datos[$truckId][0]) > 0) {
			    if ($label=='Almacén' || $label=='Reparto' || $label=='Recogida'){$label=$truckPlates[$i]['truck'];}
				if(isset($datos_finales[$label]) &&	is_array($datos_finales[$label])){		
					$data=parseResponseInfo($truckId, $datos, $label, $truckId);
					$combinado=[...$datos_finales[$label], ...$data];
					$datos_finales[$label]=$combinado;
				}else{
					$datos_finales[$label]=parseResponseInfo($truckId, $datos, $label, $truckId);
				}
			}else{
				//echo "<br>SIN DATOS! Sonda:".$truckId." Matrícula:".$truckPlates[$i]['truck']." Inicio:".$truckPlates[$i]['start']." (timestamp13:".datetimeStrToTimestamp($truckPlates[$i]['start']).") Finish:".$truckPlates[$i]['finish']." (timestamp13:".datetimeStrToTimestamp($truckPlates[$i]['finish']);
				logging('getTrucksExpedition', false, "Sin datos en movertis -> Matricula=".$truckPlates[$i]['truck'].", Expedicion: ".$expeditionCode.", Centro: ".$centerCode.", Sonda:".$truckId.", Fecha Inicio:".$truckPlates[$i]['start']." - timestamp:".datetimeStrToTimestamp($truckPlates[$i]['start']).", Fecha fin:".$truckPlates[$i]['finish']." - timestamp".datetimeStrToTimestamp($truckPlates[$i]['finish']) );
			//$datos_finales[$label]="Sin datos.";
			}
			
		}
		$i++;
	}
	//print_r($datos_finales);
	//die();
	
	return $datos_finales;
	
	
}
$app->post('/generateExpeditionPdfs', 'authenticate', function () use ($app) {
	
	
    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $m = new Movertis();
	//Obtenemos las ultimas expediciones finalizadas en el dia de hoy
    $expediciones24=$m->getExpeditions24();
	foreach($expediciones24 as $exp){
		$datos_finales=array();
		if($exp['codigoExpedicion']!='' && $exp['codigoCentro']!=''){
			
			
			$expeditionCode=$exp['codigoExpedicion'];
			$centerCode=$exp['codigoCentro'];
			
			$filePath = 'C:\wamp64\www\oms\restapi_prod\v1\pdf\\'.$centerCode.$expeditionCode.".pdf";
			//echo $filePath;
			// Comprobar si el archivo existe
			if (file_exists($filePath)) {
				continue;
			} else {
			//echo "El archivo $filePath no existe.";
			//Obtenermos el report de temperaturas
			$datos_finales=getTemperatureReport($expeditionCode,$centerCode);
			//Comprobamos si hay datos
			$haydatos=false;
			foreach($datos_finales as $data){
				// Si tenemos datos en el array, generaremos una tabla mostrando los resultados
				if (count($data) > 0) {
					$haydatos=true;
				}
			}
			if($haydatos){
				//Transformamos los datos en HTML
				$html=renderTableResults($datos_finales, $expeditionCode, $centerCode);
				//Generamos el PDF
				generateExpeditionReportPdf($html, $expeditionCode, $centerCode);
			}
			}
		}	
	}
	

	

    echoResponse(HTTP_OK, "PDFs Generados");
});
function generateExpeditionReportPdf($html, $expeditionCode, $centerCode){
	// Crear una instancia de TCPDF
$pdf = new CustomPDF();

// Establecer la información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Traldis Porta');
$pdf->SetTitle('Informe expedición '.$expeditionCode);
$pdf->SetSubject('Informe expedición '.$expeditionCode);
$pdf->SetKeywords('Traldis, PDF, informe');

// Establecer los márgenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Establecer el salto de página automático
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Añadir una página
$pdf->AddPage();
// Establecer la opción de no repetir las cabeceras de la tabla
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Escribir el contenido HTML
$pdf->writeHTML($html, true, false, true, false, '');

// Guardar el PDF en una carpeta del servidor
$directory = 'C:\wamp64\www\oms\restapi_prod\v1\pdf';
//$directory = 'Z:\temperatura';
$filename = $directory . '/'.$centerCode.''.$expeditionCode.'.pdf';
$pdf->Output($filename, 'F');


}
class CustomPDF extends TCPDF {
    // Sobrescribir el método Header para eliminar la cabecera
       public function Header() {
        // Ruta al archivo del logo
        $logoPath = '../assets/img/logo.png';
        // Posición X, Y, Ancho
        $this->Image($logoPath, 10, 10, 30);
        // Fuente para el título
        //$this->SetFont('helvetica', 'B', 12);
        // Título de la cabecera
        //$this->Cell(0, 15, 'Título del Documento', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        // Línea
        $this->Ln(20);
    }
}

function renderTableResults($datos_finales, $expeditionCode, $centerCode) {
	// Leer el contenido del archivo CSS
// Obtener la fecha actual
$current_date = date('d-m-Y H:i');
	$tabla = '
	<style>
	table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    border: 1px solid black;
    padding: 5px;
    text-align: left;
	font-size:8px;
}
thead {
    display: table-header-group;
}
</style>
	
	<div><h2>Informe expedición: '.$expeditionCode.'</h2></div>
	<div><h4>Código centro: '.$centerCode.'</h4></div>
	<div><h6>Fecha de generación: '.$current_date.'</h6></div>
	
									  <table  >
										<thead>
										  <tr>
											<th>Fecha</th>
											<th>Fase</th>
											<th>Sonda</th>
											<th>Tº Caja 1</th>
											<th>Tº Caja 2</th>
											<th>Ubicación</th>
										  </tr>
										</thead>
										<tbody>';
	
	foreach($datos_finales as $data){
    // Si tenemos datos en el array, generaremos una tabla mostrando los resultados
    if (count($data) > 0) {
        
        foreach ($data as $item) {
            $urlmaps = "https://www.google.com/maps?q=" . $item['ubicacion_y'] . "," . $item['ubicacion_x'];

            $tabla .= '
            <tr>
                <td>' . htmlspecialchars($item['tiempo']) . '</td>
                <td>' . htmlspecialchars($item['fase']) . '</td>
                <td>' . htmlspecialchars($item['sonda']) . '</td>
                <td>' . htmlspecialchars($item['caja1']) . '</td>
                <td>' . htmlspecialchars($item['caja2']) . '</td>
                <td><a href="' . htmlspecialchars($urlmaps) . '" target="_blank">Ver Maps</a></td>
            </tr>';
        }

        
    } else {
        // Retornar un mensaje si no hay datos
        //return '';
    }
	}
	$tabla.="</tbody></table>";
	return $tabla;
}
function parseResponseInfo($truckId, $response, $fase, $sonda) {
    $data = [];

    // Comprovem si aquest ID esta a l'array de resposta
    if (array_key_exists($truckId, $response)) {
        $base = $response[$truckId];

        foreach ($base as $i) {
            foreach ($i as $j) {
                if (array_key_exists("details", $j)) {
                    $details = $j["details"];
                    foreach ($details as $detail) {
                        $y = "";
                        $x = "";
                        if (isset($detail["Ubicación"])) {
                            if (isset($detail["Ubicación"]["y"])) {
                                $y = $detail["Ubicación"]["y"];
                            }
                            if (isset($detail["Ubicación"]["x"])) {
                                $x = $detail["Ubicación"]["x"];
                            }
                        } else {
                            $x = "";
                            $y = "";
                        }
                        if (isset($detail["Tiempo"])) {
                            if (isset($detail["Tiempo"]["t"])) {
                                $tiempo = $detail["Tiempo"]["t"];
                                if ($tiempo != null) {
                                    $caja1 = '-';
                                    $caja2 = '-';
                                    if (isset($detail["ºC CAIXA 1"]) || isset($detail["ºC CARGA 1"])) {
                                        if (isset($detail["ºC CAIXA 1"])) {
                                            $caja1 = $detail["ºC CAIXA 1"];
                                        }
                                        if (isset($detail["ºC CARGA 1"])) {
                                            $caja1 = $detail["ºC CARGA 1"];
                                        }
                                    }
                                    if (isset($detail["ºC CAIXA 2"]) || isset($detail["ºC CARGA 2"])) {
                                        if (isset($detail["ºC CAIXA 2"])) {
                                            $caja2 = $detail["ºC CAIXA 2"];
                                        }
                                        if (isset($detail["ºC CARGA 2"])) {
                                            $caja2 = $detail["ºC CARGA 2"];
                                        }
                                    }

                                    // Afegim les dades desitjades en un array
                                    $data[] = [
                                        "sonda" => $sonda,
                                        "fase" => $fase,
                                        "tiempo" => $tiempo,
                                        "caja1" => $caja1,
                                        "caja2" => $caja2,
                                        "ubicacion_y" => $y,
                                        "ubicacion_x" => $x
                                    ];
                                }
                            } else {
                                // Modificació trobada a la resposta de movertis sobre la expedició 1074226
                                $tiempo = $detail["Tiempo"];
                                if ($tiempo != null) {
                                    $caja1 = '-';
                                    $caja2 = '-';
                                    if (isset($detail["ºC CAIXA 1"]) || isset($detail["ºC CARGA 1"])) {
                                        if (isset($detail["ºC CAIXA 1"])) {
                                            $caja1 = $detail["ºC CAIXA 1"];
                                        }
                                        if (isset($detail["ºC CARGA 1"])) {
                                            $caja1 = $detail["ºC CARGA 1"];
                                        }
                                    }
                                    if (isset($detail["ºC CAIXA 2"]) || isset($detail["ºC CARGA 2"])) {
                                        if (isset($detail["ºC CAIXA 2"])) {
                                            $caja2 = $detail["ºC CAIXA 2"];
                                        }
                                        if (isset($detail["ºC CARGA 2"])) {
                                            $caja2 = $detail["ºC CARGA 2"];
                                        }
                                    }

                                    // Afegim les dades desitjades en un array
                                    $data[] = [
                                        "sonda" => $sonda,
                                        "fase" => $fase,
                                        "tiempo" => $tiempo,
                                        "caja1" => $caja1,
                                        "caja2" => $caja2,
                                        "ubicacion_y" => $y,
                                        "ubicacion_x" => $x
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return $data;
}
function datetimeStrToTimestamp($strDate) {
    // Añadir "Z" al final para indicar que la fecha está en UTC
    $strDate .= "Z";
    
    // Crear un objeto DateTime
    $date = new DateTime($strDate);
    
    // Obtener el timestamp en segundos
    $timestamp = $date->getTimestamp();
    
    return $timestamp*1000;
}
function dateFusion($date, $hour) {
  $dateClean = explode(" ",$date)[0];
  $hourclean = explode(" ",$hour)[1];

  return $dateClean . " " . $hourclean;
}
//A partir del llistat de camions, busquem l'id del camió que correspon a la matricula subministrada
function getTruckIdFromResponse($truckPlate, $data) {
  $normalize = function ($plate) {
    $plate = strtoupper((string)$plate);
    return str_replace(array(' ', '-'), '', $plate);
  };

  $target = $normalize($truckPlate);

  $datasets = array();
  if (is_array($data)) {
    $datasets[] = $data;
    if (isset($data['data']) && is_array($data['data'])) $datasets[] = $data['data'];
    if (isset($data['result']) && is_array($data['result'])) $datasets[] = $data['result'];
    if (isset($data['vehicles']) && is_array($data['vehicles'])) $datasets[] = $data['vehicles'];
  }

  foreach ($datasets as $datos) {
    foreach ($datos as $truck) {
      if (!is_array($truck) || !isset($truck['name'], $truck['idVehicle'])) {
        continue;
      }

      if ($normalize($truck['name']) === $target) {
        return $truck['idVehicle'];
      }
    }
  }

  return false;
}
$app->post('/getTemperaturesExpedition', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "expeditionCode"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    //validateToken();

    //Comprovem que el camp checksum sigui correcte
    //checkChecksum();

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $m = new Movertis();
    $respuesta=$m->getExpeditionFromOrder($params['expeditionCode']);
	$expeditionCode=$respuesta[0]['codigoExpedicion'];
	$centerCode=$respuesta[0]['codigoCentro'];
	$trucks = $m->getTrucksExpedition($expeditionCode, $centerCode);  //1049063 //1045884 (exemples)
	

    $response = responseTrucks($trucks);

    echoResponse(HTTP_OK, $response);
});


$app->post('/getSaleInvoices', 'authenticate', function () use ($app) { 
    
	 //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "dateInit",
		"dateEnd"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);


    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $ac = new Account();
    $respuesta=$ac->getAllSaleAccountInfo($params['dateInit'],$params['dateEnd']);


    echoResponse(HTTP_OK, $respuesta);
});
$app->post('/getAllMovements', 'authenticate', function () use ($app) { 
    
	 //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "dateInit",
		"dateEnd"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);


    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $ac = new Account();
    $respuesta=$ac->getAllMovements($params['dateInit'],$params['dateEnd']);


    echoResponse(HTTP_OK, $respuesta);
});
$app->post('/getBuyInvoices', 'authenticate', function () use ($app) { 
    
	 //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "dateInit",
		"dateEnd"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);


    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $ac = new Account();
    $respuesta=$ac->getAllBuyAccountInfo($params['dateInit'],$params['dateEnd']);


    echoResponse(HTTP_OK, $respuesta);
});

$app->post('/getDetailInvoiceBuy', 'authenticate', function () use ($app) { 

    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "ImpFraNum",
		"ImpFraCtr",
		"ImpFraSer"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $ac = new Account();
    $respuesta=$ac->getDetailInvoiceBuy($params['ImpFraNum'], $params['ImpFraCtr'], $params['ImpFraSer']);


    echoResponse(HTTP_OK, $respuesta);
});

$app->post('/getDetailInvoice', 'authenticate', function () use ($app) { 

    //Camps necessitaris per aquesta petició
    $requiredParams = array(
        "ImpFraNum",
		"ImpFraCtr",
		"ImpFraSer"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    //logging('getTrucksExpedition', false, $params);

    $response = array();
    $ac = new Account();
    $respuesta=$ac->getDetailInvoice($params['ImpFraNum'], $params['ImpFraCtr'], $params['ImpFraSer']);


    echoResponse(HTTP_OK, $respuesta);
});

$app->post('/agenda', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    $requiredParams = array(
       // "token",
        "type"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    /*validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('agenda', false, $params);  

    $response = array();
    $db = new DbHandlerExternal();
    $agenda = $db->getAgenda($params['type']);

    $response["error"] = false;
    $response["message"] = "Contactos cargados: " . count($agenda);
    $response["data"] = $agenda;

    echoResponse(HTTP_OK, $response);
});

$app->post('/companies', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('companies', false, $params);  

    $response = array();
    $db = new DbHandlerExternal();
    $companies = $db->getCompanies();

    $response["error"] = false;
    $response["message"] = "Companyies carregades: " . count($companies);
    $response["data"] = $companies;

    echoResponse(HTTP_OK, $response);
});

$app->post('/centers', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('centers', false, $params);  

    $response = array();
    $db = new DbHandlerExternal();
    $centers = $db->getCenters();

    $response["error"] = false;
    $response["message"] = "Centres carregats: " . count($centers);
    $response["data"] = $centers;

    echoResponse(HTTP_OK, $response);
});

$app->post('/categories', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('categories', false, $params);  

    $response = array();
    $db = new DbHandlerExternal();
    $categories = $db->getCategories();

    $response["error"] = false;
    $response["message"] = "Categories carregades: " . count($categories);
    $response["data"] = $categories;

    echoResponse(HTTP_OK, $response);
});

$app->post('/sections', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('sections', false, $params);    

    $response = array();
    $db = new DbHandlerExternal();
    $sections = $db->getSections();

    $response["error"] = false;
    $response["message"] = "Seccions carregades: " . count($sections);
    $response["data"] = $sections;

    echoResponse(HTTP_OK, $response);
});

$app->post('/sectors', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token"
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('sectors', false, $params);  

    $response = array();
    $db = new DbHandlerExternal();
    $sectors = $db->getSectors();

    $response["error"] = false;
    $response["message"] = "Sectors carregades: " . count($sectors);
    $response["data"] = $sectors;

    echoResponse(HTTP_OK, $response);
});

$app->post('/total_invoiced', 'authenticate', function () use ($app) {
    //Camps necessitaris per aquesta petició
    /*$requiredParams = array(
        "token",
        "agenda_id", //opt
        "date_from", //opt
        "date_end" //opt
    );
    //Comprovem que aquests camps estiguin en el body de la petició
    verifyRequiredParams($requiredParams);

    //Validem que el token sigui vàlid
    /*validateToken();

    //Comprovem que el camp checksum sigui correcte
    checkChecksum();*/

    //Recuperem els paràmetres del body
    $params = json_decode($app->request->getBody(), true);

    logging('total_invoiced', false, $params);     

    $response = array();
    $db = new DbHandlerExternal();
    $sectors = $db->getTotalInvoicedAgenda($params);

    $response["error"] = false;
    $response["message"] = "Dades carregades: " . count($sectors);
    $response["data"] = $sectors;

    echoResponse(HTTP_OK, $response);
});

//IMPAGADAS
$app->post('/unpaid_bills', 'authenticate', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);

    $dateInit      = $params['dateInit'] ?? false;
    $dateEnd       = $params['dateEnd'] ?? false;
    $invoiceNum    = $params['invoiceNum'] ?? false;
    $customerId    = $params['customerId'] ?? false;
    $customerName  = $params['customerName'] ?? false;
    $center        = $params['center'] ?? false;
    $serie         = $params['serie'] ?? false;
    $documentType  = $params['documentType'] ?? 'customer';

    // Cargar el módulo
    require_once(__DIR__ . '/../modules/facturas_impagadas/impagadas.php');

    $mod = new impagadas();
    $result = $mod->getInvoices($dateInit, $dateEnd, $invoiceNum, $customerId, $customerName, $center, $serie, $documentType);

    $response = [
        'error' => false,
        'message' => 'Facturas encontradas: ' . count($result),
        'data' => $result
    ];

    echoResponse(200, $response);
});

//MOROSOS
$app->post('/morosos', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $action = $params['action'] ?? null;
    $morosos = new morosos();

    switch ($action) {
        case 'all_incidences':
            $result = $morosos->getIncidencias(
                $params['customerCode'] ?? '',
                $params['dateInit'] ?? false,
                $params['dateEnd'] ?? false
            );
            break;
        case 'all_gestions':
            $result = $morosos->getGestionList($params['incCode'] ?? '');
            break;
        case 'detail_incidence':
            $result = $morosos->getGestionInfo(
                $params['incCode'] ?? '',
                $params['gestioNum'] ?? false
            );
            break;
        default:
            $result = ['error' => true, 'message' => 'Acción no válida'];
    }

    echo json_encode($result);
});

$app->post('/morosos/incidencias', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $morosos = new morosos();

    $customerCode = $params['customerCode'] ?? '';
    $dateInit = $params['dateInit'] ?? false;
    $dateEnd = $params['dateEnd'] ?? false;

    $result = $morosos->getIncidencias($customerCode, $dateInit, $dateEnd);
    echo json_encode($result);
});

$app->post('/morosos/gestion_list', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $morosos = new morosos();

    $incCode = $params['incCode'] ?? '';
    $result = $morosos->getGestionList($incCode);
    echo json_encode($result);
});

$app->post('/morosos/gestion_detail', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $morosos = new morosos();

    $incCode = $params['incCode'] ?? '';
    $gestioNum = $params['gestioNum'] ?? false;

    $result = $morosos->getGestionInfo($incCode, $gestioNum);
    echo json_encode($result);
});

$app->post('/morosos/facturas_pendientes', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $morosos = new morosos();

    $dateInit = $params['dateInit'] ?? false;
    $dateEnd = $params['dateEnd'] ?? false;
    $salesman = $params['salesman'] ?? false;

    $result = $morosos->getFacturasPendientes($dateInit, $dateEnd, $salesman);
    echo json_encode($result);
});


$app->post('/morosos/has_incidence', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $morosos = new morosos();

    $customerCode = $params['customerCode'] ?? '';
    $dateInit = $params['dateInit'] ?? false;
    $dateEnd = $params['dateEnd'] ?? false;

    $result = $morosos->hasIncidence($customerCode, $dateInit, $dateEnd);
    echo json_encode($result);
});

$app->post('/morosos/send_report_morosos', function () use ($app) {
    $params = json_decode($app->request->getBody(), true);
    $morosos = new morosos();


    $result = $morosos->sendReportMorosos();
    echo json_encode($result);
});



// POST /getMonthlyMovements
$app->post('/getMonthlyMovements', 'authenticate', function () use ($app) {
    // Campos requeridos
    $requiredParams = array('month', 'year');
    verifyRequiredParams($requiredParams);

    // Recupera parámetros
    $params = json_decode($app->request->getBody(), true);
    logging('getMonthlyMovements', false, $params);

    $month = (int)$params['month'];
    $year  = (int)$params['year'];

    // Validaciones básicas
    if ($month < 1 || $month > 12) {
        echoResponse(HTTP_BAD_REQUEST, array('message' => 'month debe estar entre 1 y 12.'));
        return;
    }
    if ($year < 2000 || $year > 2100) {
        echoResponse(HTTP_BAD_REQUEST, array('message' => 'year fuera de rango razonable.'));
        return;
    }

    try {
        $m = new Account(); // Cambia por tu clase real
        $rows = $m->getMonthlyMovements($month, $year);

        // Devuelve solo el array de datos
        echoResponse(HTTP_OK, $rows);
    } catch (Exception $e) {
        echoResponse(HTTP_INTERNAL_SERVER_ERROR, array('message' => 'Error procesando la solicitud.'));
    }
});
// POST /getYearMovements
$app->post('/getYearMovements', 'authenticate', function () use ($app) {
    // Campos requeridos
    $requiredParams = array('month', 'year');
    verifyRequiredParams($requiredParams);

    // Recupera parámetros
    $params = json_decode($app->request->getBody(), true);
    logging('getMonthlyMovements', false, $params);

    //$month = (int)$params['month'];
    $year  = (int)$params['year'];

    // Validaciones básicas
   /* if ($month < 1 || $month > 12) {
        echoResponse(HTTP_BAD_REQUEST, array('message' => 'month debe estar entre 1 y 12.'));
        return;
    }*/
    if ($year < 2000 || $year > 2100) {
        echoResponse(HTTP_BAD_REQUEST, array('message' => 'year fuera de rango razonable.'));
        return;
    }

    try {
        $m = new Account(); // Cambia por tu clase real
        $rows = $m->getYearMovements($month, $year);

        // Devuelve solo el array de datos
        echoResponse(HTTP_OK, $rows);
    } catch (Exception $e) {
        echoResponse(HTTP_INTERNAL_SERVER_ERROR, array('message' => 'Error procesando la solicitud.'));
    }
});


/* corremos la aplicación */
$app->run();

?>