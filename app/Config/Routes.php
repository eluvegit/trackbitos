<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->GET('/', 'Home::index');

$routes->GET('dashboard', 'Dashboard::index', ['filter' => 'auth']);

/* LENTILLAS */
// Página principal de Lentillas
$routes->GET('lentillas', 'Lentillas::index', ['filter' => 'auth']);

// Compras
$routes->match(['GET', 'POST'], 'lentillas/compras', 'Lentillas::compras', ['filter' => 'auth']);
$routes->GET('lentillas/compras/editar/(:num)', 'Lentillas::editarCompra/$1', ['filter' => 'auth']);
$routes->POST('lentillas/compras/actualizar/(:num)', 'Lentillas::actualizarCompra/$1', ['filter' => 'auth']);
$routes->delete('lentillas/compras/eliminar/(:num)', 'Lentillas::eliminarCompra/$1', ['filter' => 'auth']);

// Inventario
$routes->GET('lentillas/stock', 'Lentillas::stock', ['filter' => 'auth']);
$routes->POST('lentillas/stock/actualizar', 'Lentillas::actualizarStock', ['filter' => 'auth']);

// Sustituciones (GET y POST en la misma acción)
$routes->match(['GET', 'POST'], 'lentillas/sustituciones', 'Lentillas::sustituciones', ['filter' => 'auth']);
$routes->GET('lentillas/sustituciones/editar/(:num)', 'Lentillas::editarSustitucion/$1', ['filter' => 'auth']);
$routes->POST('lentillas/sustituciones/actualizar/(:num)', 'Lentillas::actualizarSustitucion/$1', ['filter' => 'auth']);
$routes->POST('lentillas/sustituciones/eliminar/(:num)', 'Lentillas::eliminarSustitucion/$1', ['filter' => 'auth']);



// Avisos
$routes->match(['GET', 'POST'], 'lentillas/avisos', 'Lentillas::avisos', ['filter' => 'auth']);
$routes->match(['GET', 'POST'], 'lentillas/avisos/crear', 'Lentillas::crearAviso', ['filter' => 'auth']);
$routes->GET('lentillas/avisos/editar/(:num)', 'Lentillas::editarAviso/$1', ['filter' => 'auth']);
$routes->POST('lentillas/avisos/actualizar/(:num)', 'Lentillas::actualizarAviso/$1', ['filter' => 'auth']);
$routes->match(['GET', 'POST'], 'lentillas/avisos/eliminar/(:num)', 'Lentillas::eliminarAviso/$1', ['filter' => 'auth']);

// COCHE
$routes->group('coche', ['filter' => 'auth'], function ($routes) {

    $routes->GET('/', 'Coche::index');

    // Acciones
    $routes->GET('acciones', 'Coche::acciones');
    $routes->GET('acciones/nueva', 'Coche::nuevaAccion');
    $routes->POST('acciones/guardar', 'Coche::guardarAccion');
    $routes->GET('acciones/editar/(:num)', 'Coche::editarAccion/$1');
    $routes->POST('acciones/borrar/(:num)', 'Coche::borrarAccion/$1');

    $routes->POST('acciones/rapida/(:segment)', 'Coche::accionRapida/$1');


    // Averías
    $routes->GET('averias', 'Coche::averias');
    $routes->GET('averias/nueva', 'Coche::nuevaAveria');
    $routes->POST('averias/guardar', 'Coche::guardarAveria');
    $routes->GET('averias/editar/(:num)', 'Coche::editarAveria/$1');
    $routes->POST('averias/borrar/(:num)', 'Coche::borrarAveria/$1');

    // Recordatorios
    $routes->GET('recordatorios', 'Coche::recordatorios');
    $routes->GET('recordatorios/nuevo', 'Coche::nuevoRecordatorio');
    $routes->POST('recordatorios/guardar', 'Coche::guardarRecordatorio');
    $routes->GET('recordatorios/editar/(:num)', 'Coche::editarRecordatorio/$1');
    $routes->POST('recordatorios/borrar/(:num)', 'Coche::borrarRecordatorio/$1');
    $routes->POST('recordatorios/(:num)/renovar', 'Coche::renovarRecordatorio/$1');
});

// Compras
$routes->group('compras', ['filter' => 'auth'], function ($routes) {
    $routes->GET('/', 'Compras::index');

    // Supermercados
    $routes->GET('supermercados', 'Compras::supermercados');
    $routes->GET('supermercados/nuevo', 'Compras::nuevoSupermercado');
    $routes->POST('supermercados/guardar', 'Compras::crearSupermercado');
    $routes->GET('supermercados/editar/(:num)', 'Compras::editarSupermercado/$1');
    $routes->POST('supermercados/actualizar/(:num)', 'Compras::guardarSupermercado/$1');
    $routes->POST('supermercados/(:num)/borrar', 'Compras::eliminarSupermercado');

    // Gestión: reordenar y ocultar del menú
    $routes->GET('supermercados/gestionar', 'Compras::gestionarSupermercados');
    $routes->POST('supermercados/reordenar', 'Compras::reordenarSupermercados');
    $routes->POST('supermercados/(:num)/visibilidad', 'Compras::toggleVisibleSupermercado/$1');

    // Zonas / pasillos (para definir el recorrido dentro de un supermercado)
    $routes->GET('supermercados/(:num)/zonas', 'Compras::zonas/$1');
    $routes->POST('zonas/nuevo', 'Compras::crearZona');
    $routes->POST('zonas/reordenar', 'Compras::reordenarZonas');
    $routes->POST('zonas/(:num)/renombrar', 'Compras::renombrarZona/$1');
    $routes->POST('zonas/(:num)/borrar', 'Compras::eliminarZona/$1');

    // Productos
    $routes->GET('productos/(:num)', 'Compras::productos/$1');
    $routes->POST('productos/nuevo', 'Compras::crearProducto');
    $routes->POST('productos/reordenar', 'Compras::reordenarProductos');
    $routes->POST('productos/(:num)/favorito', 'Compras::toggleFavorito/$1');
    $routes->POST('productos/(:num)/borrar', 'Compras::eliminarProducto/$1');

    $routes->GET('(:num)/faltantes', 'Compras::faltantes/$1');
    $routes->GET('(:num)/comprados', 'Compras::comprados/$1');
    $routes->POST('limpiar/faltantes/(:num)', 'Compras::limpiarFaltantes/$1');



    // Precios
    $routes->POST('precios/nuevo', 'Compras::crearPrecio');
    $routes->GET('productos/editar/(:num)', 'Compras::editarProducto/$1');
    $routes->POST('productos/(:num)/actualizar', 'Compras::actualizarProducto/$1');

    $routes->POST('precios/(:num)/borrar', 'Compras::eliminarPrecio');

    // Estado de productos
    $routes->POST('producto/(:num)/marcar-faltante', 'Compras::marcarFaltante/$1');
    $routes->POST('producto/(:num)/marcar-comprado', 'Compras::marcarComprado/$1');
    $routes->POST('producto/(:num)/desmarcar-faltante', 'Compras::desmarcarFaltante/$1');
    $routes->POST('producto/(:num)/desmarcar-comprado', 'Compras::desmarcarComprado/$1');

    // Limpiar listas
    $routes->POST('limpiar/faltantes/(:num)', 'Compras::limpiarFaltantes/$1');
    $routes->POST('limpiar/comprados/(:num)', 'Compras::limpiarComprados/$1');
});


// GIMNASIO
$routes->group('gimnasio', ['filter' => 'auth'], function ($routes) {
    $routes->GET('', 'Gimnasio::index');
    $routes->GET('ejercicios', 'GimnasioEjercicios::index');
    $routes->GET('ejercicios/create', 'GimnasioEjercicios::create');
    $routes->POST('ejercicios/store', 'GimnasioEjercicios::store');
    $routes->GET('ejercicios/edit/(:num)', 'GimnasioEjercicios::edit/$1');
    $routes->POST('ejercicios/update/(:num)', 'GimnasioEjercicios::update/$1');
    $routes->POST('ejercicios/delete/(:num)', 'GimnasioEjercicios::delete/$1');
    $routes->GET('ejercicios/por-grupo/(:segment)', 'GimnasioEjercicios::porGrupo/$1');

    $routes->GET('ejercicios/estadisticas/(:num)', 'GimnasioEjercicios::estadisticas/$1');
    $routes->GET('ejercicios/estadisticas/(:num)', 'GimnasioEjercicios::estadisticas/$1');
    $routes->GET('ejercicios/principales', 'GimnasioEjercicios::principales');


    $routes->GET('entrenamientos', 'GimnasioEntrenamientos::index');
    $routes->POST('entrenamientos/crear', 'GimnasioEntrenamientos::crear');
    $routes->GET('entrenamientos/eliminar/(:num)', 'GimnasioEntrenamientos::eliminar/$1');
    $routes->POST('entrenamientos/actualizar-datos/(:num)', 'GimnasioEntrenamientos::actualizarDatos/$1');
    $routes->GET('entrenamientos/registro/(:segment)', 'GimnasioEntrenamientos::registro/$1');
    $routes->POST('entrenamientos/guardar-serie', 'GimnasioEntrenamientos::guardarSerie');
    $routes->GET('entrenamientos/eliminar-serie/(:num)', 'GimnasioEntrenamientos::eliminarSerie/$1');
    $routes->POST('entrenamientos/actualizar-serie/(:num)', 'GimnasioEntrenamientos::actualizarSerie/$1');
    $routes->GET('entrenamientos/ultimo-valor/(:num)', 'GimnasioEntrenamientos::ultimoValor/$1');
    $routes->POST('entrenamientos/reordenar-ejercicio', 'GimnasioEntrenamientos::reordenarEjercicio');
    $routes->POST('entrenamientos/reutilizar/(:num)', 'GimnasioEntrenamientos::reutilizar/$1');


    $routes->GET('entrenamientos/resumen/(:num)', 'GimnasioEntrenamientos::resumen/$1');


    // PLANTILLAS
    $routes->GET('plantillas', 'GimnasioPlantillas::index');
    $routes->POST('plantillas/crear', 'GimnasioPlantillas::crear');
    $routes->GET('plantillas/eliminar/(:num)', 'GimnasioPlantillas::eliminar/$1');
    $routes->POST('plantillas/renombrar/(:num)', 'GimnasioPlantillas::renombrar/$1');
    $routes->GET('plantillas/editar/(:num)', 'GimnasioPlantillas::editar/$1');
    $routes->POST('plantillas/guardar-serie', 'GimnasioPlantillas::guardarSerie');
    $routes->GET('plantillas/eliminar-serie/(:num)', 'GimnasioPlantillas::eliminarSerie/$1');
    $routes->POST('plantillas/actualizar-serie/(:num)', 'GimnasioPlantillas::actualizarSerie/$1');
    $routes->POST('plantillas/reordenar-ejercicio', 'GimnasioPlantillas::reordenarEjercicio');
    $routes->POST('plantillas/aplicar/(:num)', 'GimnasioPlantillas::aplicar/$1');
    $routes->POST('plantillas/guardar-desde-entrenamiento/(:num)', 'GimnasioPlantillas::guardarDesdeEntrenamiento/$1');
});


// MESOCICLOS
$routes->group('gimnasio', ['filter' => 'auth'], static function ($r) {
    $r->GET('mesociclos',                                       'GimnasioMesociclos::index');
    $r->match(['GET', 'POST'],   'mesociclos/nuevo',             'GimnasioMesociclos::nuevo');
    $r->GET('mesociclos/(:num)',                                'GimnasioMesociclos::ver/$1');
    $r->match(['GET', 'POST'], 'mesociclos/(:num)/bloque/nuevo',  'GimnasioMesociclos::bloqueNuevo/$1');
    $r->GET('mesociclos/(:num)/simplificado',                   'GimnasioMesociclos::simplificado/$1');
    $r->match(['GET', 'POST'], 'mesociclos/(:num)/asignar/(:num)', 'GimnasioMesociclos::asignar/$1/$2');


    $r->match(['GET', 'POST'], 'mesociclos/(:num)/generar', 'GimnasioMesociclos::generar/$1');   // genera lote
    $r->POST('mesociclos/bloque/(:num)/hecho', 'GimnasioMesociclos::marcarHecho/$1');

    // Paso previo: formulario de ajuste (solo se permite si no quedan pendientes)
    $r->GET('mesociclos/(:num)/ajustar', 'GimnasioMesociclos::ajustar/$1');
    $r->POST('mesociclos/(:num)/ajustar', 'GimnasioMesociclos::ajustarPOST/$1');

    // BILBO
    $r->match(['GET', 'POST'], 'mesociclos/(:num)/generar/bilbo', 'GimnasioMesociclos::generarBilbo/$1');
});


// --- API para AJAX del diario ---
$routes->group('api', ['filter' => 'auth', 'namespace' => 'App\Controllers\Comidas'], static function ($r) {
    // Buscador y detalle de alimentos
    $r->GET('alimentos', 'Diario::buscarAlimentos');       // /api/alimentos?q=...
    $r->GET('alimentos/(:num)', 'Diario::alimento/$1');    // /api/alimentos/{id}

    // Ingestas
    $r->GET('ingestas/(:segment)/(:segment)', 'Diario::ingestasAjax/$1/$2'); // /api/ingestas/{fecha}/{tipo}
    $r->POST('add', 'Diario::addAjax');                     // /api/add
    $r->POST('delete/(:num)', 'Diario::deleteAjax/$1');     // /api/delete/{id}
    $r->POST('edit/(:num)', 'Diario::editAjax/$1');         // /api/edit/{id}
});


// === Rutas módulo Comidas (app/Config/Routes.php) ===
$routes->group('comidas', ['filter' => 'auth', 'namespace' => 'App\Controllers\Comidas'], static function ($routes) {

    // --- Diario ---
    $routes->group('diario', static function ($r) {
        $r->GET('hoy', 'Diario::hoy');
        $r->GET('top-dias', 'Diario::topDias'); // Top 28 mejores días
        $r->GET('porciones/(:num)', 'Diario::porciones/$1'); // AJAX porciones
        $r->GET('(:segment)/nutrientes', 'Diario::nutrientes/$1');


        // rutas CRUD
        $r->POST('add', 'Diario::add');
        $r->POST('edit/(:num)', 'Diario::edit/$1');
        $r->POST('delete/(:num)', 'Diario::delete/$1');

        // Diario con fecha dinámica (YYYY-MM-DD)
        // ⚠️ importante: de más específico a más genérico
        $r->GET('(:segment)/seleccionar-tipo', 'Diario::seleccionarTipo/$1');
        $r->GET('(:segment)/(:segment)', 'Diario::verTipo/$1/$2');
        $r->GET('(:segment)', 'Diario::ver/$1');
    });


    // Alimentos controlados
    $routes->get('alimentos-control', 'AlimentosControl::index', ['as' => 'comidas.alimentos_control.index']);
    $routes->post('alimentos-control/add', 'AlimentosControl::add', ['as' => 'comidas.alimentos_control.add']);
    $routes->get('alimentos-control/edit/(:num)', 'AlimentosControl::edit/$1', ['as' => 'comidas.alimentos_control.edit']);
    $routes->post('alimentos-control/edit/(:num)', 'AlimentosControl::edit/$1'); // para procesar el formulario
    $routes->get('alimentos-control/delete/(:num)', 'AlimentosControl::delete/$1', ['as' => 'comidas.alimentos_control.delete']);


    // --- Alimentos ---
    $routes->group('alimentos', static function ($r) {
        $r->GET('/', 'Alimentos::index');
        $r->GET('ranking/(:segment)', 'Alimentos::ranking/$1');
        $r->GET('create', 'Alimentos::create');
        $r->POST('store', 'Alimentos::store');
        $r->GET('edit/(:num)', 'Alimentos::edit/$1');
        $r->POST('update/(:num)', 'Alimentos::update/$1');
        $r->POST('preview', 'Alimentos::preview');
        $r->POST('delete/(:num)', 'Alimentos::delete/$1');
    });



    // --- Recetas ---
    $routes->group('recetas', static function ($r) {
        $r->GET('/', 'Recetas::index');
        $r->GET('create', 'Recetas::create');
        $r->POST('store', 'Recetas::store');
        $r->GET('edit/(:num)', 'Recetas::edit/$1');
        $r->POST('update/(:num)', 'Recetas::update/$1');
        $r->POST('delete/(:num)',   'Recetas::delete/$1');

        // AJAX ingredientes (mismo patrón que /api del diario)
        $r->GET('ingredientes/(:num)', 'Recetas::ingredientesAjax/$1');           // listar
        $r->POST('ingredientes/(:num)/add', 'Recetas::addIngredienteAjax/$1');    // añadir
        $r->POST('ingrediente/(:num)/edit', 'Recetas::editIngredienteAjax/$1');   // editar gramos
        $r->POST('ingrediente/(:num)/delete', 'Recetas::deleteIngredienteAjax/$1'); // eliminar
    });

    // --- Objetivos ---
    $routes->group('objetivos', static function ($r) {
        $r->GET('/', 'Objetivos::index');
        $r->GET('create', 'Objetivos::create');
        $r->POST('store', 'Objetivos::store');
        $r->GET('edit/(:num)', 'Objetivos::edit/$1');
        $r->POST('update/(:num)', 'Objetivos::update/$1');
        $r->GET('delete/(:num)', 'Objetivos::delete/$1');
    });

    // --- Porciones ---
    $routes->group('porciones', static function ($r) {
        $r->GET('alimento/(:num)', 'Porciones::index/$1');   // listar porciones de un alimento
        $r->GET('create/(:num)',  'Porciones::create/$1');   // form nueva porción
        $r->POST('store',         'Porciones::store');       // guardar nueva
        $r->GET('edit/(:num)',    'Porciones::edit/$1');     // form editar
        $r->POST('update/(:num)', 'Porciones::update/$1');   // actualizar
        $r->GET('delete/(:num)',  'Porciones::delete/$1');   // eliminar

        // AJAX (para editar proporciones sin salir de la pantalla de la receta)
        $r->GET('ajax/(:num)',          'Porciones::listAjax/$1');
        $r->POST('ajax/store',          'Porciones::storeAjax');
        $r->POST('ajax/(:num)/update',  'Porciones::updateAjax/$1');
        $r->POST('ajax/(:num)/delete',  'Porciones::deleteAjax/$1');
    });
    // PESO
    $routes->group('peso', ['namespace' => 'App\Controllers\Comidas'], static function ($routes) {
        $routes->GET('/',              'Peso::index');
        $routes->POST('guardar',       'Peso::store');
        $routes->GET('eliminar/(:num)', 'Peso::delete/$1');
        $routes->GET('ultimo-mes',     'Peso::ultimoMesJson'); // opcional
        $routes->GET('importar',       'Peso::importarForm');
        $routes->POST('importar',      'Peso::importar');
    });
});


$routes->group('youtube', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/',                                'Youtube::index');
    $routes->match(['GET', 'POST'], 'crear',          'Youtube::crearLista');

    // Editar lista por slug
    $routes->GET('(:segment)/editar',  'Youtube::editarLista/$1');
    $routes->POST('(:segment)/editar', 'Youtube::actualizarLista/$1');


    // IMPORTAR (requiere slug)  /youtube/{slug}/importar
    $routes->GET('(:segment)/importar',              'Youtube::importarForm/$1');
    $routes->POST('(:segment)/importar',             'Youtube::importarProcesar/$1');

    // Catch-all de una sola pieza (ver lista)
    $routes->GET('(:segment)',                       'Youtube::ver/$1');

    $routes->POST('toggle-visto/(:num)',             'Youtube::toggleVisto/$1');
    $routes->POST('toggle-relevante/(:num)',         'Youtube::toggleRelevante/$1');
    $routes->POST('toggle-largo/(:num)',             'Youtube::toggleLargo/$1');
});



// app/Config/Routes.php

$routes->group('rodajes', ['filter' => 'auth'], static function ($routes) {

    // ---- Proyectos de rodaje ----
    $routes->GET('/',                 'Rodajes::index');
    $routes->GET('create',            'Rodajes::create');
    $routes->POST('store',            'Rodajes::store');
    $routes->GET('edit/(:num)',       'Rodajes::edit/$1');
    $routes->POST('update/(:num)',    'Rodajes::update/$1');
    $routes->GET('delete/(:num)',     'Rodajes::delete/$1');
    

    // ---- Escenas (anidadas bajo proyecto) ----
    $routes->GET('(:num)/escenas',                        'RodajesEscenas::index/$1');
    $routes->GET('(:num)/escenas/create',                 'RodajesEscenas::create/$1');
    $routes->POST('(:num)/escenas/store',                 'RodajesEscenas::store/$1');
    $routes->GET('(:num)/escenas/edit/(:num)',            'RodajesEscenas::edit/$1/$2');
    $routes->POST('(:num)/escenas/update/(:num)',         'RodajesEscenas::update/$1/$2');
    $routes->GET('(:num)/escenas/delete/(:num)',          'RodajesEscenas::delete/$1/$2');
    $routes->get('(:num)/dialogos',                       'RodajesEscenas::dialogos/$1');

    // Imágenes de referencia (borrado)
    $routes->GET('(:num)/escenas/(:num)/imagen/delete/(:num)', 'RodajesEscenas::deleteImage/$1/$2/$3');

    // (Opcional) Reordenar escenas por AJAX (enviar JSON: [{id, orden}, ...])
    // Implementa RodajesEscenas::reordenar($proyectoId) si lo quieres usar.
    $routes->POST('(:num)/escenas/reordenar',             'RodajesEscenas::reordenar/$1');

    // Ver escena (detalle)
    $routes->GET('(:num)/escenas/show/(:num)', 'RodajesEscenas::show/$1/$2');
    // Storyboard
    $routes->GET('(:num)/escenas/storyboard', 'RodajesEscenas::storyboard/$1');
    $routes->GET('(:num)/escenas/ordenrodaje', 'RodajesEscenas::storyboardPorClasificacion/$1');
});


$routes->group('enlaces', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/', 'Enlaces::index');
    $routes->GET('etiquetas-disponibles', 'Enlaces::etiquetasDisponibles');
    $routes->GET('crear', 'Enlaces::crear');
    $routes->POST('guardar', 'Enlaces::guardar');
    $routes->GET('editar/(:num)', 'Enlaces::editar/$1');
    $routes->POST('actualizar/(:num)', 'Enlaces::actualizar/$1');
    $routes->GET('borrar/(:num)', 'Enlaces::borrar/$1');


    // gestión de categorías
    $routes->GET('categorias', 'Enlaces::categorias');
    $routes->POST('categorias/guardar', 'Enlaces::guardarCategoria');
    $routes->GET('categorias/borrar/(:num)', 'Enlaces::borrarCategoria/$1');


    // gestión de etiquetas
    $routes->GET('etiquetas', 'Enlaces::etiquetas');
    $routes->POST('etiquetas/guardar', 'Enlaces::guardarEtiqueta');
    $routes->GET('etiquetas/borrar/(:num)', 'Enlaces::borrarEtiqueta/$1');


    // ajax
    $routes->POST('toggle-visto/(:num)', 'Enlaces::toggleVisto/$1');


    // Página tipo Notion:
    $routes->GET('pagina/(:num)', 'Enlaces::pagina/$1');

    $routes->POST('pagina/guardar/(:num)', 'Enlaces::guardarDoc/$1');        // guarda el HTML del editor en extra
    $routes->POST('editor-upload/(:num)', 'Enlaces::editorUpload/$1');       // subida de imágenes del editor


    $routes->GET('importar', 'Enlaces::importarForm');
    $routes->POST('importar', 'Enlaces::importarUpload');

    $routes->GET('revision',               'Enlaces::revision');              // dashboard de revisión
    $routes->GET('revision/item',          'Enlaces::revisionItem');          // coge el primero pendiente
    $routes->GET('revision/item/(:num)',   'Enlaces::revisionItem/$1');       // revisar uno concreto

    $routes->POST('revision/guardar/(:num)', 'Enlaces::revisionGuardar/$1');  // guarda y va al siguiente
    $routes->POST('revision/borrar/(:num)',  'Enlaces::revisionBorrar/$1');   // borra y va al siguiente
    $routes->POST('revision/saltar/(:num)',  'Enlaces::revisionSaltar/$1');   // siguiente sin cambios



});

$routes->group('journal', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('', 'Journal::index');
    $routes->GET('edit/(:num)', 'Journal::edit/$1');
    $routes->POST('edit/(:num)', 'Journal::edit/$1');
    $routes->POST('create', 'Journal::create');
    $routes->POST('delete/(:num)', 'Journal::delete/$1');
    $routes->POST('delete-image/(:num)', 'Journal::deleteImage/$1');
    $routes->POST('toggle-current/(:num)', 'Journal::toggleCurrent/$1');
    $routes->POST('add-time/(:num)', 'Journal::addTime/$1');
    $routes->POST('add-log/(:num)', 'Journal::addLog/$1');
    $routes->GET('get-logs/(:num)', 'Journal::getLogs/$1');
    $routes->POST('update-log/(:num)', 'Journal::updateLog/$1');

    // ---- ¿Qué hago ahora? ----
    $routes->GET('que-hacer', 'Journal::queHacer');
    $routes->POST('categorias/(:num)/peso', 'Journal::actualizarPeso/$1');
});

// ---- Hogar: checklist rutinario de tareas por habitación ----
$routes->group('hogar', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/', 'Hogar::index');
    $routes->GET('pendientes', 'Hogar::pendientes');
    $routes->GET('gestionar', 'Hogar::gestionar');

    $routes->GET('habitaciones/nueva', 'Hogar::nuevaHabitacion');
    $routes->POST('habitaciones/crear', 'Hogar::crearHabitacion');
    $routes->GET('habitaciones/editar/(:num)', 'Hogar::editarHabitacion/$1');
    $routes->POST('habitaciones/actualizar/(:num)', 'Hogar::actualizarHabitacion/$1');
    $routes->POST('habitaciones/borrar/(:num)', 'Hogar::borrarHabitacion/$1');
    $routes->POST('habitaciones/reordenar', 'Hogar::reordenarHabitaciones');

    $routes->GET('tareas/editar/(:num)', 'Hogar::editarTarea/$1');
    $routes->GET('tareas/(:num)/historial', 'Hogar::historialTarea/$1');
    $routes->POST('tareas/logs/(:num)/borrar', 'Hogar::borrarLog/$1');
    $routes->POST('tareas/actualizar/(:num)', 'Hogar::actualizarTarea/$1');
    $routes->POST('tareas/borrar/(:num)', 'Hogar::borrarTarea/$1');
    $routes->POST('tareas/crear', 'Hogar::crearTarea');
    $routes->POST('tareas/reordenar', 'Hogar::reordenarTareas');
    $routes->POST('tareas/(:num)/marcar', 'Hogar::marcarTarea/$1');
    $routes->POST('tareas/(:num)/renovar', 'Hogar::renovarTarea/$1');

    $routes->POST('(:num)/renovar-todo', 'Hogar::renovarTodo/$1');
    $routes->GET('(:num)', 'Hogar::habitacion/$1');
});

// ---- Sesiones: seguimiento de sesiones de fotografía/vídeo tipo stock ----
// 'idea' es un estado más del pipeline (idea -> planificacion -> ... ->
// completado): no hay rutas ni controlador aparte para ideas, se crean y
// gestionan igual que cualquier sesión.
$routes->group('sesiones', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/', 'Sesiones::index');
    $routes->GET('crear', 'Sesiones::create');
    $routes->POST('guardar', 'Sesiones::store');
    $routes->GET('(:num)', 'Sesiones::show/$1');
    $routes->GET('(:num)/editar', 'Sesiones::edit/$1');
    $routes->POST('(:num)/actualizar', 'Sesiones::update/$1');
    $routes->POST('(:num)/borrar', 'Sesiones::delete/$1');
    $routes->POST('(:num)/estado', 'Sesiones::estado/$1');
    $routes->POST('(:num)/toggle-pausada', 'Sesiones::togglePausada/$1');
    $routes->POST('(:num)/entrega-modelos', 'Sesiones::entregaModelos/$1');

    $routes->POST('(:num)/situaciones/crear', 'Sesiones::situacionCrear/$1');
    $routes->POST('(:num)/situaciones/reordenar', 'Sesiones::situacionReordenar/$1');
    $routes->POST('(:num)/situaciones/(:num)/borrar', 'Sesiones::situacionBorrar/$1/$2');
    $routes->GET('(:num)/situaciones/(:num)/exportar', 'Sesiones::exportarSituacion/$1/$2');

    $routes->POST('(:num)/equipo/agregar', 'Sesiones::equipoAgregar/$1');
    $routes->POST('(:num)/equipo/(:num)/toggle', 'Sesiones::equipoToggle/$1/$2');
    $routes->POST('(:num)/equipo/(:num)/borrar', 'Sesiones::equipoBorrar/$1/$2');

    $routes->POST('(:num)/moodboard/subir', 'Sesiones::moodboardSubir/$1');
    $routes->POST('(:num)/moodboard/enlace', 'Sesiones::moodboardAgregarEnlace/$1');
    $routes->POST('(:num)/moodboard/(:num)/borrar', 'Sesiones::moodboardBorrar/$1/$2');
    $routes->POST('(:num)/moodboard/(:num)/vincular', 'Sesiones::moodboardVincular/$1/$2');

    $routes->POST('(:num)/releases/subir', 'Sesiones::releaseSubir/$1');
    $routes->POST('(:num)/releases/(:num)/borrar', 'Sesiones::releaseBorrar/$1/$2');

    $routes->POST('(:num)/mensajes/crear', 'Sesiones::mensajeModeloCrear/$1');
    $routes->POST('(:num)/mensajes/(:num)/borrar', 'Sesiones::mensajeModeloBorrar/$1/$2');

    $routes->GET('(:num)/exportar', 'Sesiones::exportar/$1');
});

// ---- Recordatorios: fechas de eventos recurrentes (ITV, DNI, vacunas, etc.) ----
$routes->group('recordatorios', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/', 'Recordatorios::index');
    $routes->GET('nuevo', 'Recordatorios::nuevo');
    $routes->POST('crear', 'Recordatorios::crear');
    $routes->GET('editar/(:num)', 'Recordatorios::editar/$1');
    $routes->POST('actualizar/(:num)', 'Recordatorios::actualizar/$1');
    $routes->POST('borrar/(:num)', 'Recordatorios::borrar/$1');
    $routes->POST('(:num)/renovar', 'Recordatorios::renovar/$1');
});

// ---- Braintogram: ingesta y log de mensajes de un bot de Telegram ----
// Webhook público: Telegram no puede autenticarse, así que esta ruta va SIN
// filtro auth. Se protege en su lugar con el secret token de setWebhook
// (ver Braintogram::webhook / braintogram.webhookSecret en .env).
$routes->POST('braintogram/webhook', 'Braintogram::webhook');

$routes->group('braintogram', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/', 'Braintogram::index');
    $routes->GET('(:num)', 'Braintogram::ver/$1');
});

// ---- Cuenta: gestión del propio usuario (cambio de contraseña) ----
$routes->group('cuenta', ['filter' => 'auth'], static function ($routes) {
    $routes->GET('/', 'AuthController::account', ['as' => 'account']);
    $routes->POST('password', 'AuthController::updatePassword');
});
