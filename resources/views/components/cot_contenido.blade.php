<!-- Scripts necesarios -->
<script>
    window.ROL_USUARIO = {{ Auth()->user()->rol_id }};
</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>


<div class="table-responsive table-scroll">
    <table class="table table-bordered" id="tablaArticulos">
        <thead>
            <tr>
                @if(isset($modo) && $modo == 0)<th></th>@endif
                <th>Clave</th>
                <th>Producto</th>
                <th>Imagen</th>
                <th style="text-align: center">Unidad de medida</th>
                <th>Precio</th>
                <th>Moneda</th>
                <th>Impuesto</th>
                <th style="text-align: center">Cantidad</th>
                <th>Promociones</th>
                <th>SubTotal</th>
                <th style="text-align: center">% Descuento</th>
                <th style="text-align: center">Descuento del</th>
                <th style="text-align: center">Total (doc)</th>
            </tr>
        </thead>

        <tbody>
            {{-- Modo 1: Esto es solo para poder ver los detalles de una cotizacion o pedido y se muestran todos los datos de la base de datos--}}
             @if(isset($modo) && $modo == 1)
                @php
                    // Determinar qué líneas usar: cotización o pedido
                    $lineas = collect(); // colección vacía por defecto
                    
                    if(isset($cotizacion) && $cotizacion->lineas) {
                        $lineas = $cotizacion->lineas;
                    } elseif( isset($pedido) && $pedido->lineas) {
                        $lineas = $pedido->lineas;
                    }
                @endphp

                @foreach($lineas as $linea)
                    <tr data-BaseLine="{{ $linea->BaseLine }}"> {{--Para cotizaciones existentes manden el BaseLine--}}
                        <td class="itemcode">{{ $linea->ItemCode }}</td>
                        <td class="frgnName">{{ $linea->U_Dscr ?? '' }}</td>
                        <td class="imagen" data-imagen="{{$linea->Id_imagen}}">
                            <img src="{{ $linea->imagen?->Ruta_imagen ?? asset('images/default.png') }}" alt="Imagen" style="width: 50px; height: auto;">
                        </td>
                        <td class="medida">{{ $linea->unitMsr2 ?? '' }}</td>
                        <td class="precio">{{ number_format($linea->Price ?? 0, 2, '.', '') }}</td> {{--con '.', '' se forza al formato de numero a que se use . para serapar decimales y ''para quitar  ,--}}
                        <td class="moneda">
                            @if(isset($cotizacion))
                                {{ $monedas->firstWhere('Currency_ID', $cotizacion->DocCur)->Currency ?? '' }}
                            @elseif(isset($pedido))
                                {{ $monedas->firstWhere('Currency_ID', $pedido->DocCur)->Currency ?? '' }}
                            @endif
                        </td>
                        <td class="ivaPorcentaje">IVA {{ number_format($linea->impuesto->Rate, 0, '.', '') }} %</td> 
                        <td>{{number_format($linea->Quantity,0)}} <input type="hidden" value="{{number_format($linea->Quantity,0 , '.', '')}}" min="1" class="form-control form-control-sm cantidad"></td>
                        <td class="promocion">Promociones</td>
                        <td class="subtotal">${{ number_format($linea->Subtotal, 2, '.', '')}}</td>
                        <td class="descuentoporcentaje" readonly>{{ number_format($linea->DiscPrcnt, 0, '.', '') }} %</td>
                        <td class="desMoney" readonly>${{ number_format($linea->Descuento, 2, '.', '') }}</td>
                        <td class="totalFinal" readonly>${{ number_format($linea->Total, 2, '.', '') }}</td>
                    </tr>
                @endforeach
                {{--Se agrega este renglon oculto porque cuando se hace nueva se omite el renglo ultimo que es donde esta el botn de agregar articulo y en esta parte se simula ese renglon--}}
                <tr hidden > </tr> 
            @endif

            {{-- Modo 0: agregar artículos automáticamente desde lineasComoArticulos cuando se cuenta con preseleccionados 
            aqui es cuando se abre en modo detalles una cotizacion y se va a editar que se recalculan los datos--}}
            @if(isset($modo) && $modo == 0 && isset($lineasComoArticulos) && count($lineasComoArticulos) > 0)
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const lineas = @json($lineasComoArticulos);
                        lineas.forEach(art => agregarArticulo(art));

                        setTimeout(() => {
        document
            .querySelectorAll('#tablaArticulos tbody tr')
            .forEach(fila => verificarStockFila(fila));
    }, 0);
    
                    });
                </script>
            @endif

            {{-- Modo 0: agregar artículos desde cero cuando es una cotizacion o nuevo pedido--}}
            @if(isset($modo) && $modo == 0)
                <!-- Botón para agregar nuevos artículos -->
                <tr>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalArticulos"  @if($moneda->cambios->isEmpty()) disabled @endif><b>+</b></button>
                    </td>
                    <td colspan="26"></td>
                </tr>
            @endif
        </tbody>

    </table>
</div>

<!-- *************************************************************************************************************************************************** -->
<!-- Totales y comentarios-->
<div class="row mt-3">
    <div class="col-md-6">
        <label for="comentarios" class="form-label">Comentarios:</label>
         <textarea id="comentarios" name="comentarios" class="form-control" rows="4" placeholder="Escribe tus comentarios aquí..." maxlength="254" @if(isset($modo) && $modo == 1) readonly @endif>{{ old('comentarios', $preseleccionados['comentario'] ?? '') }}</textarea>
        <small id="contador" class="text-muted"  @if(isset($modo) && $modo == 1) style="display: none;" @endif> Te quedan {{ 254 - strlen($cotizacion->comment ?? $pedido->comment ?? '') }} caracteres </small>
    </div>
    <div class="col-md-2">
    </div>
    <div class="col-md-4">
        <table class="table">
            <tr>
                <th>Total antes del descuento</th>
                <td id="totalAntesDescuento">
                    @if($modo == 1)
                        {{ number_format($cotizacion->TotalSinPromo ?? $pedido->TotalSinPromo, 2) }} 
                        @if(isset($cotizacion))
                            {{ $monedas->firstWhere('Currency_ID', $cotizacion->DocCur)->Currency ?? '' }}
                        @elseif(isset($pedido))
                            {{ $monedas->firstWhere('Currency_ID', $pedido->DocCur)->Currency ?? '' }}
                        @endif
                    @else
                        <span id="totalAntesDescuento">$0.00</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Descuento del:</th>
                <td id="DescuentoD">
                    @if($modo == 1)
                        {{ number_format($cotizacion->Descuento ?? $pedido->Descuento ?? 0, 2) }} 
                        @if(isset($cotizacion))
                            {{ $monedas->firstWhere('Currency_ID', $cotizacion->DocCur)->Currency ?? '' }}
                        @elseif(isset($pedido))
                            {{ $monedas->firstWhere('Currency_ID', $pedido->DocCur)->Currency ?? '' }}
                        @endif
                    @else
                        <span id="DescuentoD">$0.00</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Total con el descuento</th>
                <td id="totalConDescuento">
                    @if($modo == 1)
                        {{ number_format($cotizacion->Subtotal ?? $pedido->Subtotal ?? 0, 2) }} 
                        @if(isset($cotizacion))
                            {{ $monedas->firstWhere('Currency_ID', $cotizacion->DocCur)->Currency ?? '' }}
                        @elseif(isset($pedido))
                            {{ $monedas->firstWhere('Currency_ID', $pedido->DocCur)->Currency ?? '' }}
                        @endif
                    @else
                        <span id="totalConDescuento">$0.00</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Impuesto (IVA {{ number_format($IVA->Rate,0) }}%)</th>
                <td id="iva">
                    @if($modo == 1)
                        {{ number_format($cotizacion->IVA ?? $pedido->IVA ?? 0, 2) }} 
                        @if(isset($cotizacion))
                            {{ $monedas->firstWhere('Currency_ID', $cotizacion->DocCur)->Currency ?? '' }}
                        @elseif(isset($pedido))
                            {{ $monedas->firstWhere('Currency_ID', $pedido->DocCur)->Currency ?? '' }}
                        @endif
                    @else
                        <span id="iva">$0.00</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Total</th>
                <td id="total">
                    @if($modo == 1)
                        {{ number_format($cotizacion->Total ?? $pedido->Total ?? 0, 2) }}
                        @if(isset($cotizacion))
                            {{ $monedas->firstWhere('Currency_ID', $cotizacion->DocCur)->Currency ?? '' }}
                        @elseif(isset($pedido))
                            {{ $monedas->firstWhere('Currency_ID', $pedido->DocCur)->Currency ?? '' }}
                        @endif
                    @else
                        <span id="total">$0.00</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- *************************************************************************************************************************************************** -->
<!-- Modal Artículos -->
<div class="modal fade" id="modalArticulos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #05564f">
                <h5 class="modal-title">Seleccionar Artículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tabla -->
                <table class="table table-hover" id="tablaModalArticulos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Imagen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articulos as $articuloM)
                            <tr>
                                <td>{{ $articuloM->ItemCode }}</td>
                                <td>{{ $articuloM->FrgnName }}</td>
                                <td>{{ $articuloM->ItemName }}</td>
                                <td>{{ $articuloM->marca->ItmsGrpNam }}</td>
                                <td class="precio-celda" data-precio="{{ $articuloM->precio->Price }}" data-moneda='@json($articuloM->precio->moneda)'> </td>
                               {{-- <td><img src="{{ asset($articuloM->imagen->Ruta_imagen) }}" alt="Imagen" style="width:50px;height:auto;"></td>--}}
                                <td><a data-fancybox href="{{ asset($articuloM->imagen->Ruta_imagen) }}">
                                        <img src="{{ asset($articuloM->imagen->Ruta_imagen) }}" alt="Imagen" style="width:50px; cursor:pointer;">
                                    </a>
                                </td>
                                
                                <td>@if($articuloM->OnHand > 0)<button class="btn" style="background-color: blue; color: white; border: none; padding: 10px 20px; border-radius: 5px;" onclick='agregarArticulo(@json($articuloM))'>Agregar</button>@else<button class="btn" disabled style="background-color: gray; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: not-allowed;">Sin stock</button>@endif</td>
    
                                <!--button class="btn" style="background-color: blue; color: white; border: none; padding: 10px 20px; border-radius: 5px;" onclick='agregarArticulo(@json($articuloM))'>Agregar</!--button-->
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .fancybox__container {
    z-index: 20000 !important;
}
</style>