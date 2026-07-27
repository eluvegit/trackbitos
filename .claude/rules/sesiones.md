---
paths:
  - "app/Controllers/Sesiones.php"
  - "app/Controllers/SesionIdeas.php"
  - "app/Models/Sesion*.php"
  - "app/Models/Idea*.php"
  - "app/Models/MoodboardItemModel.php"
  - "app/Models/ModelReleaseModel.php"
  - "app/Models/SituacionModel.php"
  - "app/Views/sesiones/**"
---

# Sesiones module — conventions and gotchas

- A sesión tracks **two independent lifecycles**, not one: `estado_foto` and `estado_video` on `sesiones`, each nullable. `NULL` means that part doesn't apply to this session — never repurpose it as a third state. There is no `tipo`/`estado` single field; don't reintroduce one.
- Pipeline for each part: `planificacion → edicion → subiendo → completado` (`SesionModel::ESTADOS`, `SesionModel::PARTES = ['foto','video']`). Always change a part's state through `SesionModel::cambiarEstado($id, $parte, $nuevoEstado)` — it writes `sesion_historial_estados` (with `parte` + `estado`) in the same transaction. Never `update()` the column directly.
- **Ideas are a separate entity**, not a third `estado`. `idea` was tried as a `sesiones.estado_*` enum value and reverted — ideas now live in their own `ideas` + `idea_moodboard_items` tables (`SesionIdeas` controller, routes under `sesiones/ideas`). An idea only has `tiene_foto`/`tiene_video` booleans, no per-part progress, no situaciones, no equipo, no model_releases.
  - Promote idea → sesión: `SesionIdeas::promover()` (creates the sesión at `planificacion` for the parts marked, moves the moodboard flat as "general").
  - Demote sesión → idea: `Sesiones::convertirEnIdea()` — **lossy**: equipo and model_releases have no idea equivalent and are discarded (cascade delete); `briefing` has no idea equivalent either, so it gets appended into `notas` instead of silently dropped.
- The kanban (`sesiones/index.php`) is a table, **one row per session**, not one card per session. Each applicable part renders its own chip in the column matching *that part's own* `estado_*` — never collapse both parts into a single card/column (a session with foto=edición + vídeo=subiendo must show two chips, aligned in that session's row, one per column). Drag-and-drop is scoped per row via a unique SortableJS `group` (`sesion-<id>`) so a chip can never be dropped into another session's row.
- `notas` (quick notes) and `briefing` (fuller description for the exportable report, meant e.g. for the model) are separate fields, both edited from small independent forms on the show page — each form carries the *other* field as a hidden input so saving one never clobbers the other.
- Uploaded files (moodboard images, model releases) live under `public/uploads/sesiones/{sesion_id}/...` (ideas: `public/uploads/sesiones/ideas/{idea_id}/...`). Always delete the physical file via `borrarArchivoFisico()` before/with the DB row — don't leave orphaned files.
- The situaciones/moodboard export (`Sesiones::exportar`/`exportarSituacion`) is a printable HTML view, not a PDF library — it relies on the browser's print-to-PDF, styled with `@media print`.
