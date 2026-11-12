<?php // Estilo del desglose de stock (helper stock_desglose()): el total va en
      // grande en cada vista y esto debajo o al lado, en pequeño. ?>
<style>
    .st-des { display: inline-flex; flex-wrap: wrap; align-items: baseline; gap: 1px 6px; font-size: 11.5px; font-weight: 400; letter-spacing: 0; line-height: 1.35; color: var(--fv-text-3, var(--bs-tertiary-color)); }
    .st-des > i { font-style: normal; opacity: .5; }
    .st-des .st-parte { white-space: nowrap; }
    .st-des b { font-size: inherit !important; font-weight: 600; letter-spacing: 0; color: var(--fv-text-2, var(--bs-secondary-color)); font-variant-numeric: tabular-nums; }
    .st-des .aqui { color: var(--fv-text, var(--bs-emphasis-color)); }
    .st-des .aqui b { color: var(--fv-accent, #ff6a3d); }
    .st-total { display: block; font-size: 11px; font-weight: 400; color: var(--fv-text-3, var(--bs-tertiary-color)); }
    .st-mov { display: inline-flex; gap: 2px; flex: none; }
    .st-mov .fv-ibtn.alta:hover { color: var(--fv-ok, #3fb950) !important; }
    .st-mov .fv-ibtn.baja:hover { color: var(--fv-bad, #f0553f) !important; }
</style>
