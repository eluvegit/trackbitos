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
