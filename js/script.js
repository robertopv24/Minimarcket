// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {

  // Ejemplo: Mostrar un mensaje en la consola al cargar la página
  console.log('¡La página se ha cargado!');

  // Ejemplo: Agregar un evento de clic a un botón
  const boton = document.getElementById('miBoton'); // Reemplaza 'miBoton' con el ID de tu botón
  if (boton) {
    boton.addEventListener('click', function() {
      alert('¡Botón clickeado!');
    });
  }

  // Ejemplo: Manipular el contenido de un elemento
  const elemento = document.getElementById('miElemento'); // Reemplaza 'miElemento' con el ID de tu elemento
  if (elemento) {
    elemento.innerHTML = '¡Texto modificado con JavaScript!';
  }

});
// --- Inicio de javascript insertado por el instalador ---

// Javascript básico insertado por el instalador

console.log('¡Javascript inicial insertado por el instalador!');

function mostrarAlertaInstalador() {
    alert('¡Alerta desde Javascript insertado por el instalador!');
}

// --- Fin de javascript insertado por el instalador ---
