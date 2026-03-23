<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require('fpdf/fpdf.php');

    if (isset($_REQUEST['imagen']) && isset($_REQUEST['exp']) && isset($_REQUEST['ctr'])) {
        // Obtener la imagen en base64
        $imagenBase64 = $_REQUEST['imagen'];

        // Decodificar la imagen base64
        $imagenDecodificada = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imagenBase64));

        // Generar un nombre de archivo único
        $nombreArchivo = uniqid() . '.png';

        // Definir la ruta donde se guardará la imagen en el servidor (puede variar según tu configuración)
        $rutaGuardado = 'tmp/' . $nombreArchivo;		
		
        // Guardar la imagen en el servidor
        if (file_put_contents($rutaGuardado, $imagenDecodificada)) {
            //echo "https://blissful-sutherland.89-44-32-145.plesk.page/temperature_truck/tmp/".$nombreArchivo;
			$image = "https://blissful-sutherland.89-44-32-145.plesk.page/temperature_truck/tmp/".$nombreArchivo;
			list($width, $height, $type, $attr) = getimagesize($image);
			$pdf=new FPDF();
			
			$pdf->SetSize(($width/1.8)+110,($height*57/100)); //Custom function
			$pdf->AddPage('','custom');
			$pdf->cell(1000,1000,'',$pdf->Image($image,10,10,235,$height*18/100),'PNG');
			//$pdf->Image("https://blissful-sutherland.89-44-32-145.plesk.page/temperature_truck/tmp/".$nombreArchivo,0,0,0,0,'PNG');
			
			$filename="tmp/".$_REQUEST['ctr'] . $_REQUEST['exp'] .".pdf";
			$pdf->Output($filename,'F');
	        echo "https://blissful-sutherland.89-44-32-145.plesk.page/temperature_truck/".$filename;
        } else {
           echo -1;
        }
    } else {
       echo -2;
    }
	
?>