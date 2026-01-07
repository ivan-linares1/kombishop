<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SincronizacionController;

class Sincronizar extends Command
{
    //tipo1 es el nombre del metodo a sincronizar
    //tipo2 es el modo en el que se van a sincronizarn ya sea de carga total o de carga de actualizados o del dia unicamente 
    protected $signature = 'sincronizar {tipo?} {tipo2?}';
    protected $description = 'Ejecuta sincronizaciones según el tipo especificado';

    public function handle()
    {
        $tipo = $this->argument('tipo'); 
        $tipo2 = $this->argument('tipo2');
        $controller = new SincronizacionController();

        if (!$tipo || !$tipo2) {
            $this->warn('⚠️ No se especificó tipo de sincronización o el modo de carga.');
            return;
        }

        $this->info("🔄 Iniciando sincronización de: $tipo ...");
        $this->comment('⏳ Por favor espere...');

        // Llamamos al método general ServicioWeb
        if($tipo2 === 'todo')
        {
            $metodo = [
                'Monedas' => 'SBO_Monedas_OCRN',
                'Marcas' => 'SBO_GPO_AgregaTodo_Marca_OITB',
                'Categoria_Lista_Precios' => 'SBO_CAT_LP_Agrega_Todo_OPLN',
                'Articulos' => 'SBO_Articulos_AgregaTodo_OITM',
                'Lista_Precios' => 'SBO_ListaPrecios_AgregaTodo_ITM1',
                'Clientes' =>'SBO_Clientes_Agrega_Todo_OCRD',
                'Direcciones' => 'SBO_Clientes_Agrega_Todo_Direcciones_CRD1',
                'Grupo_Descuentos' => 'SBO_Grupos_Agrgega_Todo_Descuentos_OEDG',
                'Descuentos_Detalle' => 'SBO_Grupos_Descuentos_EDG1',
                'Cambios_Monedas' => 'SBO_Tipo_Cambio_ORTT',
                'Vendedores' => 'SBO_Agrega_Todo_Vendedores_OSLP',
                'DocNum' => 'SBO_No_Coti_SAP_OQUT_TODAS',
                'stock' => 'SBO_Stock',
                'DocNumP' => 'SBO_No_Pedi_SAP_ORDR_TODAS',
                'CotizacionEstatus' => 'SBO_Estatus_Coti_SAP_OQUT_TODAS',
                'PedidoEstatus' => 'SBO_Estatus_Pedi_SAP_ORDR_TODAS'
            ];

            if (!isset($metodo[$tipo])) {
                $this->warn("⚠️ Tipo de sincronización '$tipo' no reconocido.");
                return;
            }
        }
        else if($tipo2 === 'update')
        {
            $metodo = [
                'Marcas' => 'SBO_GPO_Actualiza_Marca_OITB',
                'Categoria_Lista_Precios' => 'SBO_CAT_LP_Actualiza_OPLN',
                'Articulos' => 'SBO_Articulos_Actualiza_OITM',
                'Lista_Precios' => 'SBO_ListaPrecios_Actualiza_ITM1',
                'Clientes' =>'SBO_Clientes_Actualiza_OCRD',
                'Direcciones' => 'SBO_Clientes_Actualiza_Direcciones_CRD1',
                'Grupo_Descuentos' => 'SBO_Grupos_Actualiza_Descuentos_OEDG',
                'Descuentos_Detalle' => 'SBO_Grupos_Actualiza_Descuentos_EDG1',
                'Cambios_Monedas' => 'SBO_Actualiza_Tipo_Cambio_ORTT',
                'DocNum' => 'SBO_No_Coti_SAP_OQUT',
                'DocNumP' => 'SBO_No_Ped_SAP_ORDR',
                'stock' => 'SBO_Stock_Actualiza',
                'CotizacionEstatus' => 'SBO_Estatus_Coti_SAP_OQUT',
                'PedidoEstatus' => 'SBO_Estatus_Pedi_SAP_ORDR'
            ];
        }
        else
        {
            $this->warn("⚠️ Modo de carga '$tipo2' no reconocido.");
            return;
        }

        if (!isset($metodo[$tipo])) {
            $this->warn("⚠️ Tipo de sincronización '$tipo' no reconocido.");
            return;
        }

        $servicio = $tipo;
        $metodo = $metodo[$tipo];

        try {
            if( $servicio === 'Descuentos_Detalle' && $tipo2 === 'todo'){
                $controller->ServicioWebAux($servicio, $metodo, $tipo2, true); // true = CLI
            }
            else{ $controller->ServicioWeb($servicio, $metodo, $tipo2,true);} // true = CLI
            
            $this->info("✅ Finalizacion de la sincronización de: $tipo en el Modo de carga: $tipo2");
        } catch (\Throwable $e) {
            $this->error("❌ Error ejecutando la sincronización de $tipo: en el Modo de carga: $tipo2" . $e->getMessage());
        }

        return;
    }
}
