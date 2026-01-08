{{--RECORDAR E IR AL BODY PARA QUE PUEDAMOS DAR ISPECCIONAR E IR AL JAVASCRIPT DE VALIDACIONES PARA PODER DAR CLICK EN F12--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', config('app.name'))</title>




    <!-- jQuery (obligatorio para Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> {{--Estilos--}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"> {{--Iconos--}}

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!--  Swal.fire alertas con estilos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!--  decimal.js importacion de la libreria que ayuda con los decimales limpios -->
    <script src="https://cdn.jsdelivr.net/npm/decimal.js@10.4.3/decimal.min.js"></script>

    <!--  fancybox importacion de la libreria que ayuda con la ampliacion de las imagenes -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />

    <!--  fancybox importacion de la libreria que ayuda con la ampliacion de las imagenes -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
    
    <!-- Importación de CSS -->
    @vite(['resources/css/variables.css', 'resources/css/style.css', 'resources/js/validaciones.js', 'resources/css/catalogos.css'])
</head>
<body oncontextmenu="return false;">

    {{-- Sidebar --}}
    @include('components.sidebar')

    {{-- Header --}}
    @include('components.header')
    {{--@include('components.navBar')--}}


    {{-- Contenido principal --}}
    <main class="content content-body">
        {{--muestra los mensajes de errrores en todas las vistas--}}
        @if(session('success'))
            <div class="panel-alert success"> {!! session('success') !!} </div>
        @endif

        @if(session('warning'))
            <div class="panel-alert warning"> {!! session('warning') !!} </div>
        @endif

        @if(session('error')) 
            <div class="panel-alert error"> {!! session('error') !!} </div>
        @endif

        @yield('contenido')
    </main>

    {{-- Overlay --}}
    <div class="overlay" id="overlay"></div>

    
        <!-- Bootstrap JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Script toggle sidebar para cuando es responsivo que se convierte en hamburguesa-->
        <script>
            //El "overlay" es una capa semitransparente que se muestra detrás del sidebar (o cualquier panel flotante) para oscurecer el resto de la pantalla
            // Espera a que el DOM esté completamente cargado antes de ejecutar el código
            document.addEventListener('DOMContentLoaded', function () {
                // Referencias a los elementos del DOM
                const sidebar = document.querySelector('.sidebar'); // El panel lateral
                const toggleBtn = document.getElementById('sidebarToggle'); // Botón para abrir el sidebar
                const closeBtn = document.getElementById('sidebarClose'); // Botón para cerrarlo (si existe)
                const overlay = document.getElementById('overlay'); // Capa oscura detrás del sidebar

                // Función para mostrar el sidebar y activar el overlay
                const openSidebar = () => {
                    sidebar.classList.add('sidebar-show'); // Añade la clase que lo hace visible
                    overlay.classList.add('active'); // Muestra el fondo oscuro
                };

                // Función para ocultar el sidebar y desactivar el overlay
                const closeSidebar = () => {
                    sidebar.classList.remove('sidebar-show'); // Oculta el sidebar
                    overlay.classList.remove('active'); // Oculta el fondo oscuro
                };

                // Asigna eventos de clic para abrir y cerrar el sidebar
                if (toggleBtn) toggleBtn.addEventListener('click', openSidebar); // Abre al hacer clic
                if (closeBtn) closeBtn.addEventListener('click', closeSidebar); // Cierra al hacer clic
                if (overlay) overlay.addEventListener('click', closeSidebar); // Cierra si se hace clic fuera
            });
        </script>

    @stack('scripts')
</body>
</html>
