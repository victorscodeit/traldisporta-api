//Funcio que gestionara els camps del formulari en cas qeu s'hagi marcat el radiobutton truck
function truckRadioChecked() {
  //Desactivem el camp expeditionCode, centerCode i companyCode
  $('#expeditionCode').prop('disabled', true);
  $('#centerCode').prop('disabled', true);
  //Activem els seguents camps
  $('#truckPlate').prop('disabled', false);
  $('#startDate').prop('disabled', false);
  $('#endDate').prop('disabled', false);
}

//Funcio que gestionara els camps del formulari en cas qeu s'hagi marcat el radiobutton expedition
function expeditionRadioChecked() {
  //Activem el camp expeditionCode, centerCode i companyCode
  $('#expeditionCode').prop('disabled', false);
  $('#centerCode').prop('disabled', false);
  //Desactivem els seguents camps
  $('#truckPlate').prop('disabled', true);
  $('#startDate').prop('disabled', true);
  $('#endDate').prop('disabled', true);
}

$(document).ready(function () {
  //En cas que el radiobutton truckRadio canvii
  $('#truckRadio').change(function () {
    truckRadioChecked();
  });

  //En cas que el radiobutton expeditionRadio canvii
  $('#expeditionRadio').change(function () {
    expeditionRadioChecked();
  });

  //Establim els valors per defecte dels radiobutton
  $('#truckRadio').prop('checked', true);
  $('#expeditionCode').prop('disabled', true);
  $('#centerCode').prop('disabled', true);
});

function widget_generar_imagen(){
       html2canvas(document.getElementsByClassName("form-container")[0],{
          scale:1}
).then(function(canvas) {
       var imagenBase64 = canvas.toDataURL("image/png");
      /* jQuery("#page_preloader").show();
       jQuery("#page_preloader").css('opacity','0.8');   */   
       jQuery.ajax({
            url: 'subirpdf.php',
            type: 'POST',
            data: {
                action: 'subir_imagen_canvas', // Nombre de la acción del controlador AJAX
                imagen: imagenBase64, // Datos a enviar
				exp: $('#expeditionCode').val(), // Datos a enviar
				ctr: $('#centerCode').val() // Datos a enviar
            },
            success: function(response) {
              imagen_generada=response;
			  $('#pdf_button').html('<a target="_blank" href="'+imagen_generada+'" ><button>Descargar PDF</button></a>');

            //  console.log(response);
              
            },
            error: function(error) {
                console.error('Error al subir la imagen:', error);
            }
        });
       });
}
//Funcio que oculta la pantalla de Carregant
function hideLoading(radiobuttonChecked, loadingInterval) {

  $('#overlay').fadeOut(200, function () {
    clearInterval(loadingInterval); //Para l'animació dels punts suspensius
    //Desbloqueja tots els elements del formulari
    $('#searchForm input, #searchForm button').prop('disabled', false);

    //Si hem quidat la funcio pel block truck
    if (radiobuttonChecked == 'truck') {
      truckRadioChecked();
    }
    //Si hem quidat la funcio pel block expedition
    else {
      expeditionRadioChecked();
    }
  });
}


//Aquesta funció ens permet processar la resposta.
//Creara un array amb la informacio de data i temperatures
/*function parseResponseInfo(truckId, response) {
  var data = [];

  //Comprovem si aquest ID esta a l'array de resposta
  if ((truckId in response)) {
    var base = response[truckId];

    for (let i of base) {
      for (let j of i) {
        if ("details" in j) {
          let details = j["details"];
          for (let detail of details) {
            let y = "";
            let x = "";
            if (detail["Ubicación"] !== undefined) { 
              if (detail["Ubicación"]["y"] !== undefined) {
                y = detail["Ubicación"]["y"]
              }
              if (detail["Ubicación"]["x"] !== undefined) {
                x = detail["Ubicación"]["x"]
              }              

            }else{ x ="";y ="";}
            if (detail["Tiempo"] !== undefined) {
				
              if (detail["Tiempo"]["t"] !== undefined) {
                let tiempo = detail["Tiempo"]["t"];
                if (tiempo != null) {
                  let caja1 = '-';
                  let caja2 = '-';
                  if (detail["ºC CAIXA 1"] !== undefined || detail["ºC CARGA 1"] !== undefined) {
                    if ( detail["ºC CAIXA 1"] !== undefined){
                      caja1 = detail["ºC CAIXA 1"];
                    }
                    if (detail["ºC CARGA 1"] !== undefined){
                      caja1 = detail["ºC CARGA 1"];
                    }
                  }
                  if (detail["ºC CAIXA 2"] !== undefined || detail["ºC CARGA 2"] !== undefined) {
                    if ( detail["ºC CAIXA 2"] !== undefined){
                      caja2 = detail["ºC CAIXA 2"];
                    }
                    if (detail["ºC CARGA 2"] !== undefined){
                      caja2 = detail["ºC CARGA 2"];
                    }
                  }

                  //Afegim les dades desitjades en un array
                  data.push({
                    "tiempo": tiempo,
                    "caja1": caja1,
                    "caja2": caja2,
                    "ubicacion_y": y,
                    "ubicacion_x": x
                  });
                }
              }else{
				  //Modificació trobada a la resposta de movertis sobre la expedició 1074226
				  let tiempo = detail["Tiempo"];
                if (tiempo != null) {
                  let caja1 = '-';
                  let caja2 = '-';
                  if (detail["ºC CAIXA 1"] !== undefined || detail["ºC CARGA 1"] !== undefined) {
                    if ( detail["ºC CAIXA 1"] !== undefined){
                      caja1 = detail["ºC CAIXA 1"];
                    }
                    if (detail["ºC CARGA 1"] !== undefined){
                      caja1 = detail["ºC CARGA 1"];
                    }
                  }
                  if (detail["ºC CAIXA 2"] !== undefined || detail["ºC CARGA 2"] !== undefined) {
                    if ( detail["ºC CAIXA 2"] !== undefined){
                      caja2 = detail["ºC CAIXA 2"];
                    }
                    if (detail["ºC CARGA 2"] !== undefined){
                      caja2 = detail["ºC CARGA 2"];
                    }
                  }

                  //Afegim les dades desitjades en un array
                   data.push({
                    "tiempo": tiempo,
                    "caja1": caja1,
                    "caja2": caja2,
                    "ubicacion_y": y,
                    "ubicacion_x": x
                  });		
                }
                 		  
			  }
            }
          }
        }
      }
    }
  }

  return data;
}*/

function parseResponseInfo(truckId, response, fase, sonda) {
  var data = [];

  //Comprovem si aquest ID esta a l'array de resposta
  if ((truckId in response)) {
    var base = response[truckId];

    for (let i of base) {
      for (let j of i) {
        if ("details" in j) {
          let details = j["details"];
          for (let detail of details) {
            let y = "";
            let x = "";
            if (detail["Ubicación"] !== undefined) { 
              if (detail["Ubicación"]["y"] !== undefined) {
                y = detail["Ubicación"]["y"]
              }
              if (detail["Ubicación"]["x"] !== undefined) {
                x = detail["Ubicación"]["x"]
              }              

            }else{ x ="";y ="";}
            if (detail["Tiempo"] !== undefined) {
				
              if (detail["Tiempo"]["t"] !== undefined) {
                let tiempo = detail["Tiempo"]["t"];
                if (tiempo != null) {
                  let caja1 = '-';
                  let caja2 = '-';
                  if (detail["ºC CAIXA 1"] !== undefined || detail["ºC CARGA 1"] !== undefined) {
                    if ( detail["ºC CAIXA 1"] !== undefined){
                      caja1 = detail["ºC CAIXA 1"];
                    }
                    if (detail["ºC CARGA 1"] !== undefined){
                      caja1 = detail["ºC CARGA 1"];
                    }
                  }
                  if (detail["ºC CAIXA 2"] !== undefined || detail["ºC CARGA 2"] !== undefined) {
                    if ( detail["ºC CAIXA 2"] !== undefined){
                      caja2 = detail["ºC CAIXA 2"];
                    }
                    if (detail["ºC CARGA 2"] !== undefined){
                      caja2 = detail["ºC CARGA 2"];
                    }
                  }

                  //Afegim les dades desitjades en un array
                  data.push({
					"sonda":sonda,  
					"fase":fase,
                    "tiempo": tiempo,
                    "caja1": caja1,
                    "caja2": caja2,
                    "ubicacion_y": y,
                    "ubicacion_x": x
                  });
                }
              }else{
				  //Modificació trobada a la resposta de movertis sobre la expedició 1074226
				  let tiempo = detail["Tiempo"];
                if (tiempo != null) {
                  let caja1 = '-';
                  let caja2 = '-';
                  if (detail["ºC CAIXA 1"] !== undefined || detail["ºC CARGA 1"] !== undefined) {
                    if ( detail["ºC CAIXA 1"] !== undefined){
                      caja1 = detail["ºC CAIXA 1"];
                    }
                    if (detail["ºC CARGA 1"] !== undefined){
                      caja1 = detail["ºC CARGA 1"];
                    }
                  }
                  if (detail["ºC CAIXA 2"] !== undefined || detail["ºC CARGA 2"] !== undefined) {
                    if ( detail["ºC CAIXA 2"] !== undefined){
                      caja2 = detail["ºC CAIXA 2"];
                    }
                    if (detail["ºC CARGA 2"] !== undefined){
                      caja2 = detail["ºC CARGA 2"];
                    }
                  }

                  //Afegim les dades desitjades en un array
                   data.push({
					"sonda":sonda,  
					"fase":fase,   
                    "tiempo": tiempo,
                    "caja1": caja1,
                    "caja2": caja2,
                    "ubicacion_y": y,
                    "ubicacion_x": x
                  });		
                }
                 		  
			  }
            }
          }
        }
      }
    }
  }

  return data;
}

function renderTableResults(data) {
  //Si tenim dades a l'array, generarem una taula mostrant els resultats
  if (data.length > 0) {
/*    let tabla = `
  <div class="table-responsive">
  <table class="table">
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
    <tbody>`;*/
    let tabla="";
    data.forEach(function (item) {
      let urlmaps = "https://www.google.com/maps?q="+item.ubicacion_y+","+item.ubicacion_x;

      tabla += `
    <tr>
      <td>${item.tiempo}</td>
	  <td>${item.fase}</td>
      <td>${item.sonda}</td>
      <td>${item.caja1}</td>
      <td>${item.caja2}</td>
      <td><a href="`+urlmaps+`" target="_blank">Ver Maps</a></td>
    </tr>`;
    });

    /*tabla += '</tbody></table></div>';*/

    return tabla;
  }
  else {
    //return "<p>Sin datos...</p>";
	return "";
  }
}

//Convertim un string de datetime a timestamp
function datetimeStrToTimestamp(strDate) {
  //Posem + Z perque ho agafi com si fos una data UTC, ja que sino, quan genera el timestamp varia
  return (new Date(strDate + "Z").getTime()) ;
  //return (new Date(strDate + "Z").getTime()) / 1000;
}

//A partir del llistat de camions, busquem l'id del camió que correspon a la matricula subministrada
function getTruckIdFromResponse(truckPlate, data) {
  for (let truck of data) {
	var matriculaMv=truck["name"];
	/*matriculaMv.toUpperCase();
	matriculaMv.replace(/[^a-zA-Z0-9]/g, '');
	var MatriculaMt=truckPlate;
	MatriculaMt.toUpperCase();
	MatriculaMt.replace(/[^a-zA-Z0-9]/g, '');*/
	/*if(truckPlate=='8953JPW') {
		//console.log(truckPlate+" = "+truck["name"]);
	truckPlate='8953-JPW'
	//console.log("TRUCK "+truckPlate+" = "+truck["name"]);
	}*/
    if (truck["name"] == truckPlate) {
      return truck["idVehicle"];
    }
  }

  return false;
}
//Generem el grafic
function generarGrafic(data){
    
	const ctx = document.getElementById('temperatureChart').getContext('2d');

    const dates = data.map(d => d.tiempo);
    const temperatures = data.map(d => parseFloat(d.caja1));
	let datasets = [];
    let currentPhase = null;
    let currentData = [];
    let colors = ['rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(75, 192, 192)', 'rgb(153, 102, 255)', 'rgb(255, 159, 64)'];
    let colorIndex = 0;
	
	data.forEach((point, index) => {
        if (point.fase !== currentPhase || index === data.length - 1) {
            if (currentData.length > 0) {
                datasets.push({
                    label: currentPhase,
                    data: currentData,
                    borderColor: colors[colorIndex % colors.length],
                    borderWidth: 2,
                    fill: false
                });
                colorIndex++;
            }
            currentPhase = point.fase;
            currentData = [];
        }
        currentData.push({
            x: point.tiempo,
            y: parseFloat(point.caja1)
        });
    });
	//console.log(datasets);

    // Asignar un color único basado en la fase del primer elemento
    const borderColor = (data[0].fase === "BCN SALA FRED") ? 'rgb(255, 99, 132)' : 'rgb(54, 162, 235)'; // Rojo o Azul

    // Destruir el gráfico anterior si existe
    if (window.myChart instanceof Chart) {
        window.myChart.destroy();
    }
	const genericOptions = {
	  fill: false,
	  interaction: {
		intersect: false
	  },
	  radius: 0,
	  animation: {
         onComplete: function() {
            /*isChartRendered = true*/
			widget_generar_imagen();
         }
      },
	 
	};

    // Crear el gráfico
    window.myChart = new Chart(ctx, {
        type: 'line',
	  data: {
		labels: dates,
				datasets: datasets
	  },
	  options: genericOptions
		});
}
// Función para formatear una fecha en formato español (fecha y hora)

function formatearFechaEspanol(fecha) {
  // Obtener opciones de formato de fecha
  const opcionesFormato = {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: 'numeric',
    second: 'numeric'
  };

  // Formatear la fecha en español
  const fechaFormateada = new Intl.DateTimeFormat('es-ES', opcionesFormato).format(fecha);

  // Devolver la fecha formateada
  return fechaFormateada;
}

//Aquesta funcio permet mostrar el resultat a la capa corresponent
function mostrarResultado(responseData) {
  $('#result').html(responseData);
}

function showOverlayLoading() {
  $('#overlay').fadeIn(200);
}

function loadingDots() {
  //Fem que els punts suspensius de Carregant... siguin dinàmics
  var dots = 0;
  var loadingInterval = setInterval(function () {
    dots = (dots + 1) % 4;
    var loadingText = 'Carregant' + '.'.repeat(dots);
    $('#loadingText').text(loadingText);
  }, 500);

  return loadingInterval;
}

function hideOverlayLoading() {
  $('#overlay').fadeOut(200);
}

function dateFusion(date, hour) {
  let dateClean = date.split(" ")[0];
  let hourclean = hour.split(" ")[1];

  return dateClean + " " + hourclean;
}

//Funcio que es crida en el moment de fer submit del formulari
function submitForm(event) {
  event.preventDefault();

  //Bloquegem el formulari sencer
  $('#searchForm input, #searchForm button').prop('disabled', true);

  //MOVERTIS DATA
  var urlCheckreport = "https://devapi.hellomovertis.com/report/checkreport";
  var urlShowvehicles = "https://devapi.hellomovertis.com/vehicle/showvehicles";
  var apiKey = "9a0ab49ab29426b087f8e68638760a13AB1DCFAB8F41E44A2C456FA153B313766AD32E7E";

  //TRALDISPORTA DATA
  //var urlPortaTruckInfo = "http://localhost:8080/oms/restapi/v1/getTrucksExpedition";
  var urlPortaTruckInfo = "https://blissful-sutherland.89-44-32-145.plesk.page/api/v1/getTrucksExpedition";
  var portaApiKey = "VBGxoAvLEeTDgevPZMmCgrg4g0iT1gmI";

  var searchType = $('input[name="searchType"]:checked').val();
  var truckId = false;

  //Si hem marcat buscar per camio
  if (searchType == "truck") {
    showOverlayLoading();
    var loadingInterval = loadingDots();

    var truckPlate = $('#truckPlate').val().toUpperCase();
    var startDate = $('#startDate').val();
    var sDate = datetimeStrToTimestamp(startDate);
    var endDate = $('#endDate').val();
    var eDate = datetimeStrToTimestamp(endDate);

    var settings = {
      "url": urlShowvehicles,
      "method": "POST",
      "timeout": 0,
      "headers": {
        "authorization": apiKey,
        "Content-Type": "application/json"
      },
      "data": JSON.stringify({
        "id": [],
        "flags": {
          "basicData": true
        }
      }),
    };

    $.ajax(settings)
      .done(function (response) {
        truckId = getTruckIdFromResponse(truckPlate, response);

        //En cas que no hagi trobat aquesta matricula dins de la resposta
        if (truckId == false) {
          //Mostrem missatge de matricula no trobada
          mostrarResultado("Matricula " + truckPlate + " no trobada");

          //Treurem la pantalla de carregant
          hideLoading('truck', loadingInterval);
        }
        else {
          //En cas que si que haguem trobat la matricula i ens hagi tornat l'ID del camio
          var datos = {
            "idVehicle": [
              truckId
            ],
            "idReport": [
              16
            ],
            "initial_date": sDate,
            "end_date": eDate
          };

          var settings = {
            "url": urlCheckreport,
            "method": "POST",
            "timeout": 0,
            "headers": {
              "authorization": apiKey,
              "Content-Type": "application/json"
            },
            "data": JSON.stringify(datos),
          };

          $.ajax(settings)
            .done(function (response) {
				var datosGrafico=[];
              //En cas que hagi anat bé la peticio
              //Processem la resposta
              var data = parseResponseInfo(truckId, response, "Almacén", truckId);
			 // console.log(data);
			  for (let dato of data) {
				  console.log(dato);
						  let d=dato;
						  d.tiempo=dato.tiempo;
						  if(dato.caja1=="-"){d.caja1=dato.caja2;}
						  datosGrafico.push(d);
			  }
			  console.log(datosGrafico);
			  html=renderTableResults(data);
			  let tabla = `
									  <div class="table-responsive">
									  <table class="table">
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
										<tbody>`;
			  tabla+=html;
			  tabla += '</tbody></table></div>';
              mostrarResultado(tabla);
			  generarGrafic(datosGrafico);
              //Mostrem el resultat per pantalla
              //mostrarResultado(renderTableResults(data));

              //Treurem la pantalla de carregant
              hideLoading('truck', loadingInterval);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
              //En cas que la petició no hagi anat bé, mostrarem missatge d'error
              mostrarResultado("Hi ha hagut un error a la petició en la cerca del report de temperatures...");
              console.error('Error:', textStatus, errorThrown);

              //Treurem la pantalla de carregant
              hideLoading('truck', loadingInterval);
            });

        }
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        mostrarResultado("Error en la petició de la cerca del ID del camió...");
        console.error('Error:', textStatus, errorThrown);

        //Treurem la pantalla de carregant
        hideLoading('truck', loadingInterval);
      });
  }
  else {
    var expeditionCode = $('#expeditionCode').val();
    var centerCode = $('#centerCode').val();
    //var token = "1234";

    showOverlayLoading();
    var loadingInterval = loadingDots();

    //Preparem la crida al webservice de Traldisporta per tal de recuperar els camions que intervenen en aquest número d'expedició
    var settings = {
      "url": urlPortaTruckInfo,
      //"async": false,
      "method": "POST",
      "timeout": 0,
      "headers": {
        //"Authorization": portaApiKey,
        "ApiKey": portaApiKey,
        "Content-Type": "application/json"
      },
      "data": JSON.stringify({
        "expeditionCode": expeditionCode,
        "centerCode": centerCode
        //"token": token
      }),
    };

    var truckPlates = [];
    var truckIds = [];
    var html = "";
	var datosGrafico=[];

    $.ajax(settings)
      .done(function (response) {
        let dataError = response['error'];

        if (dataError == false) {
          let dataResponse = response['data'];
          let recogidas = dataResponse['recogidas'];
          let almacenes = dataResponse['almacenes'];
          let repartos = dataResponse['repartos'];
		  let salas = dataResponse['salas'];
			
          for (let recogida of recogidas) {
            if (recogida['matriculaVehiculo'] != "") {
              let start = dateFusion(recogida['fechaSalida'], recogida['horaSalida']);
              let finish = dateFusion(recogida['fechaLlegada'], recogida['horaLlegada']);

              v = {
                "truck": recogida['matriculaVehiculo'].toUpperCase(),
                "start": start,
                "finish": finish,
                "type": "collect"
              };
              truckPlates.push(v);
            }
          }
		    
            if(salas.length>0 && typeof salas[0].inicial !== 'undefined' && salas[0].inicial !== null){
              start = salas[0].inicial.horaInici;
              finish = salas[0].inicial.horaFi;

              v = {
                "truck": salas[0].inicial.idVehicle,
                "start": start,
                "finish": finish,
                "type": "sala",
				"name": salas[0].inicial.nomSala
              };
              truckPlates.push(v);
			}
            
          
          var contador=0;
          for (let almacen of almacenes) {
			 
		    if(contador==1 && typeof salas[1].inicial !== 'undefined' && salas[1].inicial !== null){
              start = salas[contador].inicial.horaInici;
              finish = salas[contador].inicial.horaFi;

              v = {
                "truck": salas[contador].inicial.idVehicle,
                "start": start,
                "finish": finish,
                "type": "sala",
				"name": salas[contador].inicial.nomSala
              };
              truckPlates.push(v);
			}
			  
            if (almacen['matriculaCamion'] != "") {
              let start = dateFusion(almacen['fechaCarga'], almacen['horaCarga']);
              let finish = dateFusion(almacen['fechaLlegadaCarga'], almacen['horaLlegadaCarga']);
			  var matricula='';
			  if(almacen['matriculaRemolque'] != ""){
			  matricula= almacen['matriculaRemolque'];
			  }else{
			  matricula= almacen['matriculaCamion'];
			  }
              v = {
                "truck": matricula.toUpperCase(),
                "start": start,
                "finish": finish,
                "type": "warehouse"
              };
              truckPlates.push(v);
            }
			contador++;
          }
		     var numsala=0;
             if(salas.length>0){
			  if(typeof salas[1].reparto !== 'undefined' && salas[1].reparto !== null){
				  numsala=1;
			  }else{
			      if(typeof salas[2].reparto !== 'undefined' && salas[2].reparto !== null){
				  numsala=2;
				  }else{
					  numsala=0;
				  }
				  
			  }
              start = salas[numsala].reparto.horaInici;
              finish =  salas[numsala].reparto.horaFi;

              v = {
                "truck":  salas[numsala].reparto.idVehicle,
                "start": start,
                "finish": finish,
                "type": "sala",
				"name": salas[numsala].reparto.nomSala
              };
              truckPlates.push(v);
			 }
            
          
          for (let reparto of repartos) {
            if (reparto['matriculaVehiculo'] != "") {
              let start = dateFusion(reparto['fechaSalida'], reparto['horaSalida']);
              let finish = dateFusion(reparto['fechaEntrega'], reparto['horaEntrega']);
              v = {
                "truck": reparto['matriculaVehiculo'].toUpperCase(),
                "start": start,
                "finish": finish,
                "type": "delivery"
              };
              truckPlates.push(v);
            }
          }

          //Preparem la crida a Movertis per demanar quin és l'ID d'aquest camió
          var settings = {
            "url": urlShowvehicles,
            //"async": false,
            "method": "POST",
            "timeout": 0,
            "headers": {
              "authorization": apiKey,
              "Content-Type": "application/json"
            },
            "data": JSON.stringify({
              "id": [],
              "flags": {
                "basicData": true
              }
            }),
          };

          $.ajax(settings)
            .done(function (response) {

              for (let truck of truckPlates) {
				  if(truck['type']!='sala'){
                //Obtenim l'ID del camió
                truckIds.push(getTruckIdFromResponse(truck['truck'], response));
				  }else{
				   truckIds.push(truck['truck']);
				  }
              }
				console.log(truckPlates);
				console.log(truckIds);			  
              i = 0;
              for (let truckId of truckIds) {
                let label = "";
                switch (truckPlates[i]['type']) {
                  case "collect":
                    label = "Recogida";
                    break;
                  case "warehouse":
                    label = "Almacén";
                    break; 
                  case "delivery":
                    label = "Reparto";
                    break; 
                  case "sala":
                    label = truckPlates[i]['name']
                    break;					
                }


                //html += "<p><strong>" + truckPlates[i]['truck'] + ": " + truckPlates[i]['start'] + " - " + truckPlates[i]['finish'] + " " + label + "</strong></p>";

                if (truckId == false) {
                  html += "<p>Matricula no encontrada en Movertis...</p>";
                }
                else {
                  var datos = {
                    "idVehicle": [
                      truckId
                    ],
                    "idReport": [
                      16 //REPORT DE TEMPERATURES
                    ],
                    "initial_date": datetimeStrToTimestamp(truckPlates[i]['start']),
                    "end_date": datetimeStrToTimestamp(truckPlates[i]['finish'])
                  };

                  var settings = {
                    "url": urlCheckreport,
                    "async": false,
                    "method": "POST",
                    "timeout": 0,
                    "headers": {
                      "authorization": apiKey,
                      "Content-Type": "application/json"
                    },
                    "data": JSON.stringify(datos),
                  };

                  $.ajax(settings)
                    .done(function (response) {
						
                      //Processem la resposta per mostrar una taula amb les dades
					   if(label=='Almacén' || label=='Reparto' || label=='Recogida'){label=truckPlates[i]['truck'];}
                      var data = parseResponseInfo(truckId, response, label, truckId);
					  //console.log("DATA");
					  

                      //Preparem dades pel grafic
					  for (let dato of data) {
						  let d=dato;
						  d.tiempo=dato.tiempo;
						  if(dato.caja1=="-")d.caja1=dato.caja2;
						  datosGrafico.push(d);
					  }
					   //Ajuntem la taula al html de resposta						
                      html += renderTableResults(data);
					  
					  

                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                      //En cas que la petició no hagi anat bé, mostrarem missatge d'error
                      mostrarResultado("Hi ha hagut un error a la petició en la cerca del report de temperatures...");
                      console.error('Error:', textStatus, errorThrown);
                    });
                }


                i += 1;
              }

              hideLoading('exp', loadingInterval);
			   let tabla = `
									  <div class="table-responsive">
									  <table class="table">
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
										<tbody>`;
			  tabla+=html;
			  tabla += '</tbody></table></div>';
              mostrarResultado(tabla);
			  //console.log(datosGrafico);
			  generarGrafic(datosGrafico);
			  //button_pdf(expeditionCode, centerCode);

            })
            .fail(function (jqXHR, textStatus, errorThrown) {
              mostrarResultado("Error en la petició de la cerca del ID del camió...");
              console.error('Error:', textStatus, errorThrown);
            });
        }
        else {
          hideLoading('exp', loadingInterval);
          mostrarResultado(response['message']);
        }

      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error:', textStatus, errorThrown);
      });
  }

}
function button_pdf(expeditionCode, centerCode){
	
	$('#pdf_button').html('<a href="path/to/your/file.pdf" download="NombreDelArchivoDescargado.pdf"><button>Descargar PDF</button></a>');

}

function convertToDate(dateString) {
    // Dividir la fecha y la hora
    let parts = dateString.split(' ');
    let dateParts = parts[0].split('.');
    let time = parts[1];

    // Reorganizar a formato yyyy-mm-dd
    let formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}T${time}`;

    // Crear el objeto Date
    return new Date(formattedDate);
}

/*function makeAjaxCall1() {
  var urlCheckreport = "https://devapi.hellomovertis.com/report/checkreport";
  var apiKey = "9a0ab49ab29426b087f8e68638760a13AB1DCFAB8F41E44A2C456FA153B313766AD32E7E";

  let truckId = 27163753;
  // Retorna una nueva promesa para la llamada AJAX
  return new Promise(function (resolve, reject) {
    var datos = {
      "idVehicle": [
        truckId
      ],
      "idReport": [
        16 //REPORT DE TEMPERATURES
      ],
      "initial_date": 1702195988,
      "end_date": 1702282388
    };

    var settings = {
      "url": urlCheckreport,
      "method": "POST",
      "timeout": 0,
      "headers": {
        "authorization": apiKey,
        "Content-Type": "application/json"
      },
      "data": JSON.stringify(datos),
    };

    $.ajax(settings)
      .done(function (response) {
        resolve(response);
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        reject(error);
      });
  });
}

function makeAjaxCall2() {
  var urlCheckreport = "https://devapi.hellomovertis.com/report/checkreport";
  var apiKey = "9a0ab49ab29426b087f8e68638760a13AB1DCFAB8F41E44A2C456FA153B313766AD32E7E";
  let truckId = 27163753;
  // Retorna una nueva promesa para la llamada AJAX
  return new Promise(function (resolve, reject) {
    var datos = {
      "idVehicle": [
        truckId
      ],
      "idReport": [
        16 //REPORT DE TEMPERATURES
      ],
      "initial_date": 1702195988,
      "end_date": 1702282388
    };

    var settings = {
      "url": urlCheckreport,
      "method": "POST",
      "timeout": 0,
      "headers": {
        "authorization": apiKey,
        "Content-Type": "application/json"
      },
      "data": JSON.stringify(datos),
    };

    $.ajax(settings)
      .done(function (response) {
        resolve(response);

      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        reject(error);
      });
  });
}*/




