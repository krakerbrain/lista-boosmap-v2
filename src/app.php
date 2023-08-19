<?php

require '../vendor/autoload.php';
// require './newArray.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

    //DESARROLLO
    $jsonData = file_get_contents('datos.json');
    $valuesFromJson = json_decode($jsonData, true);
    $values = limpiarYAjustarValores($valuesFromJson);

    //PRODUCCION
    // $values = limpiarYAjustarValores($response);

    if (isset($_GET['filtrar']) && !empty($_GET['filtrar'])) {
        $filtro = $_GET['filtrar'] != -1 ? $_GET['filtrar'] : count($values);
    } else {
        $filtro = 10;
    }

    $chofer = isset($_GET['choferInicialHidden']) ? $_GET['choferInicialHidden'] : '';

    $usuario = isset($_GET['nombreUsuario']) ? $_GET['nombreUsuario'] : '';
    $valorCookie = $_COOKIE['choferInicial'];

    $diferenciaEntreDosChoferes = calcularDiferenciaUsuarios($values, $chofer, $usuario);
    $diferencia = isset($diferenciaEntreDosChoferes['diferencia']) ? $diferenciaEntreDosChoferes['diferencia'] : $filtro;


    $usuariosCercanos = obtenerRegistrosSegunChofer($values, $diferencia, $valorCookie);
    $listaChoferes = obtenerNombresSinRepetir($values);

    if (empty($values)) {
        echo "No data found.";
    }
} catch (Google\Service\Exception $e) {
    // Registramos el error en el archivo de registro
    error_log("Error en la llamada a la API: " . $e->getMessage(), 0);

    // Muestra un mensaje genérico para el cliente
    echo "Lo sentimos, ha ocurrido un error. Por favor, inténtalo de nuevo más tarde.";
    // echo "Error en la llamada a la API: " . $e->getMessage();
}

function limpiarYAjustarValores($response)
{

    $values = [];
    $currentEmptyCells = 0; // Contador de celdas vacías al inicio
    //produccion
    // foreach ($response->getValues() as $row) {
    //desarrollo
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

function obtenerRegistrosSegunChofer($values, $filtro, $chofer)
{
    $lastTrueIndex = -1;

    for ($i = count($values) - 1; $i >= 0; $i--) {
        if ((isset($values[$i][1]) && $values[$i][1] === $chofer)) {
            $lastTrueIndex = $i;
            break;
        }
    }

    if (isset($values[$lastTrueIndex][0]) && $values[$lastTrueIndex][0] === "TRUE") {
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if ((isset($values[$i][0]) && $values[$i][0] === "TRUE")) {
                $lastTrueIndex = $i + 1;
                break;
            }
        }
        return upAndDown($values, $filtro, $lastTrueIndex);
    } else {
        return upAndDown($values, $filtro, $lastTrueIndex);
    }
}

function upAndDown($values, $filtro, $lastTrueIndex)
{
    for ($i = max(0, $lastTrueIndex - $filtro); $i <= $lastTrueIndex - 1; $i++) {
        $valores_total[] = [
            'indice' => $i + 1,
            'dato1' => $values[$i][0],
            'dato2' => $values[$i][1],
        ];
    }

    for ($i = $lastTrueIndex; $i < min(count($values), $lastTrueIndex + $filtro); $i++) {
        $valores_total[] = [
            'indice' => $i + 1,
            'dato1' => $values[$i][0],
            'dato2' => $values[$i][1],
        ];
    }

    return ($valores_total);
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
            $indiceChoferInicial = $i;
        }

        if ($indiceUsuario === -1 && isset($values[$i][1]) && $values[$i][1] === $usuario) {
            $indiceUsuario = $i;
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
                "diferencia" => $diferencia,
                "choferInicial" => array("indice" => $indiceChoferInicial, "nombre" => $choferInicial),
                "usuario" => array("indice" => $indiceUsuario, "nombre" => $usuario)
            );
        }
    } else {
        return array("error" => "No se encontraron ambos usuarios en la lista");
    }
}
