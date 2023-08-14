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
</style>
<div class="fixed-height-table">
    <table class="table table-striped table-sm">
        <tbody>
            <?php if (isset($usuariosCercanos)) {
                // var_dump($usuariosCercanos);
                    foreach ($usuariosCercanos as $row) {
                        $icon = $row['dato1'] == 'TRUE' ? '<i class="fa-solid fa-square-check"></i>' : '<i class="fa-solid fa-square"></i>';
                        // Verificar si el usuario en la fila actual es el usuario seleccionado
                        $selectedClass = $row['dato2'] === $usuario ? 'table-dark' : '';
                        if (!empty($row['dato2'])) {
                            echo "<tr class='$selectedClass'><td class='text-center'>" . $row['indice'] . "</td><td class='text-bg-primary text-center'>".$icon."</td><td>" . $row['dato2'] . "</td></tr>";
                        }
                    }
                } else {
                    echo "<p>No se pudieron obtener los datos.</p>";
            } ?>
        </tbody>
    </table>
</div>
<?php require 'partials/footer.php'; ?>