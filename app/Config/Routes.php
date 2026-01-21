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
    // Buscador y detalle de alimentos
    $r->get('alimentos', 'Diario::buscarAlimentos');       // /api/alimentos?q=...
    $r->get('alimentos/(:num)', 'Diario::alimento/$1');    // /api/alimentos/{id}

    // Ingestas
    $r->get('ingestas/(:segment)/(:segment)', 'Diario::ingestasAjax/$1/$2'); // /api/ingestas/{fecha}/{tipo}
    $r->post('add', 'Diario::addAjax');                     // /api/add
    $r->post('delete/(:num)', 'Diario::deleteAjax/$1');     // /api/delete/{id}
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
        $r->post('delete/(:num)', 'Alimentos::delete/$1');
    });



    // --- Recetas ---
    $routes->group('recetas', static function ($r) {
        $r->get('/', 'Recetas::index');
        $r->get('create', 'Recetas::create');
        $r->post('store', 'Recetas::store');
        $r->get('edit/(:num)', 'Recetas::edit/$1');
        $r->post('update/(:num)', 'Recetas::update/$1');
        $r->get('removeIngrediente/(:num)', 'Recetas::removeIngrediente/$1');
        $r->post('delete/(:num)',   'Recetas::delete/$1');
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
    // PESO
    $routes->group('peso', ['namespace' => 'App\Controllers\Comidas'], static function ($routes) {
        $routes->get('/',              'Peso::index');
        $routes->post('guardar',       'Peso::store');
        $routes->get('eliminar/(:num)', 'Peso::delete/$1');
        $routes->get('ultimo-mes',     'Peso::ultimoMesJson'); // opcional
    });
});


$routes->group('youtube', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/',                                'Youtube::index');
    $routes->match(['get', 'post'], 'crear',          'Youtube::crearLista');

    // Editar lista por slug
    $routes->get('(:segment)/editar',  'Youtube::editarLista/$1');
    $routes->post('(:segment)/editar', 'Youtube::actualizarLista/$1');


    // IMPORTAR (requiere slug)  /youtube/{slug}/importar
    $routes->get('(:segment)/importar',              'Youtube::importarForm/$1');
    $routes->post('(:segment)/importar',             'Youtube::importarProcesar/$1');

    // Catch-all de una sola pieza (ver lista)
    $routes->get('(:segment)',                       'Youtube::ver/$1');

    $routes->post('toggle-visto/(:num)',             'Youtube::toggleVisto/$1');
    $routes->post('toggle-relevante/(:num)',         'Youtube::toggleRelevante/$1');
    $routes->post('toggle-largo/(:num)',             'Youtube::toggleLargo/$1');

});



// app/Config/Routes.php

$routes->group('rodajes', ['filter' => 'auth'], static function ($routes) {

    // ---- Proyectos de rodaje ----
    $routes->get('/',                 'Rodajes::index');
    $routes->get('create',            'Rodajes::create');
    $routes->post('store',            'Rodajes::store');
    $routes->get('edit/(:num)',       'Rodajes::edit/$1');
    $routes->post('update/(:num)',    'Rodajes::update/$1');
    $routes->get('delete/(:num)',     'Rodajes::delete/$1');

    // ---- Escenas (anidadas bajo proyecto) ----
    $routes->get('(:num)/escenas',                        'RodajesEscenas::index/$1');
    $routes->get('(:num)/escenas/create',                 'RodajesEscenas::create/$1');
    $routes->post('(:num)/escenas/store',                 'RodajesEscenas::store/$1');
    $routes->get('(:num)/escenas/edit/(:num)',            'RodajesEscenas::edit/$1/$2');
    $routes->post('(:num)/escenas/update/(:num)',         'RodajesEscenas::update/$1/$2');
    $routes->get('(:num)/escenas/delete/(:num)',          'RodajesEscenas::delete/$1/$2');

    // Imágenes de referencia (borrado)
    $routes->get('(:num)/escenas/(:num)/imagen/delete/(:num)', 'RodajesEscenas::deleteImage/$1/$2/$3');

    // (Opcional) Reordenar escenas por AJAX (enviar JSON: [{id, orden}, ...])
    // Implementa RodajesEscenas::reordenar($proyectoId) si lo quieres usar.
    $routes->post('(:num)/escenas/reordenar',             'RodajesEscenas::reordenar/$1');

    // Ver escena (detalle)
    $routes->get('(:num)/escenas/show/(:num)', 'RodajesEscenas::show/$1/$2');
    // Storyboard
    $routes->get('(:num)/escenas/storyboard', 'RodajesEscenas::storyboard/$1');
    $routes->get('(:num)/escenas/ordenrodaje', 'RodajesEscenas::storyboardPorClasificacion/$1');
});


$routes->group('enlaces', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Enlaces::index');
    $routes->get('crear', 'Enlaces::crear');
    $routes->post('guardar', 'Enlaces::guardar');
    $routes->get('editar/(:num)', 'Enlaces::editar/$1');
    $routes->post('actualizar/(:num)', 'Enlaces::actualizar/$1');
    $routes->get('borrar/(:num)', 'Enlaces::borrar/$1');


    // gestión de categorías
    $routes->get('categorias', 'Enlaces::categorias');
    $routes->post('categorias/guardar', 'Enlaces::guardarCategoria');
    $routes->get('categorias/borrar/(:num)', 'Enlaces::borrarCategoria/$1');


    // gestión de etiquetas
    $routes->get('etiquetas', 'Enlaces::etiquetas');
    $routes->post('etiquetas/guardar', 'Enlaces::guardarEtiqueta');
    $routes->get('etiquetas/borrar/(:num)', 'Enlaces::borrarEtiqueta/$1');


    // ajax
    $routes->post('toggle-visto/(:num)', 'Enlaces::toggleVisto/$1');

    
    // Página tipo Notion:
    $routes->get('pagina/(:num)', 'Enlaces::pagina/$1');

    $routes->post('pagina/guardar/(:num)', 'Enlaces::guardarDoc/$1');        // guarda el HTML del editor en extra
    $routes->post('editor-upload/(:num)', 'Enlaces::editorUpload/$1');       // subida de imágenes del editor


    $routes->get('importar', 'Enlaces::importarForm');
    $routes->post('importar', 'Enlaces::importarUpload');

    $routes->get('revision',               'Enlaces::revision');              // dashboard de revisión
    $routes->get('revision/item',          'Enlaces::revisionItem');          // coge el primero pendiente
    $routes->get('revision/item/(:num)',   'Enlaces::revisionItem/$1');       // revisar uno concreto

    $routes->post('revision/guardar/(:num)', 'Enlaces::revisionGuardar/$1');  // guarda y va al siguiente
    $routes->post('revision/borrar/(:num)',  'Enlaces::revisionBorrar/$1');   // borra y va al siguiente
    $routes->post('revision/saltar/(:num)',  'Enlaces::revisionSaltar/$1');   // siguiente sin cambios

    

});

$routes->group('journal', ['filter' => 'auth'], static function ($routes) {
    // JOURNAL

    $routes->get('/', 'Journal::index');
    $routes->get('/view/(:num)', 'Journal::view/$1');
    $routes->match(['get','post'], '/edit/(:num)', 'Journal::edit/$1');
});