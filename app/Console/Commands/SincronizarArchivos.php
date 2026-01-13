<?php

namespace App\Console\Commands;

use App\Http\Controllers\Archivos_Sincronizasores;
use Illuminate\Console\Command;

class SincronizarArchivos extends Command
{
    protected $signature = 'sincronizar-archivos {metodo?} {ruta?}';
    protected $description = 'Sincroniza archivos XML llamando al método y ruta especificados';

    public function handle()
    {
        $metodo = $this->argument('metodo');
        $ruta = $this->argument('ruta');
        $controller = new Archivos_Sincronizasores();

        $this->info("🔄 Iniciando sincronización de: $metodo ...");
        $this->comment('⏳ Por favor espere...');

        switch($metodo)
        {
            case 'Cambios_Monedas': $controller->CambiosMoneda($ruta, true); break;
            case 'Monedas': $controller->Monedas($ruta, true); break;
            case 'Articulos': $controller->Articulos($ruta, true); break;
            case 'Clientes': $controller->Clientes($ruta, true); break;
            case 'Vendedores': $controller->Vendedores($ruta, true); break;
            case 'Categoria_Lista_Precios': $controller->Categoria_Lista_Precios($ruta, true); break;
            case 'Direcciones': $controller->Direcciones($ruta, true); break;
            case 'CotizacionEstatus': $controller->CotizacionEstatus($ruta, true); break;
            case 'PedidoEstatus': $controller->PedidoEstatus($ruta, true); break;
            case 'Marcas': $controller->Marcas($ruta, true); break;
            default: $this->error("❌ Tipo de sincronización '$metodo' no reconocido."); return;
        }

        $this->info("✅ Finalizacion de la sincronización de: $metodo");
    }

}
