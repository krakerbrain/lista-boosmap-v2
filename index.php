<?php
require 'src/app.php';
require 'partials/header.php';
require 'partials/form.php'
?>

<style>
    /* Establecer la altura fija de la tabla */
    .fixed-height-table {
        max-height: 500px;
        /* Ajusta la altura según tus necesidades */
        overflow-y: auto;
        /* Agrega una barra de desplazamiento vertical si es necesario */
    }

    /* Estilo personalizado para filas con fondo oscuro */
    .custom-dark-row {
        background-color: #343a40;
        /* Define el color oscuro que deseas */
        color: white;
        /* Define el color del texto para mayor contraste */
    }
</style>
<?php
if (isset($diferenciaEntreDosChoferes) && !empty($diferenciaEntreDosChoferes)) {
    if (isset($diferenciaEntreDosChoferes["error"])) {
        echo '<div class="alert alert-danger" role="alert">' . $diferenciaEntreDosChoferes["error"] . '</div>';
    } else {
        $diferencia = $diferenciaEntreDosChoferes["diferencia"] - 2;
        $choferInicialIndice = $diferenciaEntreDosChoferes["choferInicial"]["indice"];
        $choferInicialNombre = $diferenciaEntreDosChoferes["choferInicial"]["nombre"];
        $usuarioIndice = $diferenciaEntreDosChoferes["usuario"]["indice"];
        $usuarioNombre = $diferenciaEntreDosChoferes["usuario"]["nombre"];

        if ($diferencia < 10) {
            $alert = 'alert-danger';
        } else if ($diferencia < 15) {
            $alert = 'alert-warning';
        } else {
            $alert = 'alert-primary';
        }

        $mensajeDiferencia = "Hay {$diferencia} choferes esperando por salir entre {$usuarioIndice}.- {$usuarioNombre} y {$choferInicialIndice}.- {$choferInicialNombre}.";
        echo '<div class="alert ' . $alert . ' role="alert">' . $mensajeDiferencia . '</div>';
    }
}

//usar alert en caso de que el filtro tenga -1
if (isset($_GET['filtrar'])) {
    if ($_GET['filtrar'] == -1) {
        echo '<div class="alert alert-warning" role="alert">Se muestran todos los registros</div>';
    } else if ($_GET['filtrar'] == -2) {
        echo '<div class="alert alert-warning" role="alert">Se muestran los últimos 20 registros</div>';
    }
}
?>

<div class="fixed-height-table">
    <table class="table table-striped table-sm">
        <tbody>
            <?php if (isset($usuariosCercanos)) {
                foreach ($usuariosCercanos as $row) {
                    $icon = $row['dato1'] == 'TRUE' ? '<i class="fa-solid fa-square-check"></i>' : '<i class="fa-solid fa-square"></i>';
                    // Verificar si el usuario en la fila actual es el usuario seleccionado
                    $selectedClass = $row['dato2'] === $usuario || $row['dato2'] === $_COOKIE['choferInicial'] ? 'table-dark' : '';
                    if (!empty($row['dato2'])) {
                        echo "<tr class='$selectedClass'>
                                <td class='text-center'>" . $row['indice'] . "</td>
                                <td class='text-bg-primary text-center'>" . $icon . "</td>
                                <td onclick='verDatos(\"" . $row['dato2'] . "\")'>" . $row['dato2'] . "</td>
                            </tr>";
                    }
                }
            } else {
                echo "<p>No se pudieron obtener los datos.</p>";
            } ?>
        </tbody>
    </table>
</div>

<?php require 'partials/footer.php'; ?>