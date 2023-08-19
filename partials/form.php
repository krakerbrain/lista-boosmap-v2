<a href="<?= $_ENV['INICIO'] ?>" style="text-decoration: none">
    <h6 class="display-6 text-black">Lista Espera Boosmap</h6>
</a>
<form id="data-form" class="row g-2 my-3">
    <input class="form-control" type="text" id="choferInicial" name="choferInicial" placeholder="Chofer Inicial"
        aria-label="Chofer Inicial" />
    <input type="hidden" name="choferInicialHidden" id="choferInicialHidden" value="">
    <div id="bloquearDiv" style="display:none">
        <input type="checkbox" id="bloquearCheckbox" /> Desbloquear
    </div>
    <input class="form-control" type="text" id="nombreUsuario" name="nombreUsuario" placeholder="Nombre Chofer"
        aria-label="Nombre Chofer" list="choferes" />
    <datalist id="choferes">
        <?php foreach ($listaChoferes as $choferlist) {
            echo "<option value='$choferlist'>";
        }
        ?>
    </datalist>

    <label class="form-label mb-0" for="filtrar">Filtrar</label>
    <div class="btn-group" id="btnFiltrar" style="width: 50%;"></div>
    <input class="form-control" type="number" name="filtrar" id="filtrar"
        value="<?php echo (isset($_GET['filtrar']) && $_GET['filtrar'] != -1) ? $_GET['filtrar'] : ''; ?>"
        placeholder="Filtrar por cualquier número" />

    <input class="btn btn-primary" type="submit" value="Enviar" />
</form>