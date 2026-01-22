<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Clientes;
use App\Models\Cotizacion;
use App\Models\configuracion;
use App\Models\DireccionesClientes;
use App\Models\LineasCotizacion;
use App\Models\LineasPedidos;
use App\Models\Moneda;
use App\Models\Vendedores; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionesController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        $configuracionVacia = configuracion::count() === 0;

        $buscar = $request->input('buscar');
        $fecha = $request->input('fecha');
        $mostrar = $request->input('mostrar', 10);
        $DocStatus = $request->input('DocStatus');

        $query = Cotizacion::with(['vendedor', 'moneda'])
                    ->orderBy('DocEntry', 'desc');

        // Filtrado por rol
        if ($user->rol_id == 3) { // Cliente
            $query->where('CardCode', $user->codigo_cliente);
        } elseif ($user->rol_id == 4) { // Vendedor
            $query->where('SlpCode', $user->codigo_vendedor);
        } elseif (!in_array($user->rol_id, [1,2])) {
            abort(403, 'Rol no permitido');
        }

        // Filtro búsqueda por Folio, Cliente y Vendedor
        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('DocEntry', 'like', "%$buscar%")
                ->orWhere('DocNum', 'like', "%$buscar%")
                ->orWhere('CardName', 'like', "%$buscar%")
                ->orWhereHas('vendedor', function($v) use ($buscar) {
                    $v->where('SlpName', 'like', "%$buscar%");
                });
            });
        }

        // Filtro por fecha
        if ($fecha) {
            $query->whereDate('DocDate', $fecha);
        }

        if($DocStatus == 'C')
        {
            $query->where('DocStatus', 'C');
        }
        else if($DocStatus == 'A'){
             $query->where('DocStatus', 'A');
        }

        $cotizaciones = $query->paginate($mostrar)->withQueryString();

        if ($request->ajax()) {
            return view('partials.tabla_cotizacion', [
                'cotizaciones' => $cotizaciones,
                'configuracionVacia' => $configuracionVacia
            ])->render();
        }

        return view('users.cotizaciones', compact('cotizaciones', 'configuracionVacia'));
    }

    public function NuevaCotizacion ($DocEntry = null)
    {
        $configuracion = Configuracion::with('impuesto')->firstOrFail();
        $IVA = $configuracion->impuesto;

        $hoy = Carbon::today()->format('Y-m-d');
        $mañana = Carbon::tomorrow()->format('Y-m-d');
        $pedido = null;
        $cotizacion = null;

        // Fechas por defecto (HOY para nueva cotización)
        $fechaCreacion = $hoy;
        $fechaEntrega  = $mañana;

        //$clientes = Clientes::with('descuentos.detalles.marca')->where('Active', 'Y')->get();
        $clientes = null;

        $user = Auth::user();
        $vendedores = Vendedores::where('Active', 'Y')->get();
        $vendedorBase = Vendedores::where('SlpCode', -1)->first();

        // Monedas con cambios del día
        $monedas = Moneda::with(['cambios' => function($query) use ($hoy) {
            $query->whereDate('RateDate', $hoy);
        }])->get();

        // Artículos activos que tengan cambios en su moneda para hoy
        /*$articulos = Articulo::where('Active', 'Y')
            ->where('OnHand', '>', 0)
            ->whereHas('precio.moneda.cambios', function($query) use ($hoy) {
                $query->whereDate('RateDate', $hoy);
            })
            ->with(['precio.moneda.cambios' => function($query) use ($hoy) {
                $query->whereDate('RateDate', $hoy);
            }, 'imagen'])
            ->with('marca')
            ->get();*/

        $articulos = Articulo::where('Active', 'Y')
        ->whereHas('precio.moneda.cambios', function($query) use ($hoy) {
            $query->whereDate('RateDate', $hoy);
        })
        ->with(['precio.moneda.cambios' => function($query) use ($hoy) {
            $query->whereDate('RateDate', $hoy);
        }, 'imagen'])
        ->with('marca')
        ->get();
        
        //ÚNICA MEJORA REAL: indexar colección
        $articulosPorCodigo = $articulos->keyBy('ItemCode');


        $modo = 0;

        // Valores por defecto
        $preseleccionados = [
           'cliente' => ($user->rol_id == 3) ? $user->codigo_cliente : null,
           'vendedor' => $user->rol_id == 4 ? $user->codigo_vendedor : ($user->rol_id == 3 ? $vendedorBase->SlpCode : null),
           'moneda' => $configuracion->MonedaPrincipal,
        ];

        $lineasComoArticulos = [];

        if ($DocEntry) {
            // Si hay cotización, precargamos datos
            $cotizacion = Cotizacion::with('lineas')->findOrFail($DocEntry);

            $preseleccionados = [
                'cliente'  => $cotizacion->CardCode,
                'vendedor' => $cotizacion->SlpCode,
                'moneda'   => $cotizacion->DocCur,
                'comentario' =>$cotizacion->comment,
            ];

            // 🔥 SIN O(n²)
            foreach ($cotizacion->lineas as $linea) {
                if (isset($articulosPorCodigo[$linea->ItemCode])) {
                    $artClone = clone $articulosPorCodigo[$linea->ItemCode];
                    $artClone->Quantity = $linea->Quantity;
                    $lineasComoArticulos[] = $artClone;
                }
            }
        }
        $clienteBase = null;
        if(!empty($preseleccionados['cliente'])){
            $clienteBase = Clientes::where('CardCode', $preseleccionados['cliente'])->first();
        }

        return view('users.cotizacion', compact('clienteBase', 'clientes', 'vendedores', 'monedas', 'articulos', 'IVA', 'preseleccionados', 'modo', 'fechaCreacion', 'fechaEntrega', 'lineasComoArticulos', 'pedido', 'cotizacion'));
    }

    public function ObtenerDirecciones($CardCode){
        // Obtener todas las direcciones de un cliente
        $direcciones = DireccionesClientes::where('CardCode', $CardCode)->get();
        $fiscal = "S/D";
        $entrega = "S/D";

        foreach ($direcciones as $direccion) {
            switch($direccion->Address) {
                case 'FISCAL':
                    if ($direccion->cliente) {
                        $fiscal =($direccion->Street ?? '') . "\n" .
                                ($direccion->Block ?? '') . "\n" .
                                "C.P. " . ($direccion->ZipCode ?? '') . "\n" .
                                ($direccion->City ?? '') . ", " . ($direccion->County ?? '') . "\n" .
                                ($direccion->State ?? '') . ", " . ($direccion->Country ?? '');
                    }
                    break;

                case 'ENVIO':
                case 'ENVÍO':
                    $entrega = ($direccion->Street ?? '') . "\n" .
                               ($direccion->Block ?? '') . "\n" .
                               "C.P. " . ($direccion->ZipCode ?? '') . "\n" .
                               ($direccion->City ?? '') . ", " . ($direccion->County ?? '') . "\n" .
                               ($direccion->State ?? '') . ", " . ($direccion->Country ?? '');
                    break;
            }
        }

         return response()->json([
            'fiscal' =>$fiscal,
            'entrega' => $entrega,
        ]);
    }


    public function GuardarCotizacion(Request $request)
    {
        try {
            //Validaciones
            $request->validate([
                'cliente'          => 'required',
                'SlpCode'          => 'required',
                'fechaCreacion'    => 'required',
                'fechaEntrega'     => 'required',
                'CardName'         => 'required',
                'DocCur'           => 'required',
                //'ShipToCode'       => 'required',
                //'PayToCode'        => 'required',
                'direccionFiscal'  => 'required',
                'direccionEntrega' => 'required',
                'TotalSinPromo'    => 'required',
                'Descuento'        => 'required',
                'Subtotal'         => 'required',
                'iva'              => 'required',
                'total'            => 'required',
                'articulos'        => 'required|json',
            ], [
                // Mensajes personalizados
                'required' => 'El campo :attribute es obligatorio.',
                'json'     => 'Los artículos deben enviarse en formato JSON válido.',
            ], [
                //quivalencias de atributos
                'CardName'         => 'Nombre del cliente',
                'SlpCode'          => 'Vendedor',
                'phone1'           => 'Teléfono',
                'email'            => 'Correo electrónico',
                'DocCur'           => 'Moneda',
                //'ShipToCode'       => 'Dirección de envío',
                //'PayToCode'        => 'Dirección de pago',
            ]);

            // Guardar líneas de cotización
            $articulos = json_decode($request->articulos, true);
            if (is_array($articulos) && count($articulos) < 1){
                return back()->with('error', 'Ocurrio un error no puedes guardar cotizaciones sin articulos');
            }

            // Limpiar valores numéricos
            $totalSinPromo = floatval(str_replace(['$', 'MXM', ','], '', $request->TotalSinPromo));
            $descuento     = floatval(str_replace(['$', 'MXM', ','], '', $request->Descuento));
            $subtotal      = floatval(str_replace(['$', 'MXM', ','], '', $request->Subtotal));
            $iva           = floatval(str_replace(['$', 'MXM', ','], '', $request->iva));
            $total         = floatval(str_replace(['$', 'MXM', ','], '', $request->total));

            // Guardar cotización
            $cotizacion = Cotizacion::create([
                'CardCode'      => $request->cliente,
                'DocDate'       => $request->fechaCreacion,
                'DocDueDate'    => $request->fechaEntrega,
                'CardName'      => $request->CardName,
                'SlpCode'       => $request->SlpCode,
                'Phone1'        => $request->phone1 ?? '',
                'E_Mail'        => $request->email ?? '',
                'DocCur'        => $request->DocCur,
                'ShipToCode'    => $request->ShipToCode ?? '',
                'PayToCode'     => $request->PayToCode ?? '',
                'Address'       => $request->direccionFiscal,
                'Address2'      => $request->direccionEntrega,
                'TotalSinPromo' => $totalSinPromo,
                'Descuento'     => $descuento,
                'Subtotal'      => $subtotal,
                'IVA'           => $iva,
                'Total'         => $total,
                'comment'       => $request->comentarios,
            ]);


            $lineNum = 0;

            foreach ($articulos as $art) {
                
                LineasCotizacion::create([
                    'DocEntry'      => $cotizacion->DocEntry,
                    'fecha'         => Carbon::today()->format('Y-m-d'),
                    'LineNum'       => $lineNum,
                    'BaseLine'      => $lineNum,
                    'ItemCode'      => $art['itemCode'],
                    'U_Dscr'        => $art['descripcion'],
                    'unitMsr2'      => $art['unidad'],
                    'Price'         => floatval(str_replace(',', '', $art['precio'])),
                    'DiscPrcnt'     => floatval(str_replace(['%', ','], '', $art['descuentoPorcentaje'])),
                    'Quantity'      => floatval($art['cantidad']),
                    'Id_imagen'     => $art['imagen'] ?? null,
                    'ivaPorcentaje' => $art['ivaPorcentaje'] ?? null,
                    'Subtotal'      => $art['subtotal'],  
                    'Descuento'     => $art['descuento'],
                    'Total'         => $art['total'],
                ]);
                $lineNum++;
            }

            if ($request->DocEntry_Aux != '') {
                // Buscar la cotización por su DocEntry
                $cotizacion = Cotizacion::where('DocEntry', $request->DocEntry_Aux)->first();

                if ($cotizacion) {
                    // Cambiar su estado a inactiva
                    $cotizacion->DocStatus = 'C';
                    $cotizacion->save();
                }
            }

            return redirect()->route('cotizaciones')->with('success', 'Cotización guardada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al guardar la cotización: ' . $e->getMessage());
        }
    }

    public function detalles($DocEntry)
    {
        $user = Auth::user();
        // Obtener la cotización con sus líneas
        $cotizacion = Cotizacion::with('lineas')->findOrFail($DocEntry);

        //Validar permisos según rol
        if (in_array($user->rol_id, [1, 2])) {
            // Admin y super admin pueden ver todo
        } elseif ($user->rol_id == 3) {
            // Cliente: solo puede ver sus propias cotizaciones
            if ($cotizacion->CardCode != $user->codigo_cliente) {
                abort(403, 'No tienes permiso para ver esta cotización.');
            }
        } elseif ($user->rol_id == 4) {
            // Vendedor: solo puede ver sus propias cotizaciones
            if ($cotizacion->SlpCode != $user->codigo_vendedor) {
                abort(403, 'No tienes permiso para ver esta cotización.');
            }
        } else {
            abort(403, 'Rol no permitido.');
        }

        //verifica si existe algun pedido relacionado
        $pedido = LineasPedidos::where('BaseEntry', $cotizacion->DocEntry)->first();

        // Datos adicionales para la vista
        $configuracion = Configuracion::with('impuesto')->firstOrFail();
        $IVA = $configuracion->impuesto;
        $hoy = Carbon::today()->format('Y-m-d');

        // Clientes y vendedores
        #$clientes = Clientes::with('descuentos.detalles.marca')->get();
        $clientes = [];
        $vendedores = Vendedores::where('Active', 'Y')->get();

        // Monedas
        $monedas = Moneda::with(['cambios' => function($query) use ($hoy) {
            $query->whereDate('RateDate', $hoy);
        }])->get();

        $articulos = [];

        // Fechas de la cotización
        $fechaCreacion = $cotizacion->DocDate;
        $fechaEntrega  = $cotizacion->DocDueDate;

        // Modo: 1 = solo ver (todos los campos readonly/disabled)
        $modo = 1;

        // Datos preseleccionados para evitar errores en la vista
        $preseleccionados = [
            'cliente' => $cotizacion->CardCode,
            'vendedor' => $cotizacion->SlpCode,
            'moneda' => $cotizacion->DocCur,
            'comentario' =>$cotizacion->comment,
            'crearPedido' => ($fechaCreacion === $hoy) ? true : false,
        ];

        $clienteBase = null;
        if(!empty($preseleccionados['cliente'])){
            $clienteBase = Clientes::where('CardCode', $preseleccionados['cliente'])->first();
        }

        // Retornar la vista
        return view('users.cotizacion', compact('clienteBase', 'cotizacion', 'IVA', 'clientes', 'vendedores', 'monedas', 'articulos', 'modo', 'fechaCreacion', 'fechaEntrega', 'preseleccionados', 'pedido' ));
    }


    public function pdfCotizacion($id)
    {
        
        $cotizacion = Cotizacion::with('lineas')->findOrFail($id);

        $data = [
            'logo' => resource_path('views/pdf/logo.png'),
            'titulo'  => 'COTIZACIÓN',
            'subtitulo'  => 'Cotización',
            'numero'  =>  $cotizacion->DocEntry,
            'fecha'   => $cotizacion->DocDate,
            'vendedor' => $cotizacion->vendedor->SlpName ?? '',
            'moneda'   => $cotizacion->moneda->Currency,
            'comentario' =>$cotizacion->comment,

            'cliente' => [
                'codigo'  => $cotizacion->CardCode,
                'nombre'   => $cotizacion->CardName,
                'dir_fiscal' => $cotizacion->Address,
                'dir_envio' => $cotizacion->Address2,
                'email'    => $cotizacion->E_Mail,
                'telefono' => $cotizacion->Phone1,
            ],

            'lineas' => array_chunk(
                $cotizacion->lineas->map(function($l) {
                    return [
                        'codigo'      => $l->ItemCode,//clave
                        'descripcion' => $l->U_Dscr, //descripcion
                        'cantidad'    => $l->Quantity, //cantidad
                        'precio'      => $l->Price,//precio unitario
                        'importe'     => $l->Subtotal, //subtotal
                        'descuetos'   => $l->DiscPrcnt,//descuetos
                        'total'       => $l->Total,//total
                    ];
                })->toArray(),25 //25 arituclos por pagina para poder paginarlos
            ),

            'totales' => [
                'subtotal' => number_format($cotizacion->Subtotal, 2),
                'iva'      => number_format($cotizacion->IVA, 2),
                'total'    => number_format($cotizacion->Total, 2),
            ]
        ];

        $pdf = Pdf::loadView('pdf.documento', $data)->setPaper('lette', 'portrait');

        $pdf->output();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();

        // Footer en todas las páginas
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $user = Auth::user()->nombre;
            $texto = "Documento generado automáticamente el " . date('d/m/Y H:i') . " — Página $pageNumber de $pageCount" ."   Autor: " . $user;
            $font = $fontMetrics->get_font("Calibri", "normal");
            $size = 6;

            $width = $canvas->get_width();
            $textWidth = $fontMetrics->get_text_width($texto, $font, $size);

            // Centrado exacto
            $x = ($width - $textWidth) / 2;
            $y = $canvas->get_height() - 20;

            $canvas->text($x, $y, $texto, $font, $size, [0,0,0]);
        });


        return $pdf->stream("Cotizacion-{$cotizacion->DocEntry}.pdf");
    }
}