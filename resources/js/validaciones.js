//Aqui se validan los alerts, los imputs y la animacion de cargando

//solo permite que se escriban numeros en los inputs
$(document).on('input', '.cantidad, #telefono, max-sessions-input', function() {
    this.value = this.value.replace(/[^0-9]/g, ''); 
});

//No permite que se inserten las las letras e y algunos simbolos de operacion y el punto
$(document).on('keydown', '.cantidad, #telefono, .max-sessions-input', function(e) {
    if (["e", "E", "+", "-", "."].includes(e.key)) {
        e.preventDefault();
    }
});

//No deja que un contador de cantidad pueda ser cero nunca te obliga a que el usuario elimine la linea de cotizacion
$(document).on('blur', '.cantidad', function () {
    const valor = parseInt(this.value, 10);

    if (valor === 0 || isNaN(valor)) {
        this.value = "1";
        alert('No se admiten cantidades en 0 (cero), elimina la línea en dicho caso');
    }
});


//acciones de las alerts 
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar manualmente
    document.querySelectorAll('.panel-alert .close-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            this.parentElement.remove();
        });
    });

    // Cerrar automáticamente después de X segundos
    setTimeout(() => {
        document.querySelectorAll('.panel-alert').forEach(function(alert){
            alert.remove();
        });
    }, 6000);
});



//valida las animaciones de cargando...
document.addEventListener('DOMContentLoaded', function() {
    const loading = document.getElementById('loading');

    // Mostrar loader al enviar formularios dentro de sincronizadores-panel
    document.querySelectorAll('.sincronizadores-panel form').forEach(form => {
        form.addEventListener('submit', function() {
            loading.style.display = 'grid';
        });
    });

    // Mostrar loader al hacer clic en enlaces con data-loading="true"
    document.querySelectorAll('a[data-loading="true"]').forEach(link => {
        link.addEventListener('click', function() {
            loading.style.display = 'grid';
            // no evitamos la redirección — solo mostramos el loader
        });
    });
});



//Bloquea la tecla f12 del navegador y todo lo que pueda abrir DevTools o inspeccionador o herramientas de desarrollo
document.onkeydown = function(e) {
    // Bloquea F12
    if (e.keyCode === 123) {
        return false;
    }
    // Bloquea la combinación Ctrl+Shift+I
    if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
        return false;
    }
    // Bloquea la combinación Ctrl+U (ver código fuente)
    if (e.ctrlKey && e.keyCode === 85) {
        return false;
    }
    if (
        e.keyCode == 123 || // F12
        (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || // Ctrl+Shift+I / J
        (e.ctrlKey && e.keyCode == 85) // Ctrl+U
    ) {
        e.preventDefault();
        return false;
    }

};
document.addEventListener('contextmenu', e => e.preventDefault()); //Bloquea el click derech
//document.addEventListener('selectstart', e => e.preventDefault()); // Evita que se seleccione el contenido 
document.addEventListener('dragstart', e => e.preventDefault()); //bloquea el arrastre de elementos


if (window.top !== window.self) {
    window.location = '/403';
}

/*Cada 4 minutos mientras la pestaña este abierta mandara una señal de activo para que las sesiones solo se 
cierren cuando se trabe el navegador, cerraron la pestaña  esta funcion va en conjunto con el middleware de limites de sesiones*/
(function () {

    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta.getAttribute('content');

    let contador = 0;

    setInterval(() => {
        contador++;
        fetch('/heartbeat', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });
    }, 24000); // 4 minutos en milisegundos
})();