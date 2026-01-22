@extends('layouts.app')

@section('title', 'Pedido')

@section('contenido')

<!-- Importación de datos de preseleccionados entre la vista y el javascript -->
<script>
window.preseleccionadoCliente = @json($preseleccionados['cliente'] ?? null);
window.preseleccionadoClienteText = @json( $preseleccionados['cliente'] ? ($clienteBase->CardCode.' - '.$clienteBase->CardName) : null );
window.preseleccionadoClientePhone = @json($cotizacion->Phone1 ?? $pedido->Phone1 ?? '');
window.preseleccionadoClienteEmail = @json($cotizacion->E_Mail ?? $pedido->E_Mail ?? '');
window.preseleccionadoClienteCardName = @json($clienteBase?->CardName ?? '');
window.preseleccionadoClienteDescuentos = @json($cotizacion->descuentos ?? $pedido->descuentos ?? []);
window.preseleccionadoClienteDireccionFiscal = @json($cotizacion->Address ?? $pedido->Address ?? '');
window.preseleccionadoClienteDireccionEntrega = @json($cotizacion->Address2 ?? $pedido->Address2 ?? '');
</script>

<!-- Importación de JS y CSS -->
@vite(['resources/js/cotizaciones.js', 'resources/css/formulario.css'])

@php
    // Evitar errores si no existen variables
    $pedido = $pedido ?? (object)[
        'DocEntry' => null,
        'DocDate' => now()->format('Y-m-d'),
        'DocDueDate' => now()->addDay()->format('Y-m-d'),
        'Phone1' => '',
        'E_Mail' => '',
        'Address' => '',
        'Address2' => ''
    ];
    $modo = $modo ?? 0;
@endphp

<div class="container my-4">
    <h3 class="mb-3 fw-bold">
        @if($modo == 0)
            Nuevo Pedido
        @else
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center">
                    {{-- PE principal --}}
                    <span class="fw-semibold">
                        PE - {{ $pedido->DocEntry }}
                    </span>

                    {{-- Estado (solo si no es cliente) --}}
                    @if(Auth::user()->rol_id != 3)
                        @if($pedido->DocStatus == 'A')
                            <i class="bi bi-unlock-fill text-success ms-2" title="Pedido abierto"></i>
                        @else
                            <i class="bi bi-lock-fill text-danger ms-2" title="Pedido cerrado"></i>
                        @endif
                    @endif
                </div>

                {{-- Número SAP más pequeño y debajo, sin cambiar colores --}}
                @if(Auth::user()->rol_id != 3 && $pedido->DocNum)
                    <span class="ms-1" style="font-size: 1.15rem;">
                        SAP - {{ $pedido->DocNum }}
                    </span>
                @endif
            </div>
        @endif
    </h3>

    <div class="row">
        <!-- COLUMNA IZQUIERDA: Datos del Cliente -->
        <div class="col-md-6">
            <h4>CLIENTES</h4>
            <div class="mb-3">
                <label>Cliente</label>
                <select class="form-select" name="cliente" id="selectCliente" @if($modo == 1 || in_array(Auth::user()->rol_id, [3])) disabled @endif >
                    <option value="" selected disabled>Selecciona un cliente...</option>
                </select>
            </div>

            <!-- Info Cliente -->
            <div id="infoCliente" class="mt-2" style="font-size: 14px;">
                <span id="telefono">{{ $pedido->Phone1 }}</span><br>
                <span id="correo">
                    @php
                        $email = $pedido->E_Mail ?: '';
                        if ($email) {
                            $emails = array_map('trim', explode(',', $email));
                            echo '<ul style="padding-left: 20px; margin: 0;">';
                            foreach ($emails as $e) echo "<li>{$e}</li>";
                            echo '</ul>';
                        }
                    @endphp
                </span><br>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label>Dirección Fiscal</label>
                    <span id="direccionFiscal" class="form-control" style="white-space: pre-wrap; display: block;">
                        {{ $pedido->Address }}
                    </span>
                </div>

                <div class="col-md-6">
                    <label>Dirección de Entrega</label>
                    <span id="direccionEntrega" class="form-control" style="white-space: pre-wrap; display: block;">
                        {{ $pedido->Address2 }}
                    </span>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="col-md-6">
            <h5>GENERALES</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Fecha de Creación</label>
                    <input type="date" id="fechaCreacion" class="form-control" value="{{ $pedido->DocDate }}" readonly>
                </div>
                <div class="col-md-4">
                    <label>Válido Entrega</label>
                    <input type="date" id="fechaEntrega" class="form-control" 
                           value="{{ $pedido->DocDueDate }}" 
                           @if($modo == 1) readonly @endif>
                </div>
            </div>

            <div class="mb-3">
                <label>Moneda</label>
                <select class="form-select" name="currency_id" id="selectMoneda"
                    data-monedas='@json($monedas)' data-iva='@json($IVA)'
                    @if($modo == 1 || in_array(Auth::user()->rol_id, [3,4])) disabled @endif>
                    <option value="" selected disabled>Selecciona una moneda</option>
                    @foreach($monedas as $moneda)
                        <option 
                            value="{{ $moneda->Currency_ID }}" 
                            @if($moneda->cambios->isEmpty()) disabled @endif
                            @if(($preseleccionados['moneda'] ?? '') == $moneda->Currency_ID) selected @endif>
                            {{ $moneda->Currency.' - '.$moneda->CurrName }}
                            @if($moneda->cambios->isEmpty() && $modo == 0)
                                (Sin tipo de cambio)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

                <div class="mb-3" @if(Auth::user()->rol_id == 3) hidden @endif>
                    <label>Vendedor</label>
                    <select class="form-select" name="vendedor_SlpCode" id="selectVendedor"
                        @if($modo == 1 || Auth::user()->rol_id == 4) disabled @endif>
                        <option value="" selected disabled>Selecciona un vendedor</option>
                        @foreach($vendedores as $vendedor)
                            <option 
                                value="{{ $vendedor->SlpCode }}"
                                data-SlpName="{{ $vendedor->SlpName }}"
                                @if(($preseleccionados['vendedor'] ?? '') == $vendedor->SlpCode) selected @endif>
                                {{ $vendedor->SlpName }}
                            </option>
                        @endforeach
                    </select>
                </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#contenido">Pedido</a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="contenido">
            @include('components.cot_contenido', ['articulos' => $articulos])
        </div>
    </div>

    @if($modo == 1 &&  isset($pedido->lineas[0]->BaseEntry))
        <span>
            Este pedido tiene relación con la
            <a href="{{ route('detalles', ['id' => $pedido->lineas[0]->BaseEntry]) }}">
                Cotización {{ $pedido->lineas[0]->BaseEntry }}
            </a>
        </span>
    @endif
</div>


{{--FORMULARIO para mnadar los valores a guarda el pedido --}}  
<form id="formCotizacionPedido" action="{{ route('PedidoSave') }}" method="POST">
    @csrf
    <input type="hidden" name="cliente" id="aux" value="{{ $preseleccionados['BaseEntry'] ?? ''}}">
    <input type="hidden" name="BaseEntry" id="BaseEntryP" value="{{ $preseleccionados['BaseEntry'] ?? ''}}">
    <input type="hidden" name="cliente" id="clienteP">
    <input type="hidden" name="fechaCreacion" id="fechaCreacionP">
    <input type="hidden" name="fechaEntrega" id="fechaEntregaP">
    <input type="hidden" name="CardName" id="CardNameP">
    <input type="hidden" name="SlpCode" id="SlpCodeP">
    <input type="hidden" name="phone1" id="phone1P">
    <input type="hidden" name="email" id="emailP">
    <input type="hidden" name="DocCur" id="DocCurP">
    <input type="hidden" name="ShipToCode" id="ShipToCodeP">
    <input type="hidden" name="PayToCode" id="PayToCodeH">
    <input type="hidden" name="direccionFiscal" id="direccionFiscalP">
    <input type="hidden" name="direccionEntrega" id="direccionEntregaP">
    <input type="hidden" name="TotalSinPromo" id="TotalSinPromoP">
    <input type="hidden" name="Descuento" id="DescuentoP">
    <input type="hidden" name="Subtotal" id="SubtotalP">
    <input type="hidden" name="iva" id="ivaP">
    <input type="hidden" name="total" id="totalP">
    <input type="hidden" name="articulos" id="articulosP">
    <input type="hidden" name="comentarios" id="comentariosP">
</form>

<div class="container my-4 d-flex justify-content-start gap-2">
    @if($modo == 0){{--Cuando es modo nuevo que es de creacion del pedido se tiene los botones de cancelar y crear pedido--}}
        <button type="button" class="btn btn-danger" onclick="window.location='{{ url('/') }}'">
            <i class="bi bi-x-circle"></i> Cancelar
        </button>

        @php //se verifica si existen modenas con su respectivo combio del dia 
            $monedaSinCambio = collect($monedas)->every(fn($m) => $m->cambios->isEmpty());
        @endphp

        @if($monedaSinCambio) {{--verificamos la existencia de monedas del dia si es verdadero no existen monedas y se procede a deshabilñitar el boton de crear pedido y se muestra un mensaje
        con el error --}}
            <div class="d-inline-block position-relative">
                <button class="btn btn-success" disabled><i class="bi bi-bag"></i> Crear Pedido</button>
                <small class="mensaje-cambio text-danger">
                    ⚠️ No existen tipos de cambio registrados para hoy. Contacte a soporte.
                </small>
            </div>
        @else {{--si si existen monedas se habilita el boton de crear pedido--}}
            <button type="button" id="btnPedido" class="btn btn-success">
                <i class="bi bi-save"></i> Crear Pedido
            </button>
        @endif
    @else {{--Cuando es modo 1 que es de detalles solo se tiene disponible el boton de pdf--}}
        <button type="button" class="btn btn-danger" onclick="window.open('{{ route('pedido.pdf', $pedido->DocEntry) }}', '_blank')">
            <i class="bi bi-filetype-pdf"></i> PDF
        </button>
    @endif
</div>

<style>
    .mensaje-cambio {
        position: absolute;
        top: 105%;
        left: 0;
        background: rgba(255, 245, 245, 0.95);
        border: 1px solid #dc3545;
        border-radius: 10px;
        padding: 6px 10px;
        font-size: 0.85rem;
        color: #dc3545;
        font-weight: 500;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        white-space: nowrap;
        z-index: 10;
        opacity: 0;
        transform: translateY(5px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .d-inline-block:hover .mensaje-cambio {
        opacity: 1;
        transform: translateY(0);
    }
</style>

@endsection
