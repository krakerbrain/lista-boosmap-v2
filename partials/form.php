<a href="<?= $_ENV['INICIO'] ?>" style="text-decoration: none">
    <h6 class="display-6 text-black">Lista Espera Boosmap</h6>
</a>
<form id="data-form" class="row g-2 my-3">
    <input class="form-control" type="text" id="nombreUsuario" name="nombreUsuario" placeholder="Nombre Chofer"
        aria-label="Nombre Chofer" list="choferes" />
    <datalist id="choferes">
        <?php foreach ($listaChoferes as $chofer) {
            echo "<option value='$chofer'>";
        }
        ?>
    </datalist>
    <label class="form-label mb-0" for="filtrar">Filtrar</label>
    <input class="form-control" type="number" name="filtrar" id="filtrar" value="10" />
    <input class="btn btn-primary" type="submit" value="Enviar" />
</form>