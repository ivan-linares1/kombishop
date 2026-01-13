<?php
namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Clientes;
use App\Models\Cotizacion;
use App\Models\Descuento;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Archivos_Sincronizasores extends Controller
{
    /*FUNCIONES PRIVADA DE USO EXCLUSIVO PARA LEER LOS ARCHIVOS*/
    private function Archivos($rutaXml, $CLI)
    {
        if (!file_exists($rutaXml)) {
            if($CLI) { echo "Archivo XML no encontrado\n"; return false;}
            return ['error' => 'Archivo XML no encontrado'];
        }

        $xml = simplexml_load_file($rutaXml);

        if (!$xml) {
            if($CLI) { echo "No se pudo cargar el XML\n"; return false; }
            return ['error' => 'No se pudo cargar el XML'];
        }

        return $xml;
    }

    private function Mensajes($total, $insertados, $errores, $CLI)
    {
        if ($errores > 0 && $insertados > 0) {
            $msg = "Proceso terminado con errores. Insertados: $insertados, Errores: $errores";
            if($CLI) { fwrite(STDERR, "\033[33mWARNING:\033[0m $msg" . PHP_EOL); return; }
            return ['warning' => $msg];
        }
        else if ($errores == $total || $insertados == 0) {
            $msg = "Proceso terminado con error total";
            if($CLI) {  fwrite(STDERR, "\033[31mERROR:\033[0m $msg" . PHP_EOL); exit(1); }
            return ['error' => $msg];
        }
        else if($insertados === $total){
            $msg = "Proceso completado correctamente. Total: $total, Insertados: $insertados";
            if($CLI) { echo "\033[32mSUCCESS:\033[0m $msg" . PHP_EOL; return; }
            return ['success' => $msg];
        }

         $msg = "ERROR INESPERADO";
        if($CLI) { fwrite(STDERR, "\033[31mERROR:\033[0m $msg" . PHP_EOL); exit(1); }
        return ['error' => $msg];
    }
    /*****************************************************************/

   
    public function ServicioWeb(Request $request, $servicio)
    {
        $ruta = $request->input('ruta');

        // Validación simple
        if (!$ruta) {
            return redirect()->back()->with('error', 'No se proporcionó la ruta del archivo XML.');
        }

        switch ($servicio) {
            case 'Monedas': return $this->Monedas($ruta, false);
            case 'Articulos': return $this->Articulos($ruta, false); 
            case 'Categoria_Lista_Precios': return $this->Categoria_Lista_Precios($ruta, false); 
            case 'Marcas': return $this->Marcas($ruta, false);
            case '#': return $this->ListaPrecio($ruta, false);
            case 'Clientes': return $this->Clientes($ruta, false);
            case 'Direcciones': return $this->Direcciones($ruta, false);
            case 'Grupo_Descuentos': return $this->Grupo_Descuentos($ruta, false);

            case 'Vendedores': return $this->Vendedores($ruta, false);
            case 'Cambios_Monedas': return $this->CambiosMoneda($ruta, false);      
            case '#': return $this->CotizacionUpdate($ruta, false); 
            case 'CotizacionEstatus': return $this->CotizacionEstatus($ruta, false); 


            case 'PedidoEstatus': return $this->PedidoEstatus($ruta, false); 
            case '#': return $this->stock($ruta, false);            
            default: return redirect()->back()->with('error', 'Servicio no encontrado.');
        }
    }

    public function Monedas($rutaXml, $CLI)//OCRN
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->Moneda)) { if($CLI) { echo "XML sin monedas\n"; return; }
            return redirect()->back()->with('warning', 'XML sin monedas');
        }

        if (!is_array($xml->Moneda) && !($xml->Moneda instanceof \Traversable)) {
            $xml->Moneda = [$xml->Moneda];
        }

        $total = count($xml->Moneda);
        $insertados = 0;
        $errores = 0;

        foreach ($xml->Moneda as $moneda) {
            try {
                $registro = Moneda::updateOrCreate( 
                    ['Currency' => (string) $moneda->CurrCode],
                    [ 'CurrName'   => (string) $moneda->CurrName],
                );
                if($registro){ $insertados++;}
            } catch (\Throwable $e) {
                $errores++;
                Log::channel('sync')->error("Clientes XML ({$rutaXml}) - {$moneda->CurrCode} => " . $e->getMessage());
            }
        }

        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    public function Articulos($rutaXml, $CLI) //OITM
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->Articulo)) { if($CLI) { echo "XML sin articulos\n"; return; }
            return redirect()->back()->with('warning', 'XML sin articulos');
        }

        if (!is_array($xml->Articulo) && !($xml->Articulo instanceof \Traversable)) {
            $xml->Articulo = [$xml->Articulo];
        }

        $total = count($xml->Articulo); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        
        foreach ($xml->Articulo as $art) {
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
        
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        } 
    }

    public function Categoria_Lista_Precios($rutaXml, $CLI)//ITM1
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->CAT_LP)) { if($CLI) { echo "XML sin lista de precios\n"; return; }
            return redirect()->back()->with('warning', 'XML sin lista de precios');
        }

        if (!is_array($xml->CAT_LP) && !($xml->CAT_LP instanceof \Traversable)) {
            $xml->CAT_LP = [$xml->CAT_LP];
        }

        $total = count($xml->CAT_LP); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xml->CAT_LP as $lista) {
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
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    public function Marcas($rutaXml, $CLI) //OITB
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->Marcas)) { if($CLI) { echo "XML sin marcas\n"; return; }
            return redirect()->back()->with('warning', 'XML sin marcas');
        }

        if (!is_array($xml->Marcas) && !($xml->Marcas instanceof \Traversable)) {
            $xml->Marcas = [$xml->Marcas];
        }

        $total = count($xml->Marcas);
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores


        foreach ($xml->Marcas as $marca) {
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
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    private function ListaPrecio($rutaXml, $CLI)//ITM1 DE CADA ARTICULO
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->ListaP)) { if($CLI) { echo "XML sin lista de precio\n"; return; }
            return redirect()->back()->with('warning', 'XML sin lista de precio');
        }

        if (!is_array($xml->ListaP) && !($xml->ListaP instanceof \Traversable)) {
            $xml->ListaP = [$xml->ListaP];
        }

        $total = count($xml->ListaP);
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        foreach ($xml->ListaP as $precio) {
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
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    public function Clientes($rutaXml, $CLI)//OCRD
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        

        if (!isset($xml->Cliente)) { if($CLI) { echo "XML sin clientes\n"; return; }
            return redirect()->back()->with('warning', 'XML sin clientes');
        }

        if (!is_array($xml->Cliente) && !($xml->Cliente instanceof \Traversable)) {
            $xml->Cliente = [$xml->Cliente];
        }

        $total = count($xml->Cliente);
        $insertados = 0;
        $errores = 0;

        foreach ($xml->Cliente as $cliente) {
            try {
                $registro = Clientes::updateOrInsert(
                    ['CardCode' => (string) $cliente->CardCode],
                    [
                        'CardName' => (string) $cliente->CardName,
                        'GroupNum' => (int) $cliente->GroupNum,
                        'phone1'   => (string) $cliente->Phone1,
                        'e-mail'    => (string) $cliente->Email,
                        'Active'   => (string) $cliente->ValidFor,
                    ]
                );

                if ($registro) {
                    $insertados++;
                }
            } catch (\Throwable $e) {
                $errores++;
                Log::channel('sync')->error("Clientes XML ({$rutaXml}) - {$cliente->CardCode} => " . $e->getMessage());
            }
        }

        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    public function Direcciones($rutaXml, $CLI) // CRD1
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->Direcciones)) { if($CLI) { echo "XML sin lista de precios\n"; return; }
            return redirect()->back()->with('warning', 'XML sin lista de precios');
        }

        if (!is_array($xml->Direcciones) && !($xml->Direcciones instanceof \Traversable)) {
            $xml->Direcciones = [$xml->Direcciones];
        }

        $total = count($xml->Direcciones); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        $excluidos = 0;

        foreach ($xml->Direcciones as $direccion){
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
        $mesajes = $this->Mensajes($total, $insertados+$excluidos, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    public function Grupo_Descuentos($rutaXml, $CLI) //OEDG
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->GPO_Descuentos)) { if($CLI) { echo "XML sin GPO de Descuentos\n"; return; }
            return redirect()->back()->with('warning', 'XML sin GPO de Descuentos');
        }

        if (!is_array($xml->GPO_Descuentos) && !($xml->GPO_Descuentos instanceof \Traversable)) {
            $xml->GPO_Descuentos = [$xml->GPO_Descuentos];
        }

        $total = count($xml->GPO_Descuentos); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;
        $excluidos = 0;

        foreach ($xml->GPO_Descuentos as $GPO_Descuento) {
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
        
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    /*PENDIENTE LOS DE DESCUENTOS  EDG1*/

    public function Vendedores($rutaXml, $CLI) //OSLP
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->VendedoresOSLP)) { if($CLI) { echo "XML sin monedas\n"; return; }
            return redirect()->back()->with('warning', 'XML sin monedas');
        }

        if (!is_array($xml->VendedoresOSLP) && !($xml->VendedoresOSLP instanceof \Traversable)) {
            $xml->VendedoresOSLP = [$xml->VendedoresOSLP];
        }

        $total = count($xml->VendedoresOSLP); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xml->VendedoresOSLP as $vendedor) {
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
        
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    public function CambiosMoneda($rutaXml, $CLI)//ORTT 
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->TipoCambioORTT)) { if($CLI) { echo "XML sin monedas\n"; return; }
            return redirect()->back()->with('warning', 'XML sin monedas');
        }

        if (!is_array($xml->TipoCambioORTT) && !($xml->TipoCambioORTT instanceof \Traversable)) {
            $xml->TipoCambioORTT = [$xml->TipoCambioORTT];
        }

        $total = count($xml->TipoCambioORTT); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores
        $warnings = 0;

        $fechasProcesadas = [];

        foreach ($xml->TipoCambioORTT as $moneda) {
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

        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    private function CotizacionUpdate($rutaXml, $CLI) //OQUT coloca el DocNum en la cotizaciones o sea trae el numero de SAP de cada cotizacion 
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->No_Cotizacion_OQUT)) { if($CLI) { echo "XML sin datos\n"; return; }
            return redirect()->back()->with('warning', 'XML sin datos');
        }

        if (!is_array($xml->No_Cotizacion_OQUT) && !($xml->No_Cotizacion_OQUT instanceof \Traversable)) {
            $xml->No_Cotizacion_OQUT = [$xml->No_Cotizacion_OQUT];
        }

        $total = count($xml->No_Cotizacion_OQUT);
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xml->No_Cotizacion_OQUT as $cotizacion) {
            try {
                // actualizar registro
                $registro = Cotizacion::find($cotizacion->ID_COT_KombiShop);
                $registro->update([ 'DocNum' => $cotizacion->DocNum ]);

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("OQUT: " . "Error al actualizar la cotizacion: ".$cotizacion->ID_COT_KombiShop. "=> " . $e->getMessage());
            }           
        }
         $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }  

    public function CotizacionEstatus($rutaXml, $CLI) //OQUT coloca el estado de cada cotizacion en abierto o cerrado desde SAP
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->No_Estatus_Cotizacion_OQUT)) { if($CLI) { echo "XML sin datos\n"; return; }
            return redirect()->back()->with('warning', 'XML sin datos');
        }

        if (!is_array($xml->No_Estatus_Cotizacion_OQUT) && !($xml->No_Estatus_Cotizacion_OQUT instanceof \Traversable)) {
            $xml->No_Estatus_Cotizacion_OQUT = [$xml->No_Estatus_Cotizacion_OQUT];
        }

        $total = count($xml->No_Estatus_Cotizacion_OQUT); // Total elementos del XML
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xml->No_Estatus_Cotizacion_OQUT as $cotizacion) {
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
        
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }

    /*PENDIENTES LOS PEDIDDOS UPDATE Y EL NOINSERTADOS*/
    
    public function PedidoEstatus($rutaXml, $CLI) //OQUT coloca el estado de cada cotizacion en abierto o cerrado desde SAP
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->No_Estatus_Pedido_ORDR)) { if($CLI) { echo "XML sin datos\n"; return; }
            return redirect()->back()->with('warning', 'XML sin datos');
        }

        if (!is_array($xml->No_Estatus_Pedido_ORDR) && !($xml->No_Estatus_Pedido_ORDR instanceof \Traversable)) {
            $xml->No_Estatus_Pedido_ORDR = [$xml->No_Estatus_Pedido_ORDR];
        }

        $total = count($xml->No_Estatus_Pedido_ORDR);
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xml->No_Estatus_Pedido_ORDR as $pedido) {
            try {
                // actualizar registro
                $registro = Pedido::find($pedido->ID_COT_KombiShop);
                $registro->update([ 'DocStatus' => $pedido->DocStatus ]);

                if($registro){ $insertados++;}// Si se inserta un nuevo registro o se actualiza, contamos como exitoso.
            } catch (\Throwable $e) {
                $errores++; Log::channel('sync')->error("ORDR: " . "Error al actualizar el estatus del pedido: ".$pedido->ID_COT_KombiShop. "=> " . $e->getMessage());
            }           
        }
        
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    }


    private function stock($rutaXml, $CLI)//OITW agrega el stock de cada almacen 
    {
        $xml = $this->Archivos($rutaXml, $CLI);

        if ($xml === false) return;//muestra errores en la carga del xml
        if (is_array($xml) && isset($xml['error'])) { return redirect()->back()->with('error', $xml['error']); } //Errores en WEB del xml
        
        
        if (!isset($xml->Stock)) { if($CLI) { echo "XML sin stock\n"; return; }
            return redirect()->back()->with('warning', 'XML sin stock');
        }

        if (!is_array($xml->Stock) && !($xml->Stock instanceof \Traversable)) {
            $xml->Stock = [$xml->Stock];
        }

        $total = count($xml->Stock);
        $insertados = 0; // Contador de inserciones/actualizaciones exitosas
        $errores = 0;   // Contador de errores

        foreach ($xml->Stock as $Stock) {
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
        
        $mesajes = $this->Mensajes($total, $insertados, $errores, $CLI);
        if ($CLI == false) {
            // para web
            if (isset($mesajes['warning'])) return redirect()->back()->with('warning', $mesajes['warning']);
            if (isset($mesajes['error'])) return redirect()->back()->with('error', $mesajes['error']);
            if (isset($mesajes['success'])) return redirect()->back()->with('success', $mesajes['success']);
        }
    } 

}
