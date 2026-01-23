@extends('layouts.app')

@section('title', 'Configuración General')

@section('contenido')

<style>
.lista li {
    font-size: 14px; 
    font-family: Arial, sans-serif; 
    line-height: 
}

.lista strong {
    font-size: 16px; 
    font-family: Arial, sans-serif; 
    line-height: 2;       
}
</style>

<div class="container mt-5">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header text-white py-3 d-flex justify-content-between align-items-center" style="background-color: #05564f">
            <h3 class="mb-0 fw-bold">
                <i class="bi bi-gear-fill me-2 text-warning"></i> Configuración General
            </h3>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarConfigModal">
                <i class="bi bi-pencil-square me-1 fw-bold"></i> Editar
            </button>
        </div>

        <div class="card-body p-4">
            <div class="row align-items-center">
                <!-- Información -->
                <div class="col-md-8">
                    <h2 class="fw-bold text-primary mb-3">
                        <i class="bi bi-building me-2"></i> {{ $configuracion->nombre_empresa }}
                    </h2>
                    <ul class="list-unstyled text-muted lista">
                        <li class="mb-2"><i class="bi bi-percent text-primary me-2"></i><strong>IVA:</strong> {{ $configuracion->iva }}%</li>
                        <li class="mb-2"><i class="bi bi-signpost-split text-success me-2"></i><strong>Calle:</strong> {{ $configuracion->calle }}</li>
                        <li class="mb-2"><i class="bi bi-geo-alt-fill text-danger me-2"></i><strong>Colonia:</strong> {{ $configuracion->colonia }}</li>
                        <li class="mb-2"><i class="bi bi-mailbox text-info me-2"></i><strong>C.P.:</strong> {{ $configuracion->CP }}</li>
                        <li class="mb-2"><i class="bi bi-building-check text-warning me-2"></i><strong>Ciudad:</strong> {{ $configuracion->ciudad }}</li>
                        <li class="mb-2"><i class="bi bi-globe2 text-dark me-2"></i><strong>País:</strong> {{ $configuracion->pais }}</li>
                        <li class="mb-2"><i class="bi bi-telephone-fill text-success me-2"></i><strong>Teléfono:</strong> {{ $configuracion->telefono }}</li>
                        <li class="mb-2"><i class="bi bi-cash-coin text-warning me-2"></i><strong>Moneda Principal:</strong> 
                           {{ $configuracion->monedaPrincipal ? $configuracion->monedaPrincipal->Currency . ' - ' . $configuracion->monedaPrincipal->CurrName : 'No asignada' }}
                        </li>
                    </ul>
                </div>

                <!-- Logo empresa-->
                <div class="col-md-4 text-center">
                    @if($configuracion->ruta_logo_empresa)
                        <div class="bg-light rounded-3 shadow-sm "><a data-fancybox href="{{ asset('storage/' . $configuracion->ruta_logo_empresa) }}"> 
                            <img src="{{ asset('storage/' . $configuracion->ruta_logo_empresa) }}" alt="Logo" class="img-fluid" style="width: 100%;  height: 100%; cursor:pointer;"></a>
                        </div>
                    @else
                        <div class="border rounded-3 py-5 text-muted bg-light shadow-sm">
                            <i class="bi bi-image-alt fs-1 d-block mb-2"></i>
                            <span>Sin Logo Empresarial</span>
                        </div>
                    @endif
                    <br>

                    @if($configuracion->ruta_logo_principal)
                        <div class="bg-light rounded-3 shadow-sm "><a data-fancybox href="{{ asset('storage/' . $configuracion->ruta_logo_principal) }}"> 
                            <img src="{{ asset('storage/' . $configuracion->ruta_logo_principal) }}" alt="Logo" height="100" class="rounded shadow-sm border" style="hcursor:pointer;"></a>
                        </div>
                    @else
                        <div class="border rounded-3 py-5 text-muted bg-light shadow-sm">
                            <i class="bi bi-image-alt fs-1 d-block mb-2"></i>
                            <span>Sin Logo Principal</span>
                        </div>
                    @endif
                    <br>

                    @if($configuracion->ruta_logo_login)
                        <div class="bg-light rounded-3 shadow-sm "><a data-fancybox href="{{ asset('storage/' . $configuracion->ruta_logo_login) }}"> 
                            <img src="{{ asset('storage/' . $configuracion->ruta_logo_login) }}" alt="Logo" height="100" class="rounded shadow-sm border" style="hcursor:pointer;"></a>
                        </div>
                    @else
                        <div class="border rounded-3 py-5 text-muted bg-light shadow-sm">
                            <i class="bi bi-image-alt fs-1 d-block mb-2"></i>
                            <span>Sin Logo del Login</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
<!-- *********************************************************************************************************************************** -->
<!-- Modal para editar -->
<div class="modal fade" id="editarConfigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-3 border-0 shadow-lg">
      <form action="{{ route('GuardarConfig', $configuracion->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="modal-header text-white" style="background-color: #05564f">
              <h5 class="modal-title fw-bold"><i class="bi bi-pencil-fill me-2 text-warning"></i> Editar Configuración</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body p-4">

            <div class="mb-3">
                <label for="nombre_empresa" class="form-label fw-bold"><i class="bi bi-building me-1"></i> Nombre de la Empresa</label>
                <input type="text" name="nombre_empresa" id="nombre_empresa" class="form-control" 
                        value="{{ old('nombre_empresa', $configuracion->nombre_empresa) }}">
            </div>


            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="iva" class="form-label fw-bold"><i class="bi bi-percent me-1"></i> IVA (%)</label>
                    <select name="iva" id="iva" class="form-select">
                        <option value="" disabled selected>Selecciona un impuesto</option>
                        @foreach($impuestos as $iva)
                            <option value="{{ $iva->Code }}"
                                @if(old('iva', $configuracion->iva) == $iva->Code) selected @endif>
                                {{ $iva->Code.' - '.$iva->Name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="telefono" class="form-label fw-bold"><i class="bi bi-telephone-fill me-1"></i> Teléfono</label>
                    <input type="text" name="telefono" id="telefono" class="form-control"
                            value="{{ old('telefono', $configuracion->telefono) }}"
                            minlength="10" maxlength="10" pattern="\d{10}"
                            title="Debe contener exactamente 10 dígitos numéricos">
                </div>
            </div>


            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="calle" class="form-label fw-bold"><i class="bi bi-signpost-split me-1"></i> Calle</label>
                    <input type="text" name="calle" id="calle" class="form-control" value="{{ old('calle', $configuracion->calle) }}">
                </div>
                <div class="col-md-6">
                    <label for="colonia" class="form-label fw-bold"><i class="bi bi-geo-alt-fill me-1"></i> Colonia</label>
                    <input type="text" name="colonia" id="colonia" class="form-control" value="{{ old('colonia', $configuracion->colonia) }}">
                </div>
            </div>


            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="CP" class="form-label fw-bold"><i class="bi bi-mailbox me-1"></i> Código Postal</label>
                    <input type="text" name="CP" id="CP" class="form-control" value="{{ old('CP', $configuracion->CP) }}">
                </div>
                <div class="col-md-4">
                    <label for="ciudad" class="form-label fw-bold"><i class="bi bi-building-check me-1"></i> Ciudad</label>
                    <input type="text" name="ciudad" id="ciudad" class="form-control" value="{{ old('ciudad', $configuracion->ciudad) }}">
                </div>
                <div class="col-md-4">
                    <label for="pais" class="form-label fw-bold"><i class="bi bi-globe2 me-1"></i> País</label>
                    <input type="text" name="pais" id="pais" class="form-control" value="{{ old('pais', $configuracion->pais) }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="MonedaPrincipal" class="form-label fw-bold">
                        <i class="bi bi-cash-coin me-1"></i> Moneda Principal
                    </label>
                    <select name="MonedaPrincipal" id="MonedaPrincipal" class="form-select">
                        <option value="" disabled selected>Selecciona una moneda</option>
                        @foreach($monedas as $moneda)
                            <option value="{{ $moneda->Currency_ID }}"
                                @if(old('MonedaPrincipal', $configuracion->MonedaPrincipal) == $moneda->Currency_ID) selected @endif>
                                {{ $moneda->Currency.' - '.$moneda->CurrName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>


            <div class="col-md-8">
                <label for="ruta_logo_empresa" class="form-label fw-bold"><i class="bi bi-image me-1"></i> Logo Empresarial</label>
                <input type="file" name="ruta_logo_empresa" id="ruta_logo_empresa" class="form-control">
                @if($configuracion->ruta_logo_empresa)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $configuracion->ruta_logo_empresa) }}" alt="Logo" height="80" class="rounded shadow-sm border">
                    </div>
                @endif
            </div><br>

            <div class="col-md-8">
                <label for="ruta_logo_principal" class="form-label fw-bold"><i class="bi bi-image me-1"></i> Logo Principal</label>
                <input type="file" name="ruta_logo_principal" id="ruta_logo_principal" class="form-control">
                @if($configuracion->ruta_logo_principal)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $configuracion->ruta_logo_principal) }}" alt="Logo" height="80" class="rounded shadow-sm border">
                    </div>
                @endif
            </div><br>

            <div class="col-md-8">
                <label for="ruta_logo_login" class="form-label fw-bold"><i class="bi bi-image me-1"></i> Logo Login</label>
                <input type="file" name="ruta_logo_login" id="ruta_logo_login" class="form-control">
                @if($configuracion->ruta_logo_login)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $configuracion->ruta_logo_login) }}" alt="Logo" height="80" class="rounded shadow-sm border">
                    </div>
                @endif
            </div>
        </div>

          <div class="modal-footer">
              <button type="button" class="btn btn-danger fw-bold" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </button>

              <button type="submit" class="btn btn-success fw-bold">
                  <i class="bi bi-save-fill me-1"></i> Guardar
              </button>
          </div>

      </form>

    </div>
  </div>
</div>
@endsection
