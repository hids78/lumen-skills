// Script para la interfaz del cliente.

document.addEventListener('DOMContentLoaded', function() {
    // Ejemplo: Validar un formulario simple.
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Formulario enviado!');
        });
    }
});