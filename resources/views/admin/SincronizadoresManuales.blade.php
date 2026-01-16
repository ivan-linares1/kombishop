@extends('layouts.app')

@section('title', 'Panel de Sincronizadores')

@section('contenido')
@vite(['resources/css/sincronizadores.css'])

<div class="container mt-5 sincronizadores-panel">
    <h1>Panel de Control de Sincronizadores Manuales</h1>
    <x-loading /> <!--animacion de cargando-->

    <div class="row g-4 justify-content-center">

        @php
            $cards = [
                /*ORTT 2*/ ['icon' => 'bi-currency-exchange icon-currency', 'titulo' => 'Divisas del Día',         'texto' => 'Sincroniza las divisas del día.',                    'servicio' => 'Cambios_Monedas',         'ruta1' => 'C:\SFTP_MiKombitec\data\TiposDeCambio\TiposDeCambio.xml',                  'ruta2' => 'C:\SFTP_MiKombitec\data\TiposDeCambioActualiza\TiposDeCambioActualiza.xml'],
                /*OQUT 2*/ ['icon' => 'bi-file-earmark-text icon-DocNum',   'titulo' => 'Cotizaciones',            'texto' => 'Sincroniza los DocNum desde Sap.',                   'servicio' => 'DocNum',                  'ruta1' => 'C:\SFTP_MiKombitec\data\NoCotizacion\NoCotizacion.xml',                    'ruta2' => 'C:\SFTP_MiKombitec\data\NoCotizacion_Actualiza\NoCotizacion_Actualiza.xml'],
                /*ORDR 2*/ ['icon' => 'bi-cart-check icon-DocNumP',         'titulo' => 'Pedidos',                 'texto' => 'Sincroniza los DocNum desde Sap.',                   'servicio' => 'DocNumP',                 'ruta1' => 'C:\SFTP_MiKombitec\data\NoPedido\NoPedidos.xml',                           'ruta2' => 'C:\SFTP_MiKombitec\data\NoPedido_Actualiza\NoPedidos.xml'],
                /*OCRN 1*/ ['icon' => 'bi-currency-dollar icon-monedas',    'titulo' => 'Monedas',                 'texto' => 'Sincroniza todas las monedas.',                      'servicio' => 'Monedas',                 'ruta1' => 'C:\SFTP_MiKombitec\data\Monedas\Monedas.xml',                              ],
                /*OITB 2*/ ['icon' => 'bi-grid-1x2-fill icon-marcas',       'titulo' => 'Grupos de Artículos',     'texto' => 'Sincroniza los grupos de artículos o marcas.',       'servicio' => 'Marcas',                  'ruta1' => 'C:\SFTP_MiKombitec\data\Marcas\Marcas.xml',                                'ruta2' => 'C:\SFTP_MiKombitec\data\Marcas_Actualiza\Marcas_Actualiza.xml'],
                /*OPLN 2*/ ['icon' => 'bi-list-check icon-categorias',      'titulo' => 'Cat. Listas Precios',     'texto' => 'Sincroniza las categorías de listas de precios.',    'servicio' => 'Categoria_Lista_Precios', 'ruta1' => 'C:\SFTP_MiKombitec\data\ListaPrecios\ListaPrecios.xml',                    'ruta2' => 'C:\SFTP_MiKombitec\data\ListaPreciosActualiza\ListaPreciosActualiza.xml'],
                /*OITM 2*/ ['icon' => 'bi-box-seam icon-articulos',         'titulo' => 'Artículos',               'texto' => 'Sincroniza todos los artículos.',                    'servicio' => 'Articulos',               'ruta1' => 'C:\SFTP_MiKombitec\data\Articulos\Articulos.xml',                          'ruta2' => 'C:\SFTP_MiKombitec\data\Articulos_Actualiza\Articulos_Actualiza.xml'],
                /*ITM1 2*/ ['icon' => 'bi-cash-stack icon-precios',         'titulo' => 'Lista de Precios',        'texto' => 'Sincroniza los precios de lista.',                   'servicio' => 'Lista_Precios',           'ruta1' => 'C:\SFTP_MiKombitec\data\Precios\Precios.xml',                              'ruta2' => 'C:\SFTP_MiKombitec\data\Precios_Actualiza\Precios_Actualiza.xml'],
                /*OCRD 2*/ ['icon' => 'bi-people-fill icon-clientes',       'titulo' => 'Lista de Clientes',       'texto' => 'Sincroniza la lista de Clientes.',                   'servicio' => 'Clientes',                'ruta1' => 'C:\SFTP_MiKombitec\data\Clientes\Clientes.xml',                            'ruta2' => 'C:\SFTP_MiKombitec\data\Clientes_Actualiza\Clientes_Actualiza.xml',],
                /*CRD1 2*/ ['icon' => 'bi-geo-alt-fill icon-direcciones',   'titulo' => 'Direcciones de Clientes', 'texto' => 'Sincroniza la dirección de Clientes.',               'servicio' => 'Direcciones',             'ruta1' => 'C:\SFTP_MiKombitec\data\Clientes_Direcciones\Clientes_Direcciones.xml',    'ruta2' => 'C:\SFTP_MiKombitec\data\Clientes_Direcciones_Actualiza\Clientes_Direcciones_Actualiza.xml'],
                /*OEDG 2*/ ['icon' => 'bi-tags-fill icon-descuentos',       'titulo' => 'Grupos de Descuentos',    'texto' => 'Sincroniza grupos de descuentos.',                   'servicio' => 'Grupo_Descuentos',        'ruta1' => 'C:\SFTP_MiKombitec\data\Descuentos\Descuentos.xml',                        'ruta2' => 'C:\SFTP_MiKombitec\data\Descuentos_Actualiza\Descuentos_Actualiza.xml'],
                /*EDG1 5*/ ['icon' => 'bi-percent icon-descuento',          'titulo' => 'Descuentos',              'texto' => 'Sincroniza los descuentos.',                         'servicio' => 'Descuentos_Detalle',      'ruta1' => 'C:\SFTP_MiKombitec\data\Descuentos_Cantidad_Actualiza_',                   'ruta2' => 'C:\SFTP_MiKombitec\data\Descuentos_Cantidad_Actualiza\Descuentos_Cantidad_Actualiza.xml'],
                /*OSLP 1*/ ['icon' => 'bi-person-lines-fill icon-vendedor', 'titulo' => 'Vendedores',              'texto' => 'Sincroniza los vendedores.',                         'servicio' => 'Vendedores',              'ruta1' => 'C:\SFTP_MiKombitec\data\Vendedores\Vendedores.xml',                        ],
                /*stock2*/ ['icon' => 'bi bi-graph-up icon-stock',          'titulo' => 'Stock',                   'texto' => 'Sincroniza el stock de articulos.',                  'servicio' => 'Stock',                   'ruta1' => 'C:\SFTP_MiKombitec\data\Stock\Stock.xml',                                  'ruta2' => 'C:\SFTP_MiKombitec\data\Stock_Actualiza\StockActualiza.xml'],
                /*CotSt2*/ ['icon' => 'bi-check-circle icon-cotEstatus',    'titulo' => 'Estado Cotizacion',       'texto' => 'Sincroniza el estado de las cotizaciones.',          'servicio' => 'CotizacionEstatus',       'ruta1' => 'C:\SFTP_MiKombitec\data\Estatus_Cotizaciones\Estatus_Cotizaciones.xml',    'ruta2' => 'C:\SFTP_MiKombitec\data\Estatus_Cotizaciones_Actualiza\Estatus_Cotizaciones_Actualiza.xml'],
                /*PedSt2*/ ['icon' => 'bi-check2-circle icon-pedEstatus',   'titulo' => 'Estado Pedido',           'texto' => 'Sincroniza el estado de los pedidos.',               'servicio' => 'PedidoEstatus',           'ruta1' => 'C:\SFTP_MiKombitec\data\Estatus_Pedidos\Estatus_Pedidos.xml',              'ruta2' => 'C:\SFTP_MiKombitec\data\Estatus_Pedidos_Actualiza\Estatus_Pedidos_Actualiza.xml'],
            ];
        @endphp

        @foreach ($cards as $card)
        <div class="col-md-3 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi {{ $card['icon'] }}"></i>
                    <h5 class="card-title">{{ $card['titulo'] }}</h5>
                    <p class="card-text">{{ $card['texto'] }}</p>

                    <div class="d-flex gap-2">
                        <!-- BOTÓN 1 -->
                        <form action="{{ route('SincronizarArchivo', ['servicio' => $card['servicio']]) }}" method="POST" class="flex-fill" style="flex: 1 1 48%;">
                            @csrf
                            <input type="hidden" name="ruta" value="{{ $card['ruta1'] }}">
                            <button type="submit" class="btn btn-success w-100">Carga Total</button>
                        </form>

                        <!-- BOTÓN 2 -->
                        @if ($card['servicio'] != 'Monedas' && $card['servicio'] != 'Vendedores')
                            <form action="{{ route('SincronizarArchivo', ['servicio' => $card['servicio']]) }}" method="POST" class="flex-fill" style="flex: 1 1 48%;">
                                @csrf
                                <input type="hidden" name="ruta" value="{{ $card['ruta2'] }}">
                                <button type="submit" class="btn btn-success w-100">Carga Diaria</button>
                            </form>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        @endforeach

    </div>
</div>
@endsection


{{--      ESTE ES UN MENU QUE SE USO EN EL DESARROLLO CUANDO SE ESTABAN PROBANDO TODOS LOS ARCHIVOS DE SERVICIOS DE MANERA LOCAL 

                /*ORTT 2*/ ['icon' => 'bi-currency-exchange icon-currency', 'titulo' => 'Divisas del Día',         'texto' => 'Sincroniza las divisas del día.',                    'servicio' => 'Cambios_Monedas',         'ruta1' => 'C:\Users\KOM090\Documents\Nueva\TiposDeCambio.xml',                                            'ruta2' => 'C:\Users\KOM090\Documents\Nueva\TiposDeCambioActualiza.xml'],
                /*OQUT 2*/ ['icon' => 'bi-file-earmark-text icon-DocNum',   'titulo' => 'Cotizaciones',            'texto' => 'Sincroniza los DocNum desde Sap.',                   'servicio' => 'DocNum',                  'ruta1' => 'C:\Users\KOM090\Documents\Nueva\NoCotizacion\NoCotizacion.xml',                       'ruta2' => 'C:\Users\KOM090\Documents\Nueva\NoCotizacion_Actualiza\NoCotizacion_Actualiza.xml'],
                /*ORDR 2*/ ['icon' => 'bi-cart-check icon-DocNumP',         'titulo' => 'Pedidos',                 'texto' => 'Sincroniza los DocNum desde Sap.',                   'servicio' => 'DocNumP',                 'ruta1' => 'C:\Users\KOM090\Documents\Nueva\NoPedido\NoPedidos.xml',                       'ruta2' => 'C:\Users\KOM090\Documents\Nueva\NoPedido_Actualiza\NoPedidos.xml'],
                /*OCRN 1*/ ['icon' => 'bi-currency-dollar icon-monedas',    'titulo' => 'Monedas',                 'texto' => 'Sincroniza todas las monedas.',                      'servicio' => 'Monedas',                 'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Monedas.xml',                                 ],
                /*OITB 2*/ ['icon' => 'bi-grid-1x2-fill icon-marcas',       'titulo' => 'Grupos de Artículos',     'texto' => 'Sincroniza los grupos de artículos o marcas.',       'servicio' => 'Marcas',                  'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Marcas\Marcas.xml',                    'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Marcas_Actualiza\Marcas_Actualiza.xml'],
                /*OPLN 2*/ ['icon' => 'bi-list-check icon-categorias',      'titulo' => 'Cat. Listas Precios',     'texto' => 'Sincroniza las categorías de listas de precios.',    'servicio' => 'Categoria_Lista_Precios', 'ruta1' => 'C:\Users\KOM090\Documents\Nueva\ListaPrecios\ListaPrecios.xml',                                 'ruta2' => 'C:\Users\KOM090\Documents\Nueva\ListaPreciosActualiza\ListaPreciosActualiza.xml'],
                /*OITM 2*/ ['icon' => 'bi-box-seam icon-articulos',         'titulo' => 'Artículos',               'texto' => 'Sincroniza todos los artículos.',                    'servicio' => 'Articulos',               'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Articulos.xml',                                                 'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Articulos_Actualiza\Articulos_Actualiza.xml'],
                /*ITM1 2*/ ['icon' => 'bi-cash-stack icon-precios',         'titulo' => 'Lista de Precios',        'texto' => 'Sincroniza los precios de lista.',                   'servicio' => 'Lista_Precios',           'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Precios\Precios.xml',                 'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Precios_Actualiza\Precios_Actualiza.xml'],
                /*OCRD 2*/ ['icon' => 'bi-people-fill icon-clientes',       'titulo' => 'Lista de Clientes',       'texto' => 'Sincroniza la lista de Clientes.',                   'servicio' => 'Clientes',                'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Clientes.xml',                                                  'ruta2'    => 'C:\Users\KOM090\Documents\Nueva\Clientes_Actualiza.xml',],
                /*CRD1 2*/ ['icon' => 'bi-geo-alt-fill icon-direcciones',   'titulo' => 'Direcciones de Clientes', 'texto' => 'Sincroniza la dirección de Clientes.',               'servicio' => 'Direcciones',             'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Clientes_Direcciones\Clientes_Direcciones.xml',                 'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Clientes_Direcciones_Actualiza\Clientes_Direcciones_Actualiza.xml'],
                /*OEDG 2*/ ['icon' => 'bi-tags-fill icon-descuentos',       'titulo' => 'Grupos de Descuentos',    'texto' => 'Sincroniza grupos de descuentos.',                   'servicio' => 'Grupo_Descuentos',        'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Descuentos\Descuentos.xml',          'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Descuentos_Actualiza\Descuentos_Actualiza.xml'],
                /*EDG1 5*/ ['icon' => 'bi-percent icon-descuento',          'titulo' => 'Descuentos',              'texto' => 'Sincroniza los descuentos.',                         'servicio' => 'Descuentos_Detalle',      'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Descuentos_Cantidad_Actualiza_',                       'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Descuentos_Cantidad_Actualiza\Descuentos_Cantidad_Actualiza.xml'],
                /*OSLP 1*/ ['icon' => 'bi-person-lines-fill icon-vendedor', 'titulo' => 'Vendedores',              'texto' => 'Sincroniza los vendedores.',                         'servicio' => 'Vendedores',              'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Vendedores.xml',                   ],
                /*stock2*/ ['icon' => 'bi bi-graph-up icon-stock',          'titulo' => 'Stock',                   'texto' => 'Sincroniza el stock de articulos.',                  'servicio' => 'Stock',                   'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Stock\Stock.xml',                                         'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Stock_Actualiza\StockActualiza.xml'],
                /*CotSt2*/ ['icon' => 'bi-check-circle icon-cotEstatus',    'titulo' => 'Estado Cotizacion',       'texto' => 'Sincroniza el estado de las cotizaciones.',          'servicio' => 'CotizacionEstatus',       'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Estatus_Cotizaciones\Estatus_Cotizaciones.xml',                 'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Estatus_Cotizaciones_Actualiza\Estatus_Cotizaciones_Actualiza.xml'],
                /*PedSt2*/ ['icon' => 'bi-check2-circle icon-pedEstatus',   'titulo' => 'Estado Pedido',           'texto' => 'Sincroniza el estado de los pedidos.',               'servicio' => 'PedidoEstatus',           'ruta1' => 'C:\Users\KOM090\Documents\Nueva\Estatus_Pedidos\Estatus_Pedidos.xml',                   'ruta2' => 'C:\Users\KOM090\Documents\Nueva\Estatus_Pedidos_Actualiza\Estatus_Pedidos_Actualiza.xml'],
--}}

    
