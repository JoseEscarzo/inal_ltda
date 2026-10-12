<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\IngresoRevisionController;
use App\Http\Controllers\SalidaRevisionController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\LineaProduccionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\InventarioProductoController;
use App\Http\Controllers\DistribucionController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\CamaraController;
use App\Http\Controllers\ProveedorController;

Route::get('/lentes', function () {
    return response()->json([
        ['id' => 1, 'marca' => 'Ray-Ban', 'precio' => 150],
        ['id' => 2, 'marca' => 'Oakley',  'precio' => 200],
    ]);
});

Route::get('/cristales', function () {
    return response()->json([
        ['id' => 1, 'tipo' => 'Blue-Cut', 'precio' => 100],
        ['id' => 2, 'tipo' => 'Normal',   'precio' => 50],
    ]);
});

Route::prefix('v1')->group(function () {

    Route::post('/register',             [AuthController::class, 'register']);
    Route::post('/login',               [AuthController::class, 'login'])->middleware('throttle:3,1');
    Route::post('/login/verify-device',  [AuthController::class, 'verifyLoginDevice']);
    Route::post('/verify-otp',           [AuthController::class, 'verifyOtp']);
    Route::get('/facturas/verificar/{codigo}', [FacturaController::class, 'verificar']);


    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', function (Request $request) {
            return $request->user();
        });

        Route::get('/roles', [UserController::class, 'getRoles']);


        Route::get   ('/users/inactivos',          [UserController::class, 'inactivos']);
        Route::patch ('/users/{user}/activar',     [UserController::class, 'activar']);
        Route::delete('/users/{user}/definitivo',  [UserController::class, 'eliminarDefinitivo']);
        Route::apiResource('users', UserController::class);

        Route::get   ('/productos/inactivos',              [ProductoController::class, 'inactivos']);
        Route::patch ('/productos/{producto}/activar',     [ProductoController::class, 'activar']);
        Route::delete('/productos/{producto}/definitivo',  [ProductoController::class, 'eliminarDefinitivo']);
        Route::apiResource('productos', ProductoController::class)
            ->parameters(['productos' => 'producto']);
//Reportes
        Route::get('/reportes/productos/pdf', [ReporteController::class, 'productosPDF']);
        Route::get('/reportes/productos/excel', [ReporteController::class, 'productosExcel']);
        Route::get('/reportes/destinos/pdf', [ReporteController::class, 'destinosPDF']);
        Route::get('/reportes/destinos/excel', [ReporteController::class, 'destinosExcel']);
        Route::get('/reportes/inventario/pdf', [ReporteController::class, 'inventarioPDF']);
        Route::get('/reportes/inventario/excel', [ReporteController::class, 'inventarioExcel']);
        Route::get('/reportes/usuarios/pdf', [ReporteController::class, 'usuariosPDF']);
        Route::get('/reportes/usuarios/excel', [ReporteController::class, 'usuariosExcel']);
        Route::get('/reportes/distribuciones/pdf', [ReporteController::class, 'distribucionesPDF']);
        Route::get('/reportes/distribuciones/excel', [ReporteController::class, 'distribucionesExcel']);
        Route::get('/reportes/salidas/{salidaRevision}/recibo/pdf', [ReporteController::class, 'reciboSalidaPDF']);
        Route::get('/reportes/lotes/pdf', [ReporteController::class, 'lotesPDF']);
        Route::get('/reportes/lotes/excel', [ReporteController::class, 'lotesExcel']);
        Route::get('/reportes/lineas/pdf', [ReporteController::class, 'lineasPDF']);
        Route::get('/reportes/lineas/excel', [ReporteController::class, 'lineasExcel']);
        Route::get('/reportes/trazabilidad/pdf', [ReporteController::class, 'trazabilidadPDF']);
        Route::get('/reportes/trazabilidad/excel', [ReporteController::class, 'trazabilidadExcel']);
//
        Route::get('/inventario/inactivos', [InventarioProductoController::class, 'inactivos']);
        Route::patch('/inventario/{inventarioProducto}/activar', [InventarioProductoController::class, 'activar']);
        Route::delete('/inventario/{inventarioProducto}/definitivo', [InventarioProductoController::class, 'eliminarDefinitivo']);
        Route::apiResource('inventario', InventarioProductoController::class)
            ->parameters(['inventario' => 'inventarioProducto'])
            ->except(['show']);

        Route::apiResource('ingresos', IngresoRevisionController::class)
            ->parameters(['ingresos' => 'ingresoRevision'])
            ->except(['update']);

        Route::apiResource('salidas', SalidaRevisionController::class)
            ->parameters(['salidas' => 'salidaRevision'])
            ->except(['update']);
        Route::post('/salidas/{salidaRevision}/aprobar', [SalidaRevisionController::class, 'aprobar']);
        Route::post('/salidas/{salidaRevision}/rechazar', [SalidaRevisionController::class, 'rechazar']);
        Route::get('/alertas', [AlertaController::class, 'index']);

        Route::apiResource('clientes', ClienteController::class)
            ->parameters(['clientes' => 'cliente']);
        Route::get('/facturas', [FacturaController::class, 'index']);
        Route::post('/facturas/desde-salida/{salidaRevision}', [FacturaController::class, 'storeFromSalida']);
        Route::get('/facturas/{factura}', [FacturaController::class, 'show']);
        Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'pdf']);
        Route::get('/facturas/{factura}/ticket', [FacturaController::class, 'ticket']);
        Route::post('/facturas/{factura}/enviar-correo', [FacturaController::class, 'enviarCorreo']);
        Route::get('/pagos', [PagoController::class, 'index']);
        Route::post('/facturas/{factura}/pagos', [PagoController::class, 'store']);

        Route::get   ('/destinos/inactivos',              [DestinoController::class, 'inactivos']);
        Route::patch ('/destinos/{destino}/archivar',     [DestinoController::class, 'archivar']);
        Route::patch ('/destinos/{destino}/activar',      [DestinoController::class, 'activar']);
        Route::delete('/destinos/{destino}/definitivo',   [DestinoController::class, 'destroy']);
        Route::apiResource('destinos', DestinoController::class)
            ->parameters(['destinos' => 'destino'])
            ->except(['destroy']);

        Route::apiResource('lotes', LoteController::class)
            ->parameters(['lotes' => 'lote']);
        Route::patch('/lotes/{lote}/activar', [LoteController::class, 'activar']);

        Route::apiResource('lineas', LineaProduccionController::class)
            ->parameters(['lineas' => 'lineaProduccion']);

        Route::apiResource('trazabilidad', DistribucionController::class)
            ->parameters(['trazabilidad' => 'distribucion']);

        Route::apiResource('camaras', CamaraController::class)
            ->parameters(['camaras' => 'camara']);

        Route::get('/proveedores/inactivos', [ProveedorController::class, 'inactivos']);
        Route::patch('/proveedores/{proveedor}/archivar', [ProveedorController::class, 'archivar']);
        Route::patch('/proveedores/{proveedor}/activar', [ProveedorController::class, 'activar']);
        Route::delete('/proveedores/{proveedor}/definitivo', [ProveedorController::class, 'destroy']);
        Route::apiResource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedor'])
            ->except(['destroy']);
    });
});
