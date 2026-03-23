<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traldis Porta</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/functions.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.28.0/dist/date-fns.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.min.js"></script>
	<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
</head>
 


<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="assets/img/logo.png" alt="Logo" width="150" height="50">
            </div>
        </header>

        

            <main>
                <div class="form-container">
                    <form id="searchForm" onsubmit="submitForm(event)">
                        <table class="form-table">
                            <tr>
                                <td colspan="2"><label for="searchBy">Buscar por:</label></td>
                                <td><label for="expeditionCode">Núm. Expedición</label></td>
                                <td><label for="centerCode">Centro</label></td>
                                <td><label for="truckPlate">Matrícula camión</label></td>
                                <td><label for="startDate">Fecha inicial</label></td>
                                <td><label for="endDate">Fecha final</label></td>
                                <td><button type="submit" id="submitButton" name="submitButton"
                                        class="btn btn-primary">Buscar</button></td>
                            </tr>
                            <tr>
                                <td><input type="radio" id="expeditionRadio" name="searchType" value="expedition" checked>
                                    <label for="expeditionRadio">Exp.</label>
                                </td>
                                <td>
                                    <input type="radio" id="truckRadio" name="searchType" value="truck" >
                                    <label for="truckRadio">Camión</label>
                                </td>
                                <td><input type="text" id="expeditionCode" name="expeditionCode" value="<?php echo $_REQUEST['exp']; ?>" required ></td>
                                <td>
								<!--<input type="text" id="centerCode" name="centerCode" required disabled>-->
									<select name="centerCode" id="centerCode">
									  <option value="8" selected>8</option>
									  <option value="25">25</option>
									  <option value="80">80</option>
									</select>								
								</td>
                                <td><input type="text" id="truckPlate" name="truckPlate" required></td>
                                <td><input type="datetime-local" id="startDate" name="startDate" required></td>
                                <td><input type="datetime-local" id="endDate" name="endDate" required></td>
                                <td></td>
                            </tr>
                        </table>

                    </form>
                    <div id="loading" class="loading"></div>
					<canvas id="temperatureChart"></canvas>
                    <div id="result" class="result"></div>
                </div>
                <div class="overlay" id="overlay">
                    <div class="loading-text" id="loadingText">Cargando...</div>
                </div>
            </main>

        <footer class="footer">
            <p>Traldis Porta
                <?php echo date('Y'); ?>
            </p>
        </footer>

    </div>
<script>

$(document).ready(function () {
	$('#expeditionRadio').click();
	$('#submitButton').click();
});



</script>
</body>

</html>