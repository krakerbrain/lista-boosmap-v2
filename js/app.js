function getCookie(name) {
  const value = "; " + document.cookie;
  const parts = value.split("; " + name + "=");
  if (parts.length === 2) return parts.pop().split(";").shift();
}

document.addEventListener("DOMContentLoaded", function () {
  const choferInicial = getCookie("choferInicial");
  if (choferInicial) {
    document.getElementById("choferInicial").value = choferInicial;
    document.getElementById("choferInicialHidden").value = choferInicial;
    document.getElementById("choferInicial").disabled = true;
    document.getElementById("bloquearDiv").style.display = "block";
  }

  let buttons = [10, 15, 20, 30, 50, 100, "all", "end"];

  buttons.forEach((button) => {
    document.getElementById(
      "btnFiltrar"
    ).innerHTML += `<buttton class="btn btn-outline-primary btn-sm rounded-circle me-1" onclick="agregaFiltro('${button}')"><span class="small">${button}</span></buttton>`;
  });
});

function agregaFiltro(filtroButton) {
  if (filtroButton === "all") {
    filtroButton = -1;
  } else if (filtroButton === "end") {
    filtroButton = -2;
  }
  document.getElementById("filtrar").value = filtroButton;
  document.getElementById("data-form").submit();
}

document.getElementById("data-form").addEventListener("submit", function (event) {
  // event.preventDefault(); // Evitar envío del formulario

  const choferInicial = document.getElementById("choferInicial").value;
  if (choferInicial) {
    document.cookie = `choferInicial=${choferInicial}; expires=Fri, 31 Dec 9999 23:59:59 GMT`;
    document.getElementById("choferInicial").disabled = true;
    document.getElementById("bloquearDiv").style.display = "block";
  }
});

document.getElementById("bloquearCheckbox").addEventListener("click", function () {
  const choferInicial = document.getElementById("choferInicial");
  choferInicial.disabled = !this.checked;
});

function verDatos(choferSeleccionado) {
  document.getElementById("nombreUsuario").value = choferSeleccionado;
  document.getElementById("data-form").submit();
}
