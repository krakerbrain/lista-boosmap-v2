<?php

// Función para obtener la ruta correcta de vendor/autoload.php
function getAutoloadPath()
{
    if (file_exists('./vendor/autoload.php')) {
        // En servidor (Hostinger)
        return  './vendor/autoload.php';
    } elseif (file_exists('../vendor/autoload.php')) {
        // En entorno local
        return '../vendor/autoload.php';
    } else {
        // Manejo de error si el archivo no se encuentra en ninguna ubicación
        die('No se pudo encontrar autoload.php');
    }
}

// Incluir el archivo autoload.php usando la función
require getAutoloadPath();
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = require('config.php');
$client = new Google_Client();
$client->setAuthConfig($_ENV['CREDENTIALS']);
$client->addScope(Google_Service_Sheets::SPREADSHEETS);

$service = new Google_Service_Sheets($client);
$spreadsheetId = $_ENV['SPREADSHEETID'];

date_default_timezone_set('America/Santiago');
$today = date("d-m");
$sheet = $today;
// $sheet = '12-08';
$range = $sheet . '!A:B';

try {
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);

    // Solo para Guardar los datos en un archivo JSON
    // $values = $response->getValues();
    // file_put_contents('21-08.json', json_encode($values));

    if ($config['dataSource'] === 'local') {
        $jsonData = file_get_contents('21-08.json');
        $valuesFromJson = json_decode($jsonData, true);
        $values = limpiarYAjustarValores($valuesFromJson);
    } elseif ($config['dataSource'] === 'api') {
        $resp = $response->getValues();
        $values = limpiarYAjustarValores($resp);
    }



    $chofer = isset($_GET['choferInicialHidden']) ? $_GET['choferInicialHidden'] : '';
    $usuario = isset($_GET['nombreUsuario']) ? $_GET['nombreUsuario'] : '';
    $valorCookie = $_COOKIE['choferInicial'];
    $filtro = "";
    $getDataInicial = "";
    if (isset($_GET['filtrar']) && is_numeric($_GET['filtrar'])) {
        $filtro = intval($_GET['filtrar']); // Convertir a entero

        if ($filtro == -2) {
            $filtro = 20;
            $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, 'last20', '');
        } elseif ($filtro < 0) {
            $filtro = count($values); // Valor negativo distinto de -2
            $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, '');
        } else {
            $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, '');
        }
    } else {
        $filtro = 10; // Si no se proporciona o no es un número válido
        $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, '');
    }

    $diferenciaEntreDosChoferes = calcularDiferenciaUsuarios($values, $chofer, $usuario);

    if (isset($diferenciaEntreDosChoferes['diferencia'])) {
        $diferencia = $diferenciaEntreDosChoferes['diferencia'];
        $getDataInicial = obtenerRegistrosSegunChofer($values, $diferencia, $valorCookie, 'diferencia');
    }

    $usuariosCercanos = $getDataInicial;
    $encuentraChofer = encuentraChofer($values, $valorCookie);
    $listaChoferes = obtenerNombresSinRepetir($values);

    if (empty($values)) {
        echo "No data found.";
    }
} catch (Google\Service\Exception $e) {
    error_log("Error en la llamada a la API: " . $e->getMessage(), 0);
    echo "Lo sentimos, ha ocurrido un error. Por favor, inténtalo de nuevo más tarde.";
}

function limpiarYAjustarValores($response)
{
    $values = [];
    $currentEmptyCells = 0; // Contador de celdas vacías al inicio
    foreach ($response as $row) {
        if (empty($row[0])) {
            $currentEmptyCells++;
        } else {
            for ($i = 0; $i < $currentEmptyCells; $i++) {
                $values[] = array(
                    '', // Índice vacío
                    '', // Nombre de usuario vacío
                );
            }
            $currentEmptyCells = 0;

            if (isset($row[1]) && trim($row[1]) !== '') {
                $values[] = array(
                    isset($row[0]) ? trim($row[0]) : '',
                    trim($row[1]), // Limpiar el nombre de usuario
                );
            }
        }
    }

    return $values;
}

function encuentraChofer($values, $chofer)
{
    $lastTrueIndex = -1;

    for ($i = count($values) - 1; $i >= 0; $i--) {
        if (isset($values[$i][1]) && $values[$i][1] === $chofer) {
            $lastTrueIndex = $i;
            break;
        }
    }

    return $lastTrueIndex;
}

function obtenerRegistrosSegunChofer($values, $filtro = 10, $chofer, $diferencia)
{
    $lastTrueIndex = $chofer == 'last20' ? count($values) :   encuentraChofer($values, $chofer);
    if ($lastTrueIndex == -1) {
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if ((isset($values[$i][0]) && $values[$i][0] === "TRUE")) {
                $lastTrueIndex = $i + 1;
                break;
            }
        }

        return upAndDown($values, $filtro,  $lastTrueIndex, $diferencia);
    } else {

        if (isset($values[$lastTrueIndex][0]) && $values[$lastTrueIndex][0] === "TRUE") {
            for ($i = count($values) - 1; $i >= 0; $i--) {
                if ((isset($values[$i][0]) && $values[$i][0] === "TRUE")) {
                    $lastTrueIndex = $i + 1;
                    break;
                }
            }
            return upAndDown($values, $filtro, $lastTrueIndex, $diferencia);
        } else {
            return upAndDown($values, $filtro, $lastTrueIndex, $diferencia);
        }
    }
}

function upAndDown($values, $filtro, $lastTrueIndex, $diferencia)
{
    $valores_total = [];

    for ($i = max(0, $lastTrueIndex - $filtro); $i <= $lastTrueIndex - 1; $i++) {
        if (isset($values[$i][0]) && isset($values[$i][1])) {
            $valores_total[] = [
                'indice' => $i + 2,
                'dato1' => $values[$i][0],
                'dato2' => $values[$i][1],
            ];
        }
    }
    if ($diferencia == "") {

        for ($i = $lastTrueIndex; $i < min(count($values), $lastTrueIndex + $filtro); $i++) {
            if (isset($values[$i][0]) && isset($values[$i][1])) {
                $valores_total[] = [
                    'indice' => $i + 2,
                    'dato1' => $values[$i][0],
                    'dato2' => $values[$i][1],
                ];
            }
        }
    } else {
        for ($i = $lastTrueIndex; $i < min(count($values), $lastTrueIndex + 5); $i++) {
            if (isset($values[$i][0]) && isset($values[$i][1])) {
                $valores_total[] = [
                    'indice' => $i + 2,
                    'dato1' => $values[$i][0],
                    'dato2' => $values[$i][1],
                ];
            }
        }
    }
    return $valores_total;
}
function obtenerNombresSinRepetir($values)
{
    $nombresSinRepetir = array();

    foreach ($values as $row) {
        if (isset($row[1])) {
            $nombre = trim($row[1]);
            if (!empty($nombre) && !in_array($nombre, $nombresSinRepetir)) {
                $nombresSinRepetir[] = $nombre;
            }
        }
    }
    return $nombresSinRepetir;
}

function calcularDiferenciaUsuarios($values, $choferInicial, $usuario)
{
    if (empty($choferInicial) || empty($usuario)) {
        return ""; // Si alguno de los valores está vacío, no se realiza el cálculo
    }

    $indiceChoferInicial = -1;
    $indiceUsuario = -1;

    for ($i = count($values) - 1; $i >= 0; $i--) {
        if ($indiceChoferInicial === -1 && isset($values[$i][1]) && $values[$i][1] === $choferInicial) {
            $indiceChoferInicial = $i + 1;
        }

        if ($indiceUsuario === -1 && isset($values[$i][1]) && $values[$i][1] === $usuario) {
            $indiceUsuario = $i + 1;
        }

        if ($indiceChoferInicial !== -1 && $indiceUsuario !== -1) {
            break; // Ambos usuarios encontrados, no es necesario seguir buscando
        }
    }

    if ($indiceChoferInicial !== -1 && $indiceUsuario !== -1) {
        $diferencia = abs($indiceUsuario - $indiceChoferInicial);
        if ($indiceUsuario > $indiceChoferInicial) {
            return;
        } else {
            return array(
                "diferencia" => $diferencia + 1,
                "choferInicial" => array("indice" => $indiceChoferInicial, "nombre" => $choferInicial),
                "usuario" => array("indice" => $indiceUsuario, "nombre" => $usuario)
            );
        }
    } else {
        return array("error" => "No se encontraron ambos usuarios en la lista");
    }
}
