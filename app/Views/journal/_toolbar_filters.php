<?php
/**
 * Botones de filtro/vista del Journal (Prioridad, Focus, Hechos, Vista).
 * Partial aparte porque Journal::grid() lo vuelve a renderizar en cada
 * respuesta AJAX (junto con _grid.php) para que el estado activo de cada
 * botón quede siempre en sincronía con lo que se acaba de pedir, sin
 * duplicar esta lógica en JS.
 *
 * Variables esperadas: $filterPriority, $filterFocus, $filterHechos, $view_mode.
 */

$priorityNext = $filterPriority ? 0 : 1;
$priorityClass = $filterPriority ? 'btn-primary' : 'btn-outline-primary';

$focusNext = $filterFocus === 'focus' ? 'todas' : 'focus';
$focusClass = $filterFocus === 'focus' ? 'btn-primary' : 'btn-outline-primary';

$portadasNext = $view_mode === 'portadas' ? 'listado' : 'portadas';
$portadasClass = $view_mode === 'portadas' ? 'btn-primary' : 'btn-outline-primary';

$hechosNext = $filterHechos === 'ocultar' ? 'mostrar' : 'ocultar';
$hechosClass = $filterHechos === 'ocultar' ? 'btn-primary' : 'btn-outline-primary';

// Cada enlace envía solo la clave que cambia; las demás se quedan como
// estén guardadas en su propia cookie (stickyFilter), para que un valor
// "congelado" en esta renderización no pise a otro filtro que se haya
// cambiado después en otra pestaña/carga. El JS del listado intercepta el
// click (clase js-journal-filter) y pide journal/grid con esta misma query
// string en vez de dejar que el navegador navegue a journal?...
$qs = fn($overrides) => http_build_query($overrides);
?>
<!-- Prioridad -->
<a href="<?= site_url('journal') . '?' . $qs(['priority' => $priorityNext]) ?>"
    class="btn js-journal-filter <?= $priorityClass ?>" title="Prioridad">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0-1A6 6 0 1 1 8 2a6 6 0 0 1 0 12z" />
        <path d="M7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.93-6.481a.5.5 0 0 1 .538.497v3.967a.5.5 0 0 1-1 0V5.016a.5.5 0 0 1 .462-.497z" />
    </svg>
</a>

<!-- Focus -->
<a href="<?= site_url('journal') . '?' . $qs(['filterFocus' => $focusNext]) ?>"
    class="btn js-journal-filter <?= $focusClass ?>" title="Focus">
    <?php if ($filterFocus === 'focus'): ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#ffc107" class="bi bi-star-fill" viewBox="0 0 16 16">
            <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z" />
        </svg>
    <?php else: ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-star" viewBox="0 0 16 16">
            <path d="M2.866 14.85c-.078.444.36.791.746.593L8 13.187l4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.523-3.356c.329-.314.158-.888-.283-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.495 4.935l-4.898.696c-.441.062-.612.636-.282.95l3.523 3.356-.83 4.73zM8 12.027l-3.763 1.933.717-4.088-2.97-2.829 4.102-.583L8 2.223l1.914 3.237 4.102.583-2.97 2.828.717 4.089L8 12.027z" />
        </svg>
    <?php endif; ?>
</a>

<!-- Hechos -->
<a href="<?= site_url('journal') . '?' . $qs(['hechos' => $hechosNext]) ?>"
    class="btn js-journal-filter <?= $hechosClass ?>" title="<?= $filterHechos === 'ocultar' ? 'Mostrar hechos' : 'Ocultar hechos' ?>">
    <?php if ($filterHechos === 'ocultar'): ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z" />
            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z" />
            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.879 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z" />
        </svg>
    <?php else: ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.133 13.133 0 0 1 1.172 8z" />
            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
        </svg>
    <?php endif; ?>
</a>

<!-- Vista -->
<a href="<?= site_url('journal') . '?' . $qs(['view' => $portadasNext]) ?>"
    class="btn js-journal-filter <?= $portadasClass ?>" title="Vista">
    <?php if ($view_mode === 'portadas'): ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
            <path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z" />
        </svg>
    <?php else: ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M2.5 12.5a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
        </svg>
    <?php endif; ?>
</a>
