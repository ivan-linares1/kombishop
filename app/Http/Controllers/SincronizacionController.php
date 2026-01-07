<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Clientes;
use App\Models\Cotizacion;
use App\Models\Descuento;
use App\Models\DetalleDescuento;
use App\Models\DireccionesClientes;
use App\Models\ListaPrecio;
use App\Models\Marcas;
use App\Models\Moneda;
use App\Models\MonedaCambio;
use App\Models\Pedido;
use App\Models\Precios;
use App\Models\Stock;
use App\Models\Vendedores;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SincronizacionController extends Controller
{
    // Conexión al Web Service con manejo de errores
    private function ConexionWBS()
    {
        $url = "http://10.10.1.45:8083/KombiService.asmx?wsdl";
        $token = "12345678";

        try {
            // Cabecera HTTP personalizada
            $headers = [ 'http' => [ 'header' => "Authorization: Bearer $token\r\n", ]];
            $context = stream_context_create($headers);

            $client = new \SoapClient($url, [
                'trace' => true,
                'exceptions' => true,
                'stream_context' => $context,
            ]);

           return [
                'success' => true,
                'client' => $client,
                'type' => 'success',
                'message' => 'Conexión al WS establecida correctamente.'
            ];

        } catch (\SoapFault $e) {
            return [
                'success' => false,
                'client' => null,
                'type' => 'warning',
                'message' => "ERROR SOAP: Falla en la conexion al Servicio Web",
                'error' => $e->getMessage() 
            ];
        }catch (\Exception $e) {
            return [
                'success' => false,
                'client' => null,
                'type' => 'error',
                'message' => 'Error general en la conexión al WS.',
                'error' => $e->getMessage()
            ];
        }
    }

    //$servicio, es el nombre con el que se llenaran los mensajes 
    //$metodo, Es el nombre del metodo del servico web al que se accedera
    //$cli es la bandera para saber si es por consola o por sistema web
    //$returnvalor es solo para regresar valores en EDG1 porque tiene varios metodos 
    public function ServicioWeb($servicio, $metodo, $modo, $cli = false, $returnvalor = false) 
    {
        $conexion = $this->ConexionWBS();

        if (!$conexion['success']) {
            Log::channel('sync')->error("Fallo de conexión con el servicio web: {$conexion['error']} en modo: {$modo}"); //Guarda el mensaje de error en los logs
            
            if ($cli) { echo $conexion['message'] . "\n"; return; }
            if ($returnvalor) { return ['tipo' => $conexion['type'], 'msg' => $conexion['message']]; }
            return redirect()->back()->with($conexion['type'], $conexion['message']); //Regresa a la pantalla la alerta del error
        }

        $client = $conexion['client'];

        try {
            $response = $client->$metodo(['parameters' => []]);
            $xmlResponse = $response->{$metodo.'Result'};

            switch($servicio){
                case 'Monedas': $valor = $this->Monedas($xmlResponse, $modo, $cli); break;
                case 'Articulos': $valor = $this->Articulos($xmlResponse, $modo, $cli); break;
                case 'Categoria_Lista_Precios': $valor = $this->Categoria_Lista_Precios($xmlResponse, $modo, $cli); break;
                case 'Marcas': $valor = $this->Marcas($xmlResponse, $modo, $cli); break;
                case 'Lista_Precios': $valor = $this->ListaPrecio($xmlResponse, $modo, $cli); break;
                case 'Clientes': $valor = $this->Clientes($xmlResponse, $modo, $cli); break;
                case 'Direcciones': $valor = $this->Direcciones($xmlResponse, $modo, $cli); break;
                case 'Grupo_Descuentos': $valor = $this->Grupo_Descuentos($xmlResponse, $modo, $cli); break;
                case 'Descuentos_Detalle': $valor = $this->DescuentosDetalle($xmlResponse, $modo, $cli); break;
                case 'Cambios_Monedas': $valor = $this->CambiosMoneda($xmlResponse, $modo, $cli); break;
                case 'Vendedores': $valor = $this->Vendedores($xmlResponse, $modo, $cli); break;
                case 'DocNum': $valor = $this->CotizacionUpdate($xmlResponse, $modo, $cli); break;
                case 'DocNumP': $valor = $this->PedidoUpdate($xmlResponse, $modo, $cli); break;
                case 'stock': $valor = $this->stock($xmlResponse, $modo, $cli); break;
                case 'CotizacionEstatus': $valor = $this->CotizacionEstatus($xmlResponse, $modo, $cli); break;
                case 'PedidoEstatus': $valor = $this->PedidoEstatus($xmlResponse, $modo, $cli); break;

                default:
                    $valor = ['tipo' => 'warning', 'msg' => "Servicio no reconocido: {$servicio}"];
            }

            if ($returnvalor) { return $valor; } // Devuelve array en lugar de redirect
            

            if ($cli) { echo $valor['msg'] . "\n"; } 
            else if ($returnvalor) { return ['tipo' => $conexion['type'], 'msg' => $conexion['message']]; }
            else { return redirect()->back()->with($valor['tipo'], $valor['msg']); }

        } catch (\Throwable $e) {//Marca errores que esten por mala recepcion de datos
            $msg = "Error al sincronizar ".$servicio ;//. $e->getMessage();

            //Log detallado del error
            Log::channel('sync')->error($msg, [
                'servicio' => $servicio,
                'metodo' => $metodo,
                'error' => $e->getMessage()
            ]);

            if ($returnvalor) { return ['tipo' => 'error', 'msg' => $msg . ': ' . $e->getMessage()]; }

            if ($cli) { echo $msg . "\n"; }
            else { return redirect()->back()->with('error', $msg); }
        }
    }

    public function ServicioWebAux($servicio, $metodo, $modo, $cli = false)
    {
        try {
            // Ejecuta los dos métodos SOAP
           Log::channel('sync')->notice("Inicio del servicio 1"); $valor1 =  $this->ServicioWeb($servicio, $metodo.'_1', $modo, $cli, true);
           Log::channel('sync')->notice("Inicio del servicio 2"); $valor2 = $this->ServicioWeb($servicio, $metodo.'_2', $modo, $cli, true);
           Log::channel('sync')->notice("Inicio del servicio 3"); $valor3 = $this->ServicioWeb($servicio, $metodo.'_3', $modo, $cli, true);
           Log::channel('sync')->notice("Inicio del servicio 4"); $valor4 = $this->ServicioWeb($servicio, $metodo.'_4', $modo, $cli, true);

           $tipos = [$valor1['tipo'], $valor2['tipo'], $valor3['tipo'], $valor4['tipo']];

           if ($cli) {
                // Para CLI solo mostramos en consola
                if ( count(array_unique( $tipos)) === 1 ) { echo "{$valor1['msg']}\n"; }
                else { echo "1.- {$valor1['msg']}\n2.- {$valor2['msg']}\n3.- {$valor3['msg']}\n4.- {$valor4['msg']}\n"; }
                return;
            } else { // Para web
                if( count(array_unique( $tipos)) === 1 ){$tipo = $valor1['tipo']; $mensaje = $valor1['msg']; }
                else{
                    $tipo = 'warning';
                    $msgs = [
                        "Sub WS 1.- {$valor1['msg']}",
                        "Sub WS 2.- {$valor2['msg']}",
                        "Sub WS 3.- {$valor3['msg']}",
                        "Sub WS 4.- {$valor4['msg']}"
                    ];
                    $unicos = array_unique($msgs);
                    $mensaje = implode('<br>', $unicos);
                }
                return redirect()->back()->with($tipo, $mensaje);
            }

        } catch (\Throwable $e) {
            Log::channel('sync')->error("Error al sincronizar EDG1: " . $e->getMessage());
            if ($cli) {
                echo "Error al ejecutar la sincronización de {$metodo}\n";
                return;
            } else {
                return redirect()->back()->with('Error', "Error al ejecutar la sincronización de {$metodo}");
            }
        }
    }

    //funciones privadas que se encargan de hacer las inserciones o actualizacions de cada servicio web individualmente 
    private function Monedas($xmlResponse, $modo, $cli) //OCRN
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->Moneda)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->Moneda instanceof \stdClass) { $xmlResponse->Moneda = [$xmlResponse->Moneda]; }

        $total = count($xmlResponse->Moneda); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xmlResponse->Moneda as $moneda) {
            try {
                $registro = Moneda::updateOrCreate( 
                    ['Currency' => (string) $moneda->CurrCode],
                    [ 'CurrName'   => (string) $moneda->CurrName],
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OCRN_Monedas: " . "Error con la moneda: " . (string)$moneda->CurrCode . "=> " . $e->getMessage());
            }
        }
         return $this->aux('Monedas', $total, $insertados, $errores, 0, $modo );
    }
    
    private function Articulos($xmlResponse, $modo, $cli) //OITM
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->Articulo)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->Articulo instanceof \stdClass) { $xmlResponse->Articulo = [$xmlResponse->Articulo]; } 
        

        $total = count($xmlResponse->Articulo); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
         $errores = 0;   // Contador de errores
        
        foreach ($xmlResponse->Articulo as $art) {
            try {
                $registro = Articulo::updateOrCreate( 
                    ['ItemCode' => (string) $art->ItemCode],
                    [
                        'ItemName'   => (string) $art->ItemName,
                        'FrgnName'   => (string) $art->FrgnName,
                        'SalUnitMsr' => (string) $art->SalUnitMsr,
                        'Active'     => (string) ($art->validFor),
                        'ItmsGrpCod' => (string) $art->ItmsGrpCod,
                        'Id_imagen'  => 1,
                    ],
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                 $errores++; Log::channel('sync')->error("OITM_Articulos: " . "Error con el articulo: " . (string)$art->ItemName . "=> " . $e->getMessage());
            }
        }
         return $this->aux('Articulos', $total, $insertados,  $errores, 0, $modo );
    }

    private function Categoria_Lista_Precios($xmlResponse, $modo, $cli) //OPLN
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->CAT_LP)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->CAT_LP instanceof \stdClass) { $xmlResponse->CAT_LP = [$xmlResponse->CAT_LP]; }

        $total = count($xmlResponse->CAT_LP); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xmlResponse->CAT_LP as $lista) {
            try {
                $registro = ListaPrecio::updateOrCreate(
                    ['ListNum' => (int) $lista->ListNum],
                    ['ListName' => (string) $lista->ListName]
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OPLN_Articulos: " . "Error con la categoria de lista de precio: " . (string)$lista->ListName . "=> " . $e->getMessage());
            }    
        }
        return $this->aux('Categorias de Listas de Precios', $total, $insertados, $errores, 0, $modo );
    }

    private function Marcas($xmlResponse, $modo, $cli) //OITB
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->Marcas)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->Marcas instanceof \stdClass) { $xmlResponse->Marcas = [$xmlResponse->Marcas]; }

        $total = count($xmlResponse->Marcas); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores


        foreach ($xmlResponse->Marcas as $marca) {
            try {
                $registro = Marcas::updateOrCreate(    
                    ['ItmsGrpCod' => (string) $marca->ItmsGrpCod],
                    [
                        'ItmsGrpNam' => (string) $marca->ItmsGrpNam,
                        'Locked'     => (string) $marca->Locked,
                        'Object'     => (string) $marca->Object,
                    ]
                );

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                 $errores++; Log::channel('sync')->error("OITB_Marcas: " . "Error con la marca: " . (string)$marca->ItmsGrpNam . "=> " . $e->getMessage());
            }
        }
        return $this->aux('Marcas', $total, $insertados, $errores, 0, $modo );
    }

    private function ListaPrecio($xmlResponse, $modo, $cli)//ITM1
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->ListaP)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->ListaP instanceof \stdClass) { $xmlResponse->ListaP = [$xmlResponse->ListaP]; }

        $total = count($xmlResponse->ListaP); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xmlResponse->ListaP as $precio) {
            try {
                // Obtener Currency_ID desde OCRN
                $currency = Moneda::where('Currency', (string)$precio->Currency)->first();
                if (!$currency) {
                    $warnings++; 
                    Log::channel('sync')->warning("ITM1_ListaPrecio: Warning: Faltan la moneda ".$precio->Currency." Por ingresar en el sistema");
                    // Si no existe la moneda, puedes saltarla o manejar el error
                    continue;
                }

                // Insertar o actualizar precio
                 $registro = Precios::updateOrInsert(
                    [
                        'ItemCode' => (string)$precio->ItemCode,
                        'PriceList' => (int)$precio->PriceList
                    ],
                    [
                        'Price' => (float)$precio->Price,
                        'Currency_ID' => $currency->Currency_ID
                    ]
                );
                 if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("ITM1_ListaPrecio: " . "Error con el precio del articulo: " . (string)$precio->ItemCode . "=> " . $e->getMessage());
            }
        }
        return $this->aux('Lista de Precio', $total, $insertados, $errores, $warnings, $modo );
    }

    private function Clientes($xmlResponse, $modo, $cli)//OCRD
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->Clientes)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->Clientes instanceof \stdClass) { $xmlResponse->Clientes = [$xmlResponse->Clientes]; }

        $total = count($xmlResponse->Clientes); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xmlResponse->Clientes as $cliente) {
            try {
                 $registro = Clientes::updateOrInsert(
                    ['CardCode' => (string)$cliente->CardCode],
                    [
                        'CardName' => (string)$cliente->CardName,
                        'GroupNum' => (int)$cliente->GroupNum,
                        'phone1'   => (string)$cliente->Phone1,
                        'e-mail'   => (string)$cliente->E_Mail,
                        'Active'   => (string)$cliente->validFor,
                    ]
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OCRD_Clientes: " . "Error con el cliente: " . (string)$cliente->CardName . "=> " . $e->getMessage());
            }
        }
        return $this->aux('Clientes', $total, $insertados, $errores, 0, $modo );
    }

    private function Direcciones($xmlResponse, $modo, $cli) // CRD1
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->Direcciones)){
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->Direcciones instanceof \stdClass) { $xmlResponse->Direcciones = [$xmlResponse->Direcciones]; }

        $total = count($xmlResponse->Direcciones); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        $excluidos = 0;

        foreach ($xmlResponse->Direcciones as $direccion){
            try {
                 // Verificar que el cliente exista en OCRD
                $clienteExiste = Clientes::where('CardCode', (string)$direccion->CardCode)->exists();

                // Si no existe, lo omitimos y seguimos con el siguiente
                if (!$clienteExiste) { 
                    $cardCode = (string)$direccion->CardCode;
                    // Si NO comienza con "P", lo registramos como warning
                    if (strtoupper(substr($cardCode, 0, 1)) != 'P') {
                        $warnings++; Log::channel('sync')->warning("CRD1_Direcciones: Cliente con CardCode '{$cardCode}' no encontrado en OCRD.");
                    }
                    else{ $excluidos++; }
                    continue; 
                }

                // Insertar o actualizar dirección
                $registro = DireccionesClientes::updateOrInsert(
                    [
                        'CardCode'  => (string)$direccion->CardCode,
                        'Address'   => (string)$direccion->Address,
                        'AdresType' => (string)$direccion->AdresType
                    ],
                    [
                        'Street'   => (string)$direccion->Street,
                        'Block'    => (string)$direccion->Block,
                        'ZipCode'  => (string)$direccion->ZipCode,
                        'City'     => (string)$direccion->City,
                        'Country'  => (string)$direccion->Country,
                        'County'   => (string)$direccion->County,
                        'State'    => (string)$direccion->State
                    ]
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            }catch (\Throwable $e) {
                 $errores++; Log::channel('sync')->error("CRD1_Direcciones: " . "Error con direcion de: ".$direccion->Address." Del cliente " . (string)$direccion->CardCode . "=> " . $e->getMessage());
            }
        } 
        return $this->aux('Direcciones', $total, $insertados, $errores, $warnings, $modo, $excluidos );
    }

    private function Grupo_Descuentos($xmlResponse, $modo, $cli) //OEDG
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->GPO_Descuentos)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->GPO_Descuentos instanceof \stdClass) { $xmlResponse->GPO_Descuentos = [$xmlResponse->GPO_Descuentos]; }

        $total = count($xmlResponse->GPO_Descuentos); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        $excluidos = 0;

        foreach ($xmlResponse->GPO_Descuentos as $GPO_Descuento) {
            try {
                // Verificar que el cliente exista en OCRD
                $clienteExiste = Clientes::where('CardCode', (string)$GPO_Descuento->ObjCode)->exists();

                // Si no existe, lo omitimos y seguimos con el siguiente
                if (!$clienteExiste) { 
                     $cardCode = (string)$GPO_Descuento->ObjCode;
                    // Si NO comienza con "P", lo registramos como warning
                    if (strtoupper(substr($cardCode, 0, 1)) != 'P') {
                        $warnings++; Log::channel('sync')->warning("OEDG_GruposDescuento: Cliente con CardCode '{$cardCode}' no encontrado en OCRD.");
                    }
                    else{ $excluidos++; }
                    continue; 
                }
                // Insertar o actualizar registro
                $registro = Descuento::updateOrInsert(
                    ['AbsEntry' => (int)$GPO_Descuento->AbsEntry],
                    [
                        'Type' => (string)$GPO_Descuento->Type,
                        'ObjType' => (int)$GPO_Descuento->ObjType,
                        'ObjCode'   => (string)$GPO_Descuento->ObjCode,
                    ]
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OEDG_GruposDescuento: " . "Error con el grupo de descuento de: ".$GPO_Descuento->AbsEntry." Del cliente " . (string)$GPO_Descuento->ObjCode . "=> " . $e->getMessage());
            }           
        }
         return $this->aux('Grupos de Descuentos', $total, $insertados, $errores, $warnings, $modo, $excluidos );
    }

    private function DescuentosDetalle($xmlResponse, $modo, $cli) //EDG1
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->GPO_DescuentosEDG1)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->GPO_DescuentosEDG1 instanceof \stdClass) { $xmlResponse->GPO_DescuentosEDG1 = [$xmlResponse->GPO_DescuentosEDG1]; }

        $total = count($xmlResponse->GPO_DescuentosEDG1); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        

        foreach ($xmlResponse->GPO_DescuentosEDG1 as $desc) {
            try {
                $absEntry = (int) ($desc->AbsEntry );
                $objKey   = (string) ($desc->ObjKey);

                // Validar foráneos
                $oedgExiste = Descuento::where('AbsEntry', $absEntry)->exists();
                $oitbExiste = Marcas::where('ItmsGrpCod', $objKey)->exists();

                if (!$oedgExiste || !$oitbExiste) {
                    $detalle = [];
                    if (!$oedgExiste) $detalle[] = "El grupo de descuento: '{$absEntry}' no encontrado";
                    if (!$oitbExiste) $detalle[] = "La Marca: '{$objKey}' no encontrada";
                    $warnings++; Log::channel('sync')->warning("EDG1_Descuentos: => ".implode(', ', $detalle));
                    continue; // No insertamos este registro aún
                }

                // Insertar o actualizar registro
                $registro = DetalleDescuento::updateOrInsert(
                    [
                        'AbsEntry' => (int)$absEntry,
                        'ObjType'  => (string)$desc->ObjType,
                        'ObjKey'   => (string)$objKey,
                    ],
                    [
                        'Disctype' => 'D',
                        'Discount' => (float)$desc->Discount,
                    ]
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("EDG1_DescuentosDetalle: Error con registro AbsEntry '{$absEntry}' y la Marca ObjKey '{$objKey}' => ".$e->getMessage());
            }
        }
        return $this->aux('Descuentos Detalle', $total, $insertados, $errores, $warnings, $modo);
    }

    private function Vendedores($xmlResponse, $modo, $cli) //OSLP
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->VendedoresOSLP)) {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->VendedoresOSLP instanceof \stdClass) { $xmlResponse->VendedoresOSLP = [$xmlResponse->VendedoresOSLP]; }

        $total = count($xmlResponse->VendedoresOSLP); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        $excluidos = 0;

        foreach ($xmlResponse->VendedoresOSLP as $vendedor) {
            try {
                // Insertar o actualizar registro
                $registro = Vendedores::updateOrInsert(
                    [   'SlpCode' =>  $vendedor->SlpCode],
                    [
                        'SlpName' => $vendedor->SlpName,
                        'Active' => $vendedor->Active,
                    ]
                );
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OSLP_Vendedor: " . "Error con el vendedor: ".$vendedor->SlpName. "=> " . $e->getMessage());
            }           
        }
         return $this->aux('Vendedores', $total, $insertados, $errores, $warnings, $modo, $excluidos );
    }

    private function CambiosMoneda($xmlResponse, $modo, $cli)//ORTT 
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->TipoCambioORTT)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->TipoCambioORTT instanceof \stdClass) { $xmlResponse->TipoCambioORTT = [$xmlResponse->TipoCambioORTT]; }

        $total = count($xmlResponse->TipoCambioORTT); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        $fechasProcesadas = [];

        foreach ($xmlResponse->TipoCambioORTT as $moneda) {
            try {
                // Obtener Currency_ID desde OCRN
                $currency = Moneda::where('Currency', (string)$moneda->Currency)->first();
                if (!$currency) {
                    $warnings++; 
                    Log::channel('sync')->warning("ORTT_CambiosMonedas: Warning: Faltan las monedas ".$moneda->Currency." Por ingresar en el sistema");
                    // Si no existe la moneda, puedes saltarla o manejar el error
                    continue;
                }

                 
                $fechaStr = str_replace(['a. m.', 'p. m.', 'a.m.', 'p.m.', 'A. M.', 'P. M.'], ['am', 'pm', 'am', 'pm', 'am', 'pm'], (string)$moneda->RateDate);

                $fecha = Carbon::createFromFormat('d/m/Y h:i:s a', $fechaStr)->format('Y-m-d');
                $fechasProcesadas[] = $fecha;

                // Insertar o actualizar precio
                 $registro = MonedaCambio::updateOrInsert(
                    [
                        'Currency_ID' => $currency->Currency_ID ,
                        'RateDate' => $fecha,
                    ],
                    [
                        'Rate' => $moneda->Rate,
                    ]
                );
                 if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("ORTT_CambiosMonedas: " . "Error con la moneda: " . $currency->CurrName . "=> " . $e->getMessage());
            }
        }
        $monedaMXP = Moneda::where('Currency', 'MXP')->first();
        if ($monedaMXP) {
            foreach (array_unique($fechasProcesadas) as $fechaMXP) {
                MonedaCambio::updateOrInsert(
                    [
                        'Currency_ID' => $monedaMXP->Currency_ID,
                        'RateDate'    => $fechaMXP,
                    ],
                    [
                        'Rate' => 1,
                    ]
                );
            }
        } else {
            Log::channel('sync')->warning("ORTT_CambiosMonedas: La moneda MXP no existe en OCRN");
        }

        return $this->aux('Cambios de Monedas', $total, $insertados, $errores, $warnings, $modo);
    }

    private function CotizacionUpdate($xmlResponse, $modo, $cli) //OQUT coloca el DocNum en la cotizaciones o sea trae el numero de SAP de cada cotizacion 
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->No_Cotizacion_OQUT)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->No_Cotizacion_OQUT instanceof \stdClass) { $xmlResponse->No_Cotizacion_OQUT = [$xmlResponse->No_Cotizacion_OQUT]; }

        $total = count($xmlResponse->No_Cotizacion_OQUT); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xmlResponse->No_Cotizacion_OQUT as $cotizacion) {
            try {
                // actualizar registro
                $registro = Cotizacion::find($cotizacion->ID_COT_KombiShop);
                $registro->update([ 'DocNum' => $cotizacion->DocNum ]);

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OQUT: " . "Error al actualizar la cotizacion: ".$cotizacion->ID_COT_KombiShop. "=> " . $e->getMessage());
            }           
        }
         return $this->aux('Actualizacion del DocNum de Cotizaciones', $total, $insertados, $errores, $warnings, $modo );
    }

    private function CotizacionEstatus($xmlResponse, $modo, $cli) //OQUT coloca el estado de cada cotizacion en abierto o cerrado desde SAP
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->No_Estatus_Cotizacion_OQUT)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->No_Estatus_Cotizacion_OQUT instanceof \stdClass) { $xmlResponse->No_Estatus_Cotizacion_OQUT = [$xmlResponse->No_Estatus_Cotizacion_OQUT]; }

        $total = count($xmlResponse->No_Estatus_Cotizacion_OQUT); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xmlResponse->No_Estatus_Cotizacion_OQUT as $cotizacion) {
            try {
                // actualizar registro
                $registro = Cotizacion::find($cotizacion->ID_COT_KombiShop);
                if($registro->DocStatus === 'A'){
                    $registro->update([ 'DocStatus' => $cotizacion->DocStatus ]);
                }
                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OQUT: " . "Error al actualizar el estatus de la cotizacion: ".$cotizacion->ID_COT_KombiShop. "=> " . $e->getMessage());
            }           
        }
         return $this->aux('Actualizacion del estatus de la Cotizaciones', $total, $insertados, $errores, $warnings, $modo );
    }

    private function PedidoUpdate($xmlResponse, $modo, $cli)//ORDR coloca el DocNum en el pedido o sea trae el numero de SAP de cada pedido
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->No_Pedido_ORDR)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->No_Pedido_ORDR instanceof \stdClass) { $xmlResponse->No_Pedido_ORDR = [$xmlResponse->No_Pedido_ORDR]; }

        $total = count($xmlResponse->No_Pedido_ORDR); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xmlResponse->No_Pedido_ORDR as $pedido) {
            try {
                // actualizar registro
                $registro = Pedido::find($pedido->ID_COT_KombiShop);
                //$registro->update([ 'DocNum' => $pedido->DocNum ]);
                $data = ['DocNum' => $pedido->DocNum, ];

                if ($pedido->DocNum) {
                    $data['Status'] = 'Insertado en SAP';
                }

                $registro->update($data);

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("ORDR: " . "Error al actualizar el pedido: ".$pedido->ID_COT_KombiShop. "=> " . $e->getMessage());
            }           
        }
        $this->NoInsertados('SBO_Pedidos_NoInsertados', $modo, $cli);
         return $this->aux('Actualizacion del DocNum de Pedidos', $total, $insertados, $errores, $warnings, $modo );
    }

    private function NoInsertados($metodo, $modo, $cli) //Es un metodo aparte que se ejecuta cuando existen pedidos que no se insertaron en sap me manda todos es un complemento
    {                                                   //del metodo de PedidoUpdate
        $conexion = $this->ConexionWBS();

        if (!$conexion['success']) {
            Log::channel('sync')->error("Fallo de conexión con el servicio web: {$conexion['error']} en modo: {$modo}"); //Guarda el mensaje de error en los logs
            
            if ($cli) { echo $conexion['message'] . "\n"; return; }
            return redirect()->back()->with($conexion['type'], $conexion['message']); //Regresa a la pantalla la alerta del error
        }

        $client = $conexion['client'];

        $response = $client->$metodo(['parameters' => []]);
        $xmlResponse = $response->{$metodo.'Result'};
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->PedidosNoInsertados)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->PedidosNoInsertados instanceof \stdClass) { $xmlResponse->PedidosNoInsertados = [$xmlResponse->PedidosNoInsertados]; }

        foreach ($xmlResponse->PedidosNoInsertados as $pedido) {
            try {
                // actualizar registro
                $registro = Pedido::find($pedido->Code);
                $registro->update([ 'Status' => $pedido->U_Estatus ]);
            } catch (\Throwable $e) {
                Log::channel('sync')->error("ORDR: " . "Error al actualizar el estatus de SAP del pedido: ".$pedido->Code. "=> " . $e->getMessage());
            }           
        }
    }
    
    private function PedidoEstatus($xmlResponse, $modo, $cli) //OQUT coloca el estado de cada cotizacion en abierto o cerrado desde SAP
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->No_Estatus_Pedido_ORDR)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->No_Estatus_Pedido_ORDR instanceof \stdClass) { $xmlResponse->No_Estatus_Pedido_ORDR = [$xmlResponse->No_Estatus_Pedido_ORDR]; }

        $total = count($xmlResponse->No_Estatus_Pedido_ORDR); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        foreach ($xmlResponse->No_Estatus_Pedido_ORDR as $pedido) {
            try {
                // actualizar registro
                $registro = Pedido::find($pedido->ID_COT_KombiShop);
                $registro->update([ 'DocStatus' => $pedido->DocStatus ]);

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("ORDR: " . "Error al actualizar el estatus del pedido: ".$pedido->ID_COT_KombiShop. "=> " . $e->getMessage());
            }           
        }
         return $this->aux('Actualizacion del estatus del Pedido', $total, $insertados, $errores, $warnings, $modo );
    }


    private function stock($xmlResponse, $modo, $cli)//OITW agrega el stock de cada almacen 
    {
        //Aqui valido si existen datos en el xml antes de procesarlo
        if (!isset($xmlResponse->Stock)) 
        {
            if($cli){ echo "Datos no disponibles por el momento!!! \n"; return ; }
            else{ return [ 'tipo' => 'warning', 'msg'  => "Datos no disponibles por el momento!!!" ]; }
        }
        //Esta condicion es para cuando solo llega un elemento lo pueda convertir en arreglo y poderlo procesar
        if ($xmlResponse->Stock instanceof \stdClass) { $xmlResponse->Stock = [$xmlResponse->Stock]; }

        $total = count($xmlResponse->Stock); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xmlResponse->Stock as $Stock) {
            try {
                // insertat o actualizar registro
                $registro = Stock::updateOrInsert(
                    [   
                        'ItemCode' =>  $Stock->ItemCode,
                        'WhsCode'  =>  $Stock->WhsCode,
                    ],
                    [  'OnHand' => $Stock->OnHand, ]
                );

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OITW: " . "Error al actualizar el stock: ".$Stock->ItemCode. "=> " . $e->getMessage());
            }           
        }
         return $this->aux('Actualizacion de Stock', $total, $insertados, $errores, $warnings, $modo );
    }          

    //esta funcion se encarga de retornar los mensajes 
    //$servicio = nombre del servicio que se esta trabajando
    //$total = Total elementos del XML
    //$insertados = Contador de inserciones/actualizaciones exitosas
    //$errores = Contador de errores
    //$excluidos = esta variable es para los registros que usan clientes y entre sus datos traen datos de provedores esos son excluidos
    private function aux($servicio, $total, $insertados, $errores, $warnings=0, $modo, $excluidos=0)
    {
        Log::channel('sync')->notice("Informacion de {$servicio}; Insertados={$insertados}; total={$total}; errores={$errores}");
        
        // Caso 1: todo fue exitoso
        if ($total === $insertados && $errores === 0 && $warnings === 0) {
            return [
                'tipo' => 'success',
                'msg'  => "Sincronización de {$servicio} completada correctamente. En modo {$modo}."
            ];
        }

        //Caso 1.1: hubo exlucidos pero no errores
        if($excluidos > 0 && ($excluidos + $insertados === $total)) {
            return [
                'tipo' => 'success',
                'msg'  => "Sincronización de {$servicio} completada correctamente. En modo {$modo}."
            ];
        }

        // Caso 2: hubo advertencias (pero sin errores graves)
        if ($warnings > 0 && $errores === 0) {
            return [
                'tipo' => 'warning',
                'msg'  => "Faltan ".$warnings." datos base para continuar con la operacion. En modo {$modo}."
            ];
        }

        // Caso 3: hubo errores
        return [
            'tipo' => 'error',
            'msg'  => "Total de {$servicio} = $total, Insertados/Actualizados = $insertados. En modo {$modo}."
        ];
    }
}