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
    <div id="btnFiltrar"></div>
    <input class="form-control" type="number" name="filtrar" id="filtrar"
        value="<?php echo (isset($_GET['filtrar']) && $_GET['filtrar'] != -1) ? $_GET['filtrar'] : ''; ?>"
        placeholder="Filtrar por cualquier número" />

    <input class="btn btn-primary" type="submit" value="Enviar" />
</form>

<script>
function getCookie(name) {
    const value = "; " + document.cookie;
    const parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
}

document.addEventListener('DOMContentLoaded', function() {

    const choferInicial = getCookie('choferInicial');
    if (choferInicial) {
        document.getElementById('choferInicial').value = choferInicial;
        document.getElementById('choferInicialHidden').value = choferInicial;
        document.getElementById('choferInicial').disabled = true;
        document.getElementById('bloquearDiv').style.display = 'block';
    }

    let buttons = [10, 15, 20, 30, 50, 100, 'all'];

    buttons.forEach(button => {
        document.getElementById('btnFiltrar').innerHTML +=
            `<buttton class="btn btn-outline-primary rounded-circle me-1" onclick="agregaFiltro(${button})"><span class="small">${button}</span></buttton>`
    })
});

function agregaFiltro(filtro) {

    document.getElementById('filtrar').value = filtro !== 'all' ? -1 : filtro;
    document.getElementById('data-form').submit();
}

document.getElementById('data-form').addEventListener('submit', function(event) {
    // event.preventDefault(); // Evitar envío del formulario

    const choferInicial = document.getElementById('choferInicial').value;
    if (choferInicial) {
        document.cookie = `choferInicial=${choferInicial}; expires=Fri, 31 Dec 9999 23:59:59 GMT`;
        document.getElementById('choferInicial').disabled = true;
        document.getElementById('bloquearDiv').style.display = 'block';
    }
});

document.getElementById('bloquearCheckbox').addEventListener('click', function() {
    const choferInicial = document.getElementById('choferInicial');
    choferInicial.disabled = !this.checked;
});
</script>