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
    $diferenciaEntreDosChoferes = calcularDiferenciaUsuarios($values, $chofer, $usuario);

    $getDataInicial = [];
    if (isset($_GET['filtrar']) && is_numeric($_GET['filtrar'])) {
        $filtro = intval($_GET['filtrar']); // Convertir a entero

        if ($filtro == -2) {
            // echo "filtro1" . $filtro . "\n";
            $filtro = 20;
            $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, 'last20', '');
        } elseif ($filtro < 0) {
            $filtro = end($values)[0]; // Valor negativo distinto de -2
            // echo "filtro2" . $filtro . "\n";
            $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, '');
        } else {
            // echo "filtro3" . $filtro . "\n";
            $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, '');
        }
    } else {
        // echo "filtro4" . $filtro . "\n";
        $filtro = 10; // Si no se proporciona o no es un número válido
        $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, '');
    }

    if (isset($diferenciaEntreDosChoferes['diferencia'])) {
        $filtro = $diferenciaEntreDosChoferes['filtro'];
        // echo "filtro5:" . $filtro . "\n";
        $getDataInicial = obtenerRegistrosSegunChofer($values, $filtro, $valorCookie, 'diferencia');
    }

    $usuariosCercanos = $getDataInicial;

    $findDriverIndex = findDriverIndex($values, $valorCookie);
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
    $index = null; // Inicializar el índice en null
    $currentIndex = 1; // Inicializar el índice actual en 1

    foreach ($response as $row) {
        if ($index === null && isset($row[1]) && trim($row[1]) !== '') {
            $index = $currentIndex; // Establecer el índice
        }

        if (isset($row[1]) && trim($row[1]) !== '') {
            $values[] = array(
                $index,
                isset($row[0]) ? trim($row[0]) : '',
                trim($row[1]), // Limpiar el nombre de usuario
            );
            $index++; // Incrementar el índice
        }

        $currentIndex++; // Incrementar el índice actual en cada iteración
    }

    return $values;
}

function findDriverIndex(array $data, string $driver): int
{
    $driverIndex = -1;

    foreach ($data as $value) {
        if (isset($value[2]) && $value[2] === $driver) {
            $driverIndex = $value[0];
            break;
        }
    }

    return $driverIndex;
}

function obtenerRegistrosSegunChofer($values, $filtro = 10, $chofer, $diferencia)
{

    if ($chofer != "last20") {

        $driverIndex = findDriverIndex($values, $chofer);
        $lastTrueIndex = "";
        if ($driverIndex == -1) {
            for ($i = end($values)[0] - 1; $i >= 0; $i--) {
                if (isset($values[$i][1]) && $values[$i][1] === "TRUE") {
                    $lastTrueIndex = $values[$i][0] + 1;
                    break;
                }
            }
        } else {
            for ($i = end($values)[0] - 1; $i >= 0; $i--) {
                if (isset($values[$i][1]) && $values[$i][1] === "TRUE") {
                    $lastTrueIndex = $values[$i][0] + 1;
                    break;
                }
            }
        }

        $result = upAndDown($values, $filtro, $lastTrueIndex, $diferencia);
    } else {

        $result = upAndDown($values, $filtro, end($values)[0], $diferencia);
    }

    return $result;
}

function upAndDown($values, $filtro, $lastTrueIndex, $diferencia)
{
    $valoresTotal = [];


    for ($i = max(0, $lastTrueIndex - $filtro) - 1; $i <= $lastTrueIndex - 1; $i++) {
        if (isset($values[$i][0]) && isset($values[$i][1])) {
            $valoresTotal[] = [
                'indice' => $values[$i][0],
                'dato1' => $values[$i][1],
                'dato2' => $values[$i][2],
            ];
        }
    }

    $endIndex = min(end($values)[0], $lastTrueIndex + ($diferencia == "" ? $filtro : 5));
    for ($i = $lastTrueIndex; $i < $endIndex; $i++) {

        if (isset($values[$i][0]) && isset($values[$i][1])) {
            $valoresTotal[] = [
                'indice' => $values[$i][0],
                'dato1' => $values[$i][1],
                'dato2' => $values[$i][2],
            ];
        }
    }
    return $valoresTotal;
}
function obtenerNombresSinRepetir($values)
{
    $nombresSinRepetir = array();

    foreach ($values as $row) {
        if (isset($row[2])) {
            $nombre = trim($row[2]);
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

    for ($i = end($values)[0] - 1; $i >= 0; $i--) {
        if ($indiceChoferInicial === -1 && isset($values[$i][2]) && $values[$i][2] === $choferInicial) {
            $indiceChoferInicial = $values[$i][0];
            $choferInicialValue = $values[$i][1];
        }

        if ($indiceUsuario === -1 && isset($values[$i][2]) && $values[$i][2] === $usuario) {
            $indiceUsuario = $values[$i][0];
        }

        if ($indiceChoferInicial !== -1 && $indiceUsuario !== -1) {
            break; // Ambos usuarios encontrados, no es necesario seguir buscando
        }
    }

    if ($indiceChoferInicial !== -1 && $indiceUsuario !== -1) {
        $diferencia = abs($indiceUsuario - $indiceChoferInicial);

        // Obtener el valor de choferInicial (true o false) desde $values

        $filtro = $choferInicialValue === "TRUE" ? $diferencia + 10  : $diferencia + 5;

        if ($indiceUsuario > $indiceChoferInicial) {
            return;
        } else {
            return array(
                "diferencia" => $diferencia,
                "filtro" => $filtro,
                "choferInicial" => array("indice" => $indiceChoferInicial, "nombre" => $choferInicial),
                "usuario" => array("indice" => $indiceUsuario, "nombre" => $usuario),
            );
        }
    } else {
        return array("error" => "No se encontraron ambos usuarios en la lista");
    }
}
