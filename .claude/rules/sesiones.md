---
paths:
  - "app/Controllers/Sesiones.php"
  - "app/Models/Sesion*.php"
  - "app/Models/MoodboardItemModel.php"
  - "app/Models/ModelReleaseModel.php"
  - "app/Models/SituacionModel.php"
  - "app/Views/sesiones/**"
---

# Sesiones module — conventions and gotchas

- A sesión tracks **two independent lifecycles**, not one: `estado_foto` and `estado_video` on `sesiones`, each nullable (real MySQL `ENUM` columns, not free text). `NULL` means that part doesn't apply to this session — never repurpose it as a third state. There is no `tipo`/`estado` single field; don't reintroduce one.
- Pipeline for each part: `idea → planificacion → edicion → subiendo → completado` (`SesionModel::ESTADOS`, `SesionModel::PARTES = ['foto','video']`). Always change a part's state through `SesionModel::cambiarEstado($id, $parte, $nuevoEstado)` — it writes `sesion_historial_estados` (with `parte` + `estado`) in the same transaction. Never `update()` the column directly. Adding a new value here means a migration to widen the `ENUM` on both `sesiones.estado_foto`/`estado_video` and `sesion_historial_estados.estado` — the PHP `in_list[...]` validation rule alone is not enough, the DB will reject an unlisted value.
- **`idea` is not a separate entity** — it is the pipeline's first stage, with the exact same functionality and data as any other stage (moodboard, situaciones, equipo, model releases, briefing, notas...). There is no `IdeaModel`/`SesionIdeas` controller and no separate `ideas` table anymore (migration `2026-07-28-000001_RestoreIdeaEstadoEnSesiones` folded that back into `sesiones`, reverting an earlier attempt at a separate module that turned out to be the wrong model). A session is created directly at `idea` instead of `planificacion` via a checkbox on the create form (`es_idea` post field in `Sesiones::store()`); moving it forward is just changing its stage via the normal "Cambiar etapa" dropdown, like any other transition — there is no dedicated "promote"/"convert" action.
  - The **only** thing special about `idea`: the sessions list (`sesiones/index.php`) hides it by default (`estadosVisibles()` always excludes `'idea'` from the default filter set) — a dedicated "Ideas" filter chip shows it, combining with the other stage chips using the same OR logic.
- The sessions list (`sesiones/index.php`) is a table, **one row per session** by default (or one row per applicable part when the "Dividir" toggle is on), entirely client-rendered from a `SESIONES`-like JSON blob embedded by the controller — not server-looped HTML. Each part shows as a 5-segment progress bar (`idea/planificacion/edicion/subiendo/completado`), filled up to its current stage; clicking a segment calls `Sesiones::estado` (no page reload). Filtering/sorting/hide-completed/split are all client-side JS state, nothing server-side.
- `notas` (quick notes) and `briefing` (fuller description for the exportable report, meant e.g. for the model) are separate fields, both edited from small independent forms on the show page — each form carries the *other* field as a hidden input so saving one never clobbers the other.
- Uploaded files (moodboard images, model releases) live under `public/uploads/sesiones/{sesion_id}/...`. Always delete the physical file via `borrarArchivoFisico()` before/with the DB row — don't leave orphaned files.
- The situaciones/moodboard export (`Sesiones::exportar`/`exportarSituacion`) is a printable HTML view, not a PDF library — it relies on the browser's print-to-PDF, styled with `@media print`.
