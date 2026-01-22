//NO MOVERLE SI FUNCIONA Y NO SE COMO, CUALQUIER INTENTO DE MOVERLE VAS A LLORAR POR 1 CENTAVO.
//Aqui se maneja todo lo que tiene que ver con cotizaciones y pedidos ya que comparten misma logica esta parte es muy sensible ya que hace las cuentas matematicas
const rolUsuario = window.ROL_USUARIO ?? null;
const rolesConStock = [1, 2, 4];

// ================================
// FECHA MÍNIMA PARA ENTREGA
// ================================

// Obtenemos la fecha del día de hoy
const hoy = new Date().toISOString().split('T')[0];

// Evitar que el calendario permita escoger días pasados
document.getElementById('fechaEntrega').setAttribute('min', hoy);

// ================================
// DATOS DE MONEDA Y IVA
// ================================

// Recibe el dato de monedas desde el controlador (JSON en dataset)
const monedas = JSON.parse(selectMoneda.dataset.monedas);

// Recibe el IVA desde el controlador
const IVA = JSON.parse(selectMoneda.dataset.iva);

// ================================
// SELECT CLIENTE CON BUSQUEDA
// ================================
$(document).ready(function() {
    const $select = $('#selectCliente');

    $('#selectCliente').select2({
        placeholder: "Selecciona un cliente",
        allowClear: true,
        width: '100%',
        ajax: {
            url: '/clientes/buscar',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;

                const items = data.items.map(cli => ({
                    id: cli.id,
                    text: cli.text,
                    cardname: cli.cardname,
                    phone: cli.phone || 'Sin teléfono',
                    email: cli.email || 'Sin correo',
                    descuentos: cli.descuentos || [],
                    active: cli.active
                }));

                // Crear manualmente <option> para cada resultado, con data-descuentos
                items.forEach(cli => {
                    const opt = new Option(cli.text, cli.id);
                    $(opt).attr('data-phone', cli.phone);
                    $(opt).attr('data-email', cli.email);
                    $(opt).attr('data-cardname', cli.cardname);
                    $(opt).attr('data-descuentos', JSON.stringify(cli.descuentos));
                    $select.append(opt);
                });

                return {
                    results: items,
                    pagination: { more: data.more }
                };
            },
            cache: true
        },
        minimumInputLength: 0,
        templateResult: repo => repo.loading ? "Cargando..." : repo.text,
        templateSelection: repo => repo.text || repo.id
    });

    if (window.preseleccionadoCliente) {
        const $select = $('#selectCliente');
        
        // 🔹 Primero, pedimos los datos reales del cliente al backend
        $.ajax({
            url: '/clientes/buscar',
            data: { q: window.preseleccionadoCliente },
            dataType: 'json'
        }).then(function(data) {
            // Buscamos el cliente exacto en los resultados
            const cliente = data.items.find(c => String(c.id) === String(window.preseleccionadoCliente));

            // Si no lo encontró (por si el backend paginó), usamos los valores de respaldo
            const descuentosData = cliente ? (cliente.descuentos || []) : (window.preseleccionadoClienteDescuentos || []);

            // Crear opción con datos reales
            const option = new Option(
                window.preseleccionadoClienteText,
                window.preseleccionadoCliente,
                true,
                true
            );

            // Guardar todos los atributos
            $(option).attr({
                'data-phone': cliente?.phone || window.preseleccionadoClientePhone || '',
                'data-email': cliente?.email || window.preseleccionadoClienteEmail || '',
                'data-cardname': cliente?.cardname || window.preseleccionadoClienteCardName || '',
                'data-descuentos': JSON.stringify(descuentosData),
                'data-direccionFiscal': window.preseleccionadoClienteDireccionFiscal || '',
                'data-direccionEntrega': window.preseleccionadoClienteDireccionEntrega || ''
            });

            // Agregar al select y actualizar Select2
            $select.append(option).trigger('change.select2');

            // Refrescar datos internos de Select2
            $select.select2('data', [{
                id: window.preseleccionadoCliente,
                text: window.preseleccionadoClienteText,
                cardname: cliente?.cardname || window.preseleccionadoClienteCardName,
                phone: cliente?.phone || window.preseleccionadoClientePhone || 'Sin teléfono',
                email: cliente?.email || window.preseleccionadoClienteEmail || 'Sin correo',
                descuentos: descuentosData,
                direccionFiscal: window.preseleccionadoClienteDireccionFiscal || '',
                direccionEntrega: window.preseleccionadoClienteDireccionEntrega || ''
            }]);

            // 🔹 Actualiza la vista y aplica descuentos
            actualizarDatosCliente();
            setTimeout(() => {
                $('#selectCliente').trigger('change');
            }, 150);
        });
    }

    // Cargar primera página al abrir sin escribir
    $('#selectCliente').on('select2:open', function() {
        if (!$('.select2-results__option').length) {
            $(this).data('select2').trigger('query', { term: '', page: 1 });
        }
    });
});


// ================================
// ACTUALIZAR DATOS DEL CLIENTE
// ================================
function actualizarDatosCliente() {
    const cliente = $('#selectCliente').select2('data')[0]; // obtiene el cliente seleccionado
    if (!cliente) return;

    // Si es un <option>, saca los data-* desde dataset
    const el = cliente.element || cliente; // soporte por si viene del objeto interno
    const phone = el.dataset?.phone || 'Sin teléfono';
    const email = el.dataset?.email || 'Sin correo';
    const cardCode = cliente.id || el.value;

    let emailFormatted = email.split(',').join('<br>');
    if (email !== 'Sin correo') {
        let emails = email.split(',').map(e => `<li>${e.trim()}</li>`).join('');
        emailFormatted = `<ul style="padding-left: 20px; margin: 0;">${emails}</ul>`;
    }

    $('#telefono').text("Teléfono: " + phone);
    $('#correo').html("Correos:<br>" + emailFormatted);

    // 🔹 Traer direcciones vía AJAX
    $.ajax({
        url: `/Cotizaciones/cliente/${cardCode}/direcciones`,
        type: 'GET',
        success: function (data) {
            $('#direccionFiscal').text(data.fiscal);
            $('#direccionEntrega').text(data.entrega);
        },
        error: function () {
            alert('No se pudieron obtener las direcciones del cliente.');
        }
    });
}


// Ejecutar cuando cambie el select del cliente
$('#selectCliente').on('change', actualizarDatosCliente);

// Ejecutar al cargar la página si ya hay un cliente preseleccionado
$(document).ready(function () {
    if ($('#selectCliente').val()) {
        actualizarDatosCliente();
    }
});

// Script basico de inicializacion del select2 para poder buscar un vendedor dentro del select 
//solo busca con los datos ya cargados no esta paginado ya que son pocos vendedores
$(document).ready(function() {
    $('#selectVendedor').select2({
        placeholder: "Selecciona un vendedor",
        allowClear: true,
        width: '100%' 
    });
});

// ================================
// ACTUALIZAR DATOS DE LA MONEDA
// ================================
function actualizarDatosMoneda(){
    const monedaCambioID = parseInt(this.value);
    const monedaCambio = monedas.find(m => m.Currency_ID == monedaCambioID);

    // Recorremos todas las filas de la tabla y actualizamos el precio y la moneda
    const filas = document.querySelectorAll("#tablaArticulos tbody tr");
    filas.forEach(fila => {
        if (!fila.dataset.precioOriginal || !fila.dataset.monedaOriginal) return;

        const precioOriginal = parseFloat(fila.dataset.precioOriginal);
        const monedaOriginal = JSON.parse(fila.dataset.monedaOriginal);

        // Calculamos el precio convertido
        const precioConvertido = conversionesMonedas(precioOriginal, monedaOriginal, monedaCambio);
        fila.querySelector('.precio').textContent = precioConvertido.toFixed(2);

        // Actualizamos la columna de moneda
        fila.querySelector('.moneda').textContent = monedaCambio ? monedaCambio.Currency : monedaOriginal.Currency;
    });

    // Recalculamos totales
    calcularTotales();
}

// Ejecutar cuando cambie el select de moneda
$('#selectMoneda').on('change', actualizarDatosMoneda);

// ================================
// ACTUALIZAR DESCUENTOS SEGÚN CLIENTE
// ================================
$('#selectCliente').on('change', function() {
    const clienteOption = this.options[this.selectedIndex];
    const descuentosCliente = clienteOption ? JSON.parse(clienteOption.dataset.descuentos || '[]') : [];

    // Recorremos las filas de artículos
    const filas = document.querySelectorAll("#tablaArticulos tbody tr");
    filas.forEach(fila => {
        const itmsGrpCod = fila.dataset.itmsGrpCod;
        if (!itmsGrpCod) return;

        // Buscar descuento por grupo
        const detalle = descuentosCliente.find(det => String(det.ObjKey) === String(itmsGrpCod));
        const descuento = detalle ? parseFloat(detalle.Discount) : 0;

        fila.querySelector('.descuentoporcentaje').textContent = `${descuento} %`;
        calcularTotales();
    });
});

// ================================
// VERIFICACION DE STOCK POR LINEA
// ================================

window.verificarStockFila = function (fila) {
    if (!rolesConStock.includes(rolUsuario)) return;

    const cantidad = Number(fila.querySelector('.cantidad')?.value || 0);
    const itemCode = fila.querySelector('.itemcode')?.textContent?.trim();
    const icono = fila.querySelector('.stock-icon');

    if (!itemCode || !icono) return;

    icono.textContent = '⏳';

    $.ajax({
        url: `/Pedidos/Articulo/stock`,
        type: 'POST',
        data: {
            articulos: [{
                itemCode: itemCode,
                cantidad: cantidad
            }]
        },
        success: function (response) {
            if (response.success) {
                icono.textContent = '✔';
                icono.style.color = 'green';
            } else {
                icono.textContent = '✖';
                icono.style.color = 'red';
            }
        },
        error: function () {
            icono.textContent = '✖';
            icono.style.color = 'red';
        }
    });
}



// ================================
// AGREGAR ARTÍCULO A LA TABLA
// ================================
window.agregarArticulo = function(art) {
    const tabla = document.querySelector("#tablaArticulos tbody");
    const fila = document.createElement("tr");

    const cantidad = art.Quantity ? Number(art.Quantity) : 1;

    // Guardamos info útil en data-attributes
    fila.dataset.precioOriginal = art.precio.Price;
    fila.dataset.monedaOriginal = JSON.stringify(art.precio.moneda);
    fila.dataset.itmsGrpCod = art.ItmsGrpCod;
   if (art.BaseLine !== undefined && art.BaseLine !== null) {
        fila.dataset.baseline = art.BaseLine;
    }  
    fila.dataset.itemcode = art.ItemCode;

    const monedaCambioID = parseInt(document.querySelector('select[name="currency_id"]').value);
    const monedaCambio = monedas.find(m => m.Currency_ID == monedaCambioID);
    const precio = conversionesMonedas(art.precio.Price, art.precio.moneda, monedaCambio);

    // Descuento según cliente y grupo de artículo
    const clienteSelect = document.getElementById('selectCliente');
    const clienteOption = clienteSelect.options[clienteSelect.selectedIndex];
    const descuentosCliente = clienteOption ? JSON.parse(clienteOption.dataset.descuentos || '[]') : [];
    const detalle = descuentosCliente.find(det => String(det.ObjKey) === String(art.ItmsGrpCod));
    const descuento = detalle ? parseFloat(detalle.Discount) : 0;

    // Construcción del HTML de la fila
    fila.innerHTML = `
        <td><button class="btn btn-sm btn-danger">X</button></td>
        <td class="itemcode" >${art.ItemCode}</td>
        <td class="frgnName">${art.FrgnName}</td>
        <td class="imagen" data-imagen="${art.Id_imagen}"><img src="${art.imagen?.Ruta_imagen}" alt="Imagen" style="width: 50px; height: auto;"></td>
        <td class="medida">${art.SalUnitMsr}</td>
        <td class="precio">${Number(precio || 0).toFixed(2)}</td>
        <td class="moneda">${monedaCambio ? monedaCambio.Currency : art.precio.moneda.Currency}</td>
        <td class="ivaPorcentaje">IVA ${Number(IVA.Rate).toFixed(0)}%</td>
        <td><div class="d-flex align-items-center gap-2"><input type="number" value="${cantidad}" min="1" class="form-control form-control-sm cantidad"> ${rolesConStock.includes(rolUsuario) ? '<span class="stock-icon">⏳</span>' : ''}</div></td>
        <td class="promocion">Promociones</td>
        <td class="subtotal"></td>
        <td class="descuentoporcentaje">${descuento} %</td>
        <td class="desMoney"></td>
        <td class="totalFinal">Total (doc)</td>
    `;

    // Insertamos la fila antes de la última (botón agregar)
    tabla.insertBefore(fila, tabla.lastElementChild);

    // Eventos
    //fila.querySelector('.cantidad').addEventListener('input', calcularTotales);
    const cantidadInput = fila.querySelector('.cantidad');
    cantidadInput.addEventListener('input', function () {
        calcularTotales();
        verificarStockFila(fila);
    });

    fila.querySelector('button').addEventListener('click', function() { eliminarFila(this); });

    calcularTotales();

    // validar al agregar
   setTimeout(() => verificarStockFila(fila), 50);

    // Cerrar modal si está abierto
    const modalEl = document.getElementById('modalArticulos');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
            fila.querySelector('.cantidad')?.focus();
        }
    }
};

// ================================
// ACTUALIZAR PRECIOS
// ================================
function actualizarPrecios() {
    const monedaCambioID = parseInt(document.querySelector('select[name="currency_id"]').value);
    const monedaCambio = monedas.find(m => m.Currency_ID == monedaCambioID);

    document.querySelectorAll(".precio-celda").forEach(cell => {
        const precioOriginal = parseFloat(cell.dataset.precio);
        const monedaOriginal = JSON.parse(cell.dataset.moneda);

        const precioConvertido = conversionesMonedas(precioOriginal, monedaOriginal, monedaCambio);
        const simboloMoneda = monedaCambio ? monedaCambio.Currency : monedaOriginal.Currency;

        cell.innerHTML = `$${Number(precioConvertido).toFixed(2)} ${simboloMoneda}`;
    });
}

document.addEventListener("DOMContentLoaded", actualizarPrecios);
document.querySelector('select[name="currency_id"]').addEventListener("change", actualizarPrecios);

// ================================
// TEXTAREA COMENTARIOS CON LÍMITE DE CARACTERES
// ================================
const textarea = document.getElementById("comentarios");
const contador = document.getElementById("contador");
const limiteCaracteres = 200;

// Inicializa contador
contador.textContent = `Te quedan ${limiteCaracteres} caracteres`;

// Escucha input y corta si excede
textarea.addEventListener("input", function() {
    let texto = this.value;

    if (texto.length > limiteCaracteres) {
        this.value = texto.slice(0, limiteCaracteres);
        texto = this.value;
    }

    const restantes = limiteCaracteres - texto.length;
    contador.textContent = `Te quedan ${restantes} caracteres`;
});

// ================================
// FUNCIONES DE MONEDA Y TOTALES
// ================================

// Convierte precios según tipo de moneda
function conversionesMonedas(precioOriginal, monedaOriginal, monedaConvertir) {
    if(!monedaConvertir) return parseFloat(precioOriginal).toFixed(2);

    let rate = monedaOriginal.cambios[0]?.Rate ?? 1;
    const precioBase = parseFloat(precioOriginal) * rate;
    rate = monedaConvertir.cambios[0]?.Rate ?? 1;

    return precioBase / rate;
}

// Calcular totales generales de la tabla
// Limpia texto para obtener solo números con punto decimal (por si hay símbolos $ o espacios)
function limpiarNumero(texto) {
    return texto.replace(/[^\d.-]/g, '').trim() || '0';
}

// Calcular totales generales de la tabla
function calcularTotales() {
    const filas = document.querySelectorAll("#tablaArticulos tbody tr");
    let totalAntesDescuento = new Decimal(0);
    let totalDescuento = new Decimal(0);
    let totalFinalGeneral = new Decimal(0);
    const moneda = filas[0]?.querySelector('td.moneda')?.textContent?.trim() || '';

    filas.forEach(fila => {
        const cantidad = new Decimal(fila.querySelector(".cantidad")?.value || 0);

        const precioRaw = fila.querySelector(".precio")?.textContent || '0';
        const precio = new Decimal(limpiarNumero(precioRaw));

        const descuentoRaw = fila.querySelector(".descuentoporcentaje")?.textContent.replace('%', '') || '0';
        const descuentoP = new Decimal(limpiarNumero(descuentoRaw));

        // Calculos con decimal.js y redondeo a 2 decimales
        const subtotalRaw = cantidad.times(precio);
        const subtotal = subtotalRaw.toDecimalPlaces(2, Decimal.ROUND_HALF_UP);

        const descuentoMoneyRaw = subtotalRaw.times(descuentoP.dividedBy(100));
        const descuentoMoney = descuentoMoneyRaw.toDecimalPlaces(2, Decimal.ROUND_HALF_UP);

        const totalLineaRaw = subtotalRaw.minus(descuentoMoneyRaw);
        const totalLinea = totalLineaRaw.toDecimalPlaces(2, Decimal.ROUND_HALF_UP);

        // Suma solo valores redondeados
        totalAntesDescuento = totalAntesDescuento.plus(subtotal);
        totalDescuento = totalDescuento.plus(descuentoMoney);
        totalFinalGeneral = totalFinalGeneral.plus(totalLinea);

        // Actualizar celdas en tabla con formato de 2 decimales
        const cells = {
            subtotal: fila.querySelector('.subtotal'),
            desMoney: fila.querySelector('.desMoney'),
            totalFinal: fila.querySelector('.totalFinal')
        };

        if (cells.subtotal) cells.subtotal.textContent = `$${subtotal.toFixed(2)}`;
        if (cells.desMoney) cells.desMoney.textContent = `$${descuentoMoney.toFixed(2)}`;
        if (cells.totalFinal) cells.totalFinal.textContent = `$${totalLinea.toFixed(2)}`;
    });

    // Calcular IVA y total con base en totales redondeados
    const ivaRate =  new Decimal(IVA.Rate);
    const ivaRaw = totalFinalGeneral.times(ivaRate.dividedBy(100));
    const iva = ivaRaw.toDecimalPlaces(2, Decimal.ROUND_HALF_UP);
    const totalConIva = totalFinalGeneral.plus(iva).toDecimalPlaces(2, Decimal.ROUND_HALF_UP);

    // Actualizar totales generales en el DOM
    const setTotal = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = `$${value.toFixed(2)} ${moneda}`;
    };

    setTotal('totalAntesDescuento', totalAntesDescuento);
    setTotal('DescuentoD', totalDescuento);
    setTotal('totalConDescuento', totalFinalGeneral);
    setTotal('iva', iva);
    setTotal('total', totalConIva);
}

// Eliminar fila y recalcular totales
function eliminarFila(boton) {
    boton.closest("tr").remove();
    calcularTotales();
}

// ================================
// FILTROS Y MODAL DE ARTÍCULOS
// ================================
$(document).ready(function() {
    //configuracion 
    var tablaModal = $('#tablaModalArticulos').DataTable({
        pageLength: 25,
        language: { url: '/datatables/i18n/es-ES.json' },
        ordering: false,
        searching: true
    });

    //cuando se cambia de pagina regresa al inicio del modal
    function scrollModalAlInicio() {
        setTimeout(() => $('#modalArticulos .modal-body').scrollTop(0), 50);
    }

    $('#buscadorModal').on('keyup', function() { tablaModal.search(this.value).draw(); scrollModalAlInicio(); });
    $('#filtroMostrar').on('change', function() { tablaModal.page.len($(this).val()).draw(); scrollModalAlInicio(); });
    $('#tablaModalArticulos').on('page.dt', function() { scrollModalAlInicio(); });
    $('#modalArticulos').on('shown.bs.modal', function() { scrollModalAlInicio(); });

    $('#modalArticulos').on('hidden.bs.modal', function () {
        $('#buscadorModal').val('');
        tablaModal.search('').draw();
        $('#filtroMostrar').val('25');
        tablaModal.page.len(25).draw();
        tablaModal.page('first').draw('page');
        scrollModalAlInicio();
    });
});

// ================================
// Validar campos
// ================================
function validar(e) {
    let camposInvalidos = [];

    const $cliente = $("#selectCliente");
    const $moneda = $("#selectMoneda");
    const $vendedor = $("#selectVendedor");

    const clienteVal = $cliente.val();
    const monedaVal = $moneda.val();

    // Validar Cliente
    if (!clienteVal) {
        $cliente.next('.select2').find('.select2-selection').addClass('is-invalid');
        camposInvalidos.push("Cliente");
    } else {
        $cliente.next('.select2').find('.select2-selection').removeClass('is-invalid');
    }

    // Validar Moneda
    if (!monedaVal) {
        $moneda.addClass('is-invalid');
        camposInvalidos.push("Moneda");
    } else {
        $moneda.removeClass('is-invalid');
    }

    // Validar Vendedor (si no está oculto)
    if ($vendedor.is(":visible") && !$vendedor.val()) {
        $vendedor.addClass('is-invalid');
        camposInvalidos.push("Vendedor");
    } else {
        $vendedor.removeClass('is-invalid');
    }

    //  Mostrar alerta si hay campos vacíos
    if (camposInvalidos.length > 0) {
        e.preventDefault();

        Swal.fire({
            icon: "warning",
            title: "Campos incompletos",
            html: `Por favor completa los siguientes campos:<br><b>${camposInvalidos.join(", ")}</b>`,
            confirmButtonText: "Entendido",
            confirmButtonColor: "#d33"
        });

        return false;
    }

    return true; 
}
// ================================
// GUARDAR COTIZACIÓN
// ================================
$("#guardarCotizacion").on("click", function(e) {
    if (!validar(e)) return;

    // Llenar inputs ocultos con valores de la página
    const cliente = $('#selectCliente').select2('data')[0] || {};
    const $option = $('#selectCliente').find(`option[value="${cliente.id}"]`);

    const cardname = cliente.cardname || $option.data('cardname') || '';
    const phone = cliente.phone || $option.data('phone') || '';
    const email = cliente.email || $option.data('email') || '';

    $("#DocEntry_AuxH").val($("h3[data-DocEntryAux]").data("docentryaux"));
    $("#clienteH").val(cliente.id || '');
    $("#fechaCreacionH").val($("#fechaCreacion").val());
    $("#fechaEntregaH").val($("#fechaEntrega").val());
    $("#CardNameH").val(cardname);
    $("#SlpCodeH").val($("#selectVendedor").val());
    $("#phone1H").val(phone);
    $("#emailH").val(email);
    $("#DocCurH").val($("#selectMoneda").val());
    $("#direccionFiscalH").val($("#direccionFiscal").text());
    $("#direccionEntregaH").val($("#direccionEntrega").text());
    $("#TotalSinPromoH").val($("#totalAntesDescuento").text().replace('$', ''));
    $("#DescuentoH").val($("#DescuentoD").text().replace('$', ''));
    $("#SubtotalH").val($("#totalConDescuento").text().replace('$', ''));
    $("#ivaH").val($("#iva").text().replace('$', ''));
    $("#totalH").val($("#total").text().replace('$', ''));
    $("#comentariosH").val($('#comentarios').val());


    // Recopilar artículos
    let articulos = [];
    $("#tablaArticulos tbody tr:not(:last)").each(function() {
        articulos.push({
            itemCode: $(this).find(".itemcode").text(),
            descripcion: $(this).find(".frgnName").text(),
            unidad: $(this).find(".medida").text(),
            precio: $(this).find(".precio").text(),
            descuentoPorcentaje: $(this).find(".descuentoporcentaje").text(),
            cantidad: $(this).find(".cantidad").val(),
            imagen: $(this).find(".imagen").data("imagen"),
            ivaPorcentaje:IVA.Code,
            subtotal:  $(this).find(".subtotal").text().replace('$', ''),
            descuento:  $(this).find(".desMoney").text().replace('$', ''),
            total:  $(this).find(".totalFinal").text().replace('$', '')
        });
    });

    $("#articulosH").val(JSON.stringify(articulos));
    $("#formCotizacion").submit();
    // === Verificar stock antes de enviar ===
    /*$.ajax({
        url: `/Pedidos/Articulo/stock`, // ruta al controlador para verificar el stock de articulos
        type: "POST",
        data: {
            articulos: articulos
        },
        success: function(response) {
            if (response.success) {
                // Todo bien → enviamos el formulario
                $("#formCotizacion").submit();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stock insuficiente',
                    html: response.mensaje,
                    confirmButtonColor: '#05564f',
                    confirmButtonText: 'Aceptar'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo verificar el stock. Intenta nuevamente.'
            });
        }
    });*/

});

// ================================
// GUARDAR PEDIDO
// ================================
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
    });
});

$("#btnPedido").on("click", function(e) {
    if (!validar(e)) return;

    // Llenar inputs ocultos con valores de la página
    const cliente = $('#selectCliente').select2('data')[0] || {};
    const $option = $('#selectCliente').find(`option[value="${cliente.id}"]`);

    const cardname = cliente.cardname || $option.data('cardname') || '';
    const phone = cliente.phone || $option.data('phone') || '';
    const email = cliente.email || $option.data('email') || '';

    $("#clienteP").val(cliente.id || '');
    $("#fechaCreacionP").val($("#fechaCreacion").val());
    $("#fechaEntregaP").val($("#fechaEntrega").val());
    $("#CardNameP").val(cardname || '');
    $("#SlpCodeP").val($("#selectVendedor").val());
    $("#phone1P").val(phone || '');
    $("#emailP").val(email || '');
    $("#DocCurP").val($("#selectMoneda").val());
    $("#direccionFiscalP").val($("#direccionFiscal").text());
    $("#direccionEntregaP").val($("#direccionEntrega").text());
    $("#TotalSinPromoP").val($("#totalAntesDescuento").text().replace('$',''));
    $("#DescuentoP").val($("#DescuentoD").text().replace('$',''));
    $("#SubtotalP").val($("#totalConDescuento").text().replace('$',''));
    $("#ivaP").val($("#iva").text().replace('$',''));
    $("#totalP").val($("#total").text().replace('$',''));
    $("#comentariosP").val($('#comentarios').val());

    const $filas = $("#tablaArticulos tbody tr");
    let articulos = [];
    $filas.each(function(index) {
        // Si hay más de una fila y esta es la última → saltar
        if ($filas.length > 1 && index === $filas.length - 1) return;

        const itemCode = $(this).find(".itemcode").text().trim();
        const baseLine = $(this).data('baseline') ?? null;
        if (!itemCode) return; // ignorar filas vacías o plantillas

        articulos.push({
            baseLine,
            itemCode,
            descripcion: $(this).find(".frgnName").text(),
            unidad: $(this).find(".medida").text(),
            precio: $(this).find(".precio").text(),
            descuentoPorcentaje: $(this).find(".descuentoporcentaje").text(),
            cantidad: $(this).find(".cantidad").val(),
            imagen: $(this).find(".imagen").data("imagen"),
            ivaPorcentaje: IVA.Code,
            subtotal: $(this).find(".subtotal").text().replace('$', ''),
            descuento: $(this).find(".desMoney").text().replace('$', ''),
            total: $(this).find(".totalFinal").text().replace('$', '')
        });
    });
    
    $("#articulosP").val(JSON.stringify(articulos));
    const baseEntry = document.getElementById('aux').value;
    //$("#formCotizacionPedido").submit();
    // === Verificar stock antes de enviar ===
    $.ajax({
        url: `/Pedidos/Articulo/stock`, // ruta al controlador para verificar el stock de articulos
        type: "POST",
        data: {
            articulos: articulos
        },
        success: function(response) {
            if (response.success) {
                // Todo bien → enviamos el formulario
                $("#formCotizacionPedido").submit();
            } else {
                // Si hay BaseEntry, mostrar botón adicional
                let config = {
                    icon: 'warning',
                    title: 'Stock insuficiente',
                    html: response.mensaje,
                    confirmButtonColor: '#05564f',
                    confirmButtonText: baseEntry ? 'Ir al pedido' : 'Aceptar'
                };

                if (baseEntry) {
                    config.showCancelButton = true;
                    config.cancelButtonColor = '#fc0202ff',
                    config.cancelButtonText = 'Cancelar';
                }

                Swal.fire(config).then(result => {
                    if (result.isConfirmed && baseEntry) {
                        window.location.href = `/Pedidos/NuevoPedido/${baseEntry}`;
                    }
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo verificar el stock. Intenta nuevamente.'
            });
        }
    });
});

