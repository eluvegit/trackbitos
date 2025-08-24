<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

/* LENTILLAS */
// Página principal de Lentillas
$routes->get('lentillas', 'Lentillas::index', ['filter' => 'auth']);

// Compras
$routes->match(['GET', 'POST'], 'lentillas/compras', 'Lentillas::compras', ['filter' => 'auth']);
$routes->get('lentillas/compras/editar/(:num)', 'Lentillas::editarCompra/$1', ['filter' => 'auth']);
$routes->post('lentillas/compras/actualizar/(:num)', 'Lentillas::actualizarCompra/$1', ['filter' => 'auth']);
$routes->delete('lentillas/compras/eliminar/(:num)', 'Lentillas::eliminarCompra/$1', ['filter' => 'auth']);

// Inventario
$routes->get('lentillas/stock', 'Lentillas::stock', ['filter' => 'auth']);
$routes->post('lentillas/stock/actualizar', 'Lentillas::actualizarStock', ['filter' => 'auth']);

// Sustituciones (GET y POST en la misma acción)
$routes->match(['GET', 'POST'], 'lentillas/sustituciones', 'Lentillas::sustituciones', ['filter' => 'auth']);
$routes->get('lentillas/sustituciones/editar/(:num)', 'Lentillas::editarSustitucion/$1', ['filter' => 'auth']);
$routes->post('lentillas/sustituciones/actualizar/(:num)', 'Lentillas::actualizarSustitucion/$1', ['filter' => 'auth']);
$routes->post('lentillas/sustituciones/eliminar/(:num)', 'Lentillas::eliminarSustitucion/$1', ['filter' => 'auth']);



// Avisos
$routes->match(['GET', 'POST'], 'lentillas/avisos', 'Lentillas::avisos', ['filter' => 'auth']);
$routes->match(['GET', 'POST'], 'lentillas/avisos/crear', 'Lentillas::crearAviso', ['filter' => 'auth']);
$routes->get('lentillas/avisos/editar/(:num)', 'Lentillas::editarAviso/$1', ['filter' => 'auth']);
$routes->post('lentillas/avisos/actualizar/(:num)', 'Lentillas::actualizarAviso/$1', ['filter' => 'auth']);
$routes->match(['GET', 'POST'], 'lentillas/avisos/eliminar/(:num)', 'Lentillas::eliminarAviso/$1', ['filter' => 'auth']);

// COCHE
$routes->group('coche', ['filter' => 'auth'], function ($routes) {

    $routes->get('/', 'Coche::index');

    // Acciones
    $routes->get('acciones', 'Coche::acciones');
    $routes->get('acciones/nueva', 'Coche::nuevaAccion');
    $routes->post('acciones/guardar', 'Coche::guardarAccion');
    $routes->get('acciones/editar/(:num)', 'Coche::editarAccion/$1');
    $routes->get('acciones/borrar/(:num)', 'Coche::borrarAccion/$1');

    $routes->get('acciones/rapida/(:segment)', 'Coche::accionRapida/$1');


    // Averías
    $routes->get('averias', 'Coche::averias');
    $routes->get('averias/nueva', 'Coche::nuevaAveria');
    $routes->post('averias/guardar', 'Coche::guardarAveria');
    $routes->get('averias/editar/(:num)', 'Coche::editarAveria/$1');
    $routes->get('averias/borrar/(:num)', 'Coche::borrarAveria/$1');

    // Recordatorios
    $routes->get('recordatorios', 'Coche::recordatorios');
    $routes->get('recordatorios/nuevo', 'Coche::nuevoRecordatorio');
    $routes->post('recordatorios/guardar', 'Coche::guardarRecordatorio');
    $routes->get('recordatorios/editar/(:num)', 'Coche::editarRecordatorio/$1');
    $routes->get('recordatorios/borrar/(:num)', 'Coche::borrarRecordatorio/$1');
});

// Compras
$routes->group('compras', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Compras::index');

    // Supermercados
    $routes->get('supermercados', 'Compras::supermercados');
    $routes->get('supermercados/nuevo', 'Compras::nuevoSupermercado');
    $routes->post('supermercados/guardar', 'Compras::crearSupermercado');
    $routes->get('supermercados/editar/(:num)', 'Compras::editarSupermercado/$1');
    $routes->post('supermercados/actualizar/(:num)', 'Compras::guardarSupermercado/$1');
    $routes->post('supermercados/(:num)/borrar', 'Compras::eliminarSupermercado');

    // Productos
    $routes->get('productos/(:num)', 'Compras::productos/$1');
    $routes->post('productos/nuevo', 'Compras::crearProducto');
    $routes->post('productos/(:num)/borrar', 'Compras::eliminarProducto/$1');

    $routes->get('(:num)/faltantes', 'Compras::faltantes/$1');
    $routes->get('(:num)/comprados', 'Compras::comprados/$1');
    $routes->post('limpiar/faltantes/(:num)', 'Compras::limpiarFaltantes/$1');



    // Precios
    $routes->post('precios/nuevo', 'Compras::crearPrecio');
    $routes->get('productos/editar/(:num)', 'Compras::editarProducto/$1');
    $routes->post('productos/(:num)/actualizar', 'Compras::actualizarProducto/$1');

    $routes->post('precios/(:num)/borrar', 'Compras::eliminarPrecio');

    // Estado de productos
    $routes->post('producto/(:num)/marcar-faltante', 'Compras::marcarFaltante/$1');
    $routes->post('producto/(:num)/marcar-comprado', 'Compras::marcarComprado/$1');
    $routes->post('producto/(:num)/desmarcar-faltante', 'Compras::desmarcarFaltante/$1');
    $routes->post('producto/(:num)/desmarcar-comprado', 'Compras::desmarcarComprado/$1');

    // Limpiar listas
    $routes->post('limpiar/faltantes/(:num)', 'Compras::limpiarFaltantes/$1');
    $routes->post('limpiar/comprados/(:num)', 'Compras::limpiarComprados/$1');
});


// GIMNASIO
$routes->group('gimnasio', ['filter' => 'auth'], function ($routes) {
    $routes->get('', 'Gimnasio::index');
    $routes->get('ejercicios', 'GimnasioEjercicios::index');
    $routes->get('ejercicios/create', 'GimnasioEjercicios::create');
    $routes->post('ejercicios/store', 'GimnasioEjercicios::store');
    $routes->get('ejercicios/edit/(:num)', 'GimnasioEjercicios::edit/$1');
    $routes->post('ejercicios/update/(:num)', 'GimnasioEjercicios::update/$1');
    $routes->post('ejercicios/delete/(:num)', 'GimnasioEjercicios::delete/$1');
    $routes->get('ejercicios/por-grupo/(:segment)', 'GimnasioEjercicios::porGrupo/$1');

    $routes->get('ejercicios/estadisticas/(:num)', 'GimnasioEjercicios::estadisticas/$1');
    $routes->get('ejercicios/estadisticas/(:num)', 'GimnasioEjercicios::estadisticas/$1');
    $routes->get('ejercicios/principales', 'GimnasioEjercicios::principales');


    $routes->get('entrenamientos', 'GimnasioEntrenamientos::index');
    $routes->post('entrenamientos/crear', 'GimnasioEntrenamientos::crear');
    $routes->get('entrenamientos/eliminar/(:num)', 'GimnasioEntrenamientos::eliminar/$1');
    $routes->post('entrenamientos/actualizar-datos/(:num)', 'GimnasioEntrenamientos::actualizarDatos/$1');
    $routes->get('entrenamientos/registro/(:segment)', 'GimnasioEntrenamientos::registro/$1');
    $routes->post('entrenamientos/guardar-serie', 'GimnasioEntrenamientos::guardarSerie');
    $routes->get('entrenamientos/eliminar-serie/(:num)', 'GimnasioEntrenamientos::eliminarSerie/$1');
    $routes->post('entrenamientos/actualizar-serie/(:num)', 'GimnasioEntrenamientos::actualizarSerie/$1');


    $routes->get('entrenamientos/resumen/(:num)', 'GimnasioEntrenamientos::resumen/$1');
});


// MESOCICLOS
$routes->group('gimnasio', ['filter' => 'auth'], static function ($r) {
    $r->get('mesociclos',                                       'GimnasioMesociclos::index');
    $r->match(['get', 'post'],   'mesociclos/nuevo',             'GimnasioMesociclos::nuevo');
    $r->get('mesociclos/(:num)',                                'GimnasioMesociclos::ver/$1');
    $r->match(['get', 'post'], 'mesociclos/(:num)/bloque/nuevo',  'GimnasioMesociclos::bloqueNuevo/$1');
    $r->get('mesociclos/(:num)/simplificado',                   'GimnasioMesociclos::simplificado/$1');
    $r->match(['get', 'post'], 'mesociclos/(:num)/asignar/(:num)', 'GimnasioMesociclos::asignar/$1/$2');


    $r->match(['get', 'post'], 'mesociclos/(:num)/generar', 'GimnasioMesociclos::generar/$1');   // genera lote
    $r->post('mesociclos/bloque/(:num)/hecho', 'GimnasioMesociclos::marcarHecho/$1');

    // Paso previo: formulario de ajuste (solo se permite si no quedan pendientes)
    $r->get('mesociclos/(:num)/ajustar', 'GimnasioMesociclos::ajustar/$1');
    $r->post('mesociclos/(:num)/ajustar', 'GimnasioMesociclos::ajustarPost/$1');

    // BILBO
    $r->match(['get', 'post'], 'mesociclos/(:num)/generar/bilbo', 'GimnasioMesociclos::generarBilbo/$1');
});


// --- API para AJAX del diario ---
$routes->group('api', ['filter' => 'auth', 'namespace' => 'App\Controllers\Comidas'], static function ($r) {
    $r->get('alimentos', 'Diario::buscarAlimentos'); // /api/alimentos?q=...
    $r->get('ingestas/(:segment)/(:segment)', 'Diario::ingestasAjax/$1/$2'); // /api/ingestas/{fecha}/{tipo}
    $r->post('add', 'Diario::addAjax');             // /api/add
    $r->post('delete/(:num)', 'Diario::deleteAjax/$1'); // /api/delete/{id}
});


// === Rutas módulo Comidas (app/Config/Routes.php) ===
$routes->group('comidas', ['filter' => 'auth', 'namespace' => 'App\Controllers\Comidas'], static function ($routes) {

    // --- Diario ---
    $routes->group('diario', static function ($r) {
        $r->get('hoy', 'Diario::hoy');
        $r->get('porciones/(:num)', 'Diario::porciones/$1'); // AJAX porciones
        $r->get('(:segment)/nutrientes', 'Diario::nutrientes/$1');


        // rutas CRUD
        $r->post('add', 'Diario::add');
        $r->post('edit/(:num)', 'Diario::edit/$1');
        $r->post('delete/(:num)', 'Diario::delete/$1');

        // Diario con fecha dinámica (YYYY-MM-DD)
        // ⚠️ importante: de más específico a más genérico
        $r->get('(:segment)/seleccionar-tipo', 'Diario::seleccionarTipo/$1');
        $r->get('(:segment)/(:segment)', 'Diario::verTipo/$1/$2');
        $r->get('(:segment)', 'Diario::ver/$1');
    });




    // --- Alimentos ---
    $routes->group('alimentos', static function ($r) {
        $r->get('/', 'Alimentos::index');
        $r->get('create', 'Alimentos::create');
        $r->post('store', 'Alimentos::store');
        $r->get('edit/(:num)', 'Alimentos::edit/$1');
        $r->post('update/(:num)', 'Alimentos::update/$1');
        $r->post('preview', 'Alimentos::preview');

    });

    // --- Recetas ---
    $routes->group('recetas', static function ($r) {
        $r->get('/', 'Recetas::index');
        $r->get('create', 'Recetas::create');
        $r->post('store', 'Recetas::store');
        $r->get('edit/(:num)', 'Recetas::edit/$1');
        $r->post('update/(:num)', 'Recetas::update/$1');
        $r->get('removeIngrediente/(:num)', 'Recetas::removeIngrediente/$1');
    });

    // --- Objetivos ---
    $routes->group('objetivos', static function ($r) {
        $r->get('/', 'Objetivos::index');
        $r->get('create', 'Objetivos::create');
        $r->post('store', 'Objetivos::store');
        $r->get('edit/(:num)', 'Objetivos::edit/$1');
        $r->post('update/(:num)', 'Objetivos::update/$1');
        $r->get('delete/(:num)', 'Objetivos::delete/$1');
    });

    // --- Porciones ---
    $routes->group('porciones', static function ($r) {
        $r->get('alimento/(:num)', 'Porciones::index/$1');   // listar porciones de un alimento
        $r->get('create/(:num)',  'Porciones::create/$1');   // form nueva porción
        $r->post('store',         'Porciones::store');       // guardar nueva
        $r->get('edit/(:num)',    'Porciones::edit/$1');     // form editar
        $r->post('update/(:num)', 'Porciones::update/$1');   // actualizar
        $r->get('delete/(:num)',  'Porciones::delete/$1');   // eliminar
    });
});
