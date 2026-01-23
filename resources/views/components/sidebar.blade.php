{{-- Version actual de la barra de navegacion vertical--}}
<nav class="sidebar d-flex flex-column">
<!-- Botón cerrar sidebar (icono flecha) para menu hamburguesa-->
    <button id="sidebarClose" class="d-md-none">
        <i class="bi bi-arrow-left"></i>
    </button>

{{-- titulo y logo --}}
    <div class="logo-section text-center">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('storage/' . $configuracion->ruta_logo_principal) }}"  alt="Logo" class="logo-img mb-2">
        </a>
        <a href="https://www.kombitec.com.mx/" class="brand-text d-block">MI KOMBITEC</a>
    </div>
{{-- Dashboard --}}
    <ul class="nav flex-column px-2">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
        </li>

{{-- Ventas con submenu --}}
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuVentas" role="button" aria-expanded="false" aria-controls="submenuVentas">
                Ventas
                <i class="bi bi-chevron-down"></i>
            </a>
            <div class="collapse" id="submenuVentas" data-bs-parent=".sidebar">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-3">
                    @if (Auth::user()->rol_id == 1 || Auth::user()->rol_id == 2)
                        <li><a href="{{ route('clientes') }}" class="nav-link">Clientes</a></li>
                    @endif
                    <li><a href="{{ route('cotizaciones') }}" class="nav-link">Cotizaciones</a></li>
                    <li><a href="{{ route('Pedidos') }}" class="nav-link">Pedidos</a></li>
                    @if (Auth::user()->rol_id == 1 || Auth::user()->rol_id == 2)<li>
                    <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuConfiguracionVentas" role="button" aria-expanded="false" aria-controls="submenuConfiguracionVentas">
                    Configuración
                    <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="collapse" id="submenuConfiguracionVentas" data-bs-parent="#submenuVentas">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-3">
                            {{--<li><a href="#" class="nav-link">Cartera de clientes</a></li>--}}
                            <li><a href="{{ route('admin.catalogo.vendedores') }}" class="nav-link">Catálogo de vendedores</a></li>
                        </ul>
                    </div>
                    </li>@endif
                </ul>
            </div>
        </li>

{{-- Productos/Servicios con submenu --}}
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuProductos" role="button" aria-expanded="false" aria-controls="submenuProductos">
            Productos/Servicios
            <i class="bi bi-chevron-down"></i>
            </a>
            <div class="collapse" id="submenuProductos" data-bs-parent=".sidebar">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-3">
                @if (Auth::user()->rol_id != 3)
                     <li><a href="{{ route('consulta_stock') }}" class="nav-link">Consulta Stock</a></li>
                @endif
                <li><a href="{{ route('articulos', ['estatus' => 'Activos'])}}" class="nav-link">Productos</a></li>
                @if (Auth::user()->rol_id == 1 || Auth::user()->rol_id == 2)<li>
                    <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuConfiguracionProductos" role="button" aria-expanded="false" aria-controls="submenuConfiguracionProductos">
                    Configuración
                    <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="collapse" id="submenuConfiguracionProductos" data-bs-parent="#submenuProductos">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-3">
                        <li><a href="{{ route('admin.marcas.index') }}" class="nav-link">Grupos de Productos</a></li>
                        </ul>
                    </div>
                </li>@endif
            </ul>
            </div>
        </li>

{{-- Configuracion general --}}
        @if (Auth::user()->rol_id == 1 || Auth::user()->rol_id == 2)
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuConfiguracionGeneral" role="button" aria-expanded="false" aria-controls="submenuConfiguracionGeneral">
            Configuración General   
            <i class="bi bi-chevron-down"></i>
            </a>
            <div class="collapse" id="submenuConfiguracionGeneral" data-bs-parent=".sidebar">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-3">
                <li><a href="{{ route('configuracion') }}" class="nav-link">Configurar Sistema</a></li>
                <li><a href="{{ route('usuarios') }}" class="nav-link">Usuarios</a></li>
                @if (Auth::user()->rol_id == 1)
                <li><a href="{{ route('sincronizadores') }}" class="nav-link">Sincronizadores</a></li>
                @endif
            </ul>
            </div>
        </li>@endif
    </ul>
</nav>