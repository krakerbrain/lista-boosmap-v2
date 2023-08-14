<?php

require '../vendor/autoload.php';
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
$range = $sheet.'!A:B';

try {
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = limpiarYAjustarValores($response);

    $filtro = isset($_GET['filtrar']) && !empty($_GET['filtrar']) ? $_GET['filtrar'] : 10;

    $usuario = isset($_GET['nombreUsuario']) ? $_GET['nombreUsuario'] : '';

    $usuariosCercanos = $usuario == '' ? obtenerUsuariosCercanos($values, $filtro) : obtenerUsuario($values, $filtro, $usuario);

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

function limpiarYAjustarValores($response) {

    $values = [];
    $currentEmptyCells = 0; // Contador de celdas vacías al inicio

    foreach ($response->getValues() as $row) {
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
function obtenerUsuariosCercanos($values,$filtro) {
    $lastTrueIndex = -1;

    // Encontrar el índice del último "true"
    for ($i = count($values) - 1; $i >= 0; $i--) {
        if (isset($values[$i][0]) && $values[$i][0] === "TRUE") {
            $lastTrueIndex = $i;
            break;
        }
    }

    $usuariosCercanos = array();

    if ($lastTrueIndex !== -1) {
        // Encontrar el índice del primer "false" después del último "true"
        for ($i = $lastTrueIndex + 1; $i < count($values); $i++) {
            if (isset($values[$i][0]) && $values[$i][0] === "FALSE") {
                $startIndex = max(0, $i - $filtro);
                $endIndex = min(count($values) - 1, $i + $filtro);

                for ($j = $startIndex; $j <= $endIndex; $j++) {
                    $usuariosCercanos[] = array(
                        'indice' => $j + 1,
                        'dato1' => isset($values[$j][0]) ? $values[$j][0] : '',
                        'dato2' => isset($values[$j][1]) ? $values[$j][1] : '',
                    );
                }
                break;
            }
        }
    }

        // Si no se encontró ningún "false" después de "true", mostrar los últimos 10 registros
        if (empty($usuariosCercanos)) {
            $startIndex = max(0, $lastTrueIndex - 10);
            $endIndex = min(count($values) - 1, $lastTrueIndex + 10);
    
            for ($j = $startIndex; $j <= $endIndex; $j++) {
                $usuariosCercanos[] = array(
                    'indice' => $j + 1,
                    'dato1' => isset($values[$j][0]) ? $values[$j][0] : '',
                    'dato2' => isset($values[$j][1]) ? $values[$j][1] : '',
                );
            }
        }

    return $usuariosCercanos;
}

function obtenerUsuario($values, $filtro, $usuario) {
    if ($usuario === 'X') {
        $usuario = 'MARIO MONTENEGRO';
    }
    $usuario = strtoupper(trim($usuario)); 
    $targetIndex = -1;
   
    // Encontrar el índice del último momento en que aparece el usuario
    for ($i = count($values) - 1; $i >= 0; $i--) {
        if (isset($values[$i][1]) && $values[$i][1] === $usuario) {
            $targetIndex = $i;
            break;
        }
    }
    
    if ($targetIndex !== -1) {
        $startIndex = max(0, $targetIndex - $filtro);
        
        // Verificar si el usuario está marcado como "false"
        if (isset($values[$targetIndex][0]) && $values[$targetIndex][0] === 'FALSE') {
            // Mostrar solo los 5 registros posteriores
            $endIndex = min(count($values) - 1, $targetIndex + 5);
        } else {
            $endIndex = min(count($values) - 1, $targetIndex + $filtro);
        }

        $usuariosCercanos = array();
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $usuariosCercanos[] = array(
                'indice' => $i + 1,
                'dato1' => isset($values[$i][0]) ? $values[$i][0] : '',
                'dato2' => isset($values[$i][1]) ? $values[$i][1] : '',
            );
        }

        return $usuariosCercanos;
    } else {
        return null; // No se encontró el usuario en el array
    }
}

function obtenerNombresSinRepetir($values) {
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