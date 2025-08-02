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





