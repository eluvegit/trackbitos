<?php namespace App\Commands;

use App\Models\EnlacesModel;
use App\Models\EnlacesCategoriasModel;
use App\Models\EnlacesEtiquetasModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class ImportNotion extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'import:notion';
    protected $description = 'Importa un CSV exportado de Notion a tablas enlaces_*';
    protected $usage       = 'import:notion <path_csv>';

    public function run(array $params)
    {
        $path = $params[0] ?? null;
        if (!$path || !is_file($path)) {
            CLI::error('Debe indicar la ruta a un CSV válido.');
            return;
        }

        // --- Helpers robustos ---
        $normalize = function(string $s): string {
            $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);        // BOM
            $s = mb_strtolower($s);
            $s = preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200F}\x{2028}\x{202F}\x{205F}\x{3000}]+/u', ' ', $s); // NBSP & cia
            $s = preg_replace('/\p{C}|\p{Cf}/u', '', $s);        // chars invisibles
            $s = strtr($s, [ 'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n', 'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u' ]);
            $s = str_replace(['"', "'", '“','”','„','‟','‹','›','‚','‘','’'], '', $s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return trim($s);
        };

        $findIndex = function(array $headersNorm, array $aliases): int|false {
            foreach ($aliases as $a) {
                $i = array_search($a, $headersNorm, true);
                if ($i !== false) return $i;
            }
            foreach ($aliases as $a) {
                if ($a === '') continue;
                foreach ($headersNorm as $i => $h) if (strpos($h, $a) === 0) return $i;
            }
            foreach ($aliases as $a) {
                if ($a === '') continue;
                foreach ($headersNorm as $i => $h) if (strpos($h, $a) !== false) return $i;
            }
            return false;
        };

        // --- Detectar separador ---
        $fh = fopen($path, 'r');
        if (!$fh) { CLI::error('No se pudo abrir el archivo.'); return; }
        $firstLine = fgets($fh) ?: '';
        rewind($fh);
        $sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        // --- Cabeceras ---
        $header = fgetcsv($fh, 0, $sep);
        if (!$header) { CLI::error('CSV sin cabeceras.'); fclose($fh); return; }
        $headersNorm = array_map($normalize, $header);

        // Aliases
        $aliasesTitulo = ['descripcion','descripción','name','titulo','título','title'];
        $aliasesURL    = ['fuente url','url','enlace','link','source','source url','web','website'];
        $aliasesFecha  = ['date add','fecha','created','fecha creacion','creado','date'];
        $aliasesPrio   = ['prioridad','priority','relevancia','stars','rating'];
        $aliasesVisto  = ['revisado?','revisado','visto','done','check','checked','estado'];
        $aliasesCat    = ['categoria','categorias','category','categories'];
        $aliasesTags   = ['tags','etiquetas','labels'];

        // Índices
        $idxTitulo = $findIndex($headersNorm, $aliasesTitulo);
        $idxURL    = $findIndex($headersNorm, $aliasesURL);
        $idxFecha  = $findIndex($headersNorm, $aliasesFecha);
        $idxPrio   = $findIndex($headersNorm, $aliasesPrio);
        $idxVisto  = $findIndex($headersNorm, $aliasesVisto);
        $idxCat    = $findIndex($headersNorm, $aliasesCat);
        $idxTags   = $findIndex($headersNorm, $aliasesTags);

        if ($idxTitulo === false || $idxURL === false) {
            CLI::error("Faltan columnas obligatorias (Título y/o URL).");
            CLI::write("Cabeceras detectadas (normalizadas):", 'yellow');
            foreach ($headersNorm as $i => $h) CLI::write("  [$i] \"$h\"");
            fclose($fh);
            return;
        }

        // --- Modelos / DB ---
        $db      = Database::connect();
        $enlaces = new EnlacesModel();
        // Si tu EnlacesModel valida 'url' como required y quieres permitir filas sin URL, puedes:
        // $enlaces->skipValidation(true);

        $catsM = new EnlacesCategoriasModel();
        $tagsM = new EnlacesEtiquetasModel();

        // Caches locales
        $catCache = [];
        $tagCache = [];

        $getCatId = function(string $name) use ($catsM, &$catCache, $normalize) {
            $name = trim($name);
            if ($name === '') return null;
            if (isset($catCache[$name])) return $catCache[$name];
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $normalize($name));
            $row  = $catsM->where('slug', $slug)->first();
            $id   = $row ? $row['id'] : $catsM->insert(['nombre' => $name, 'slug' => $slug]);
            return $catCache[$name] = (int)$id;
        };

        $getTagId = function(string $name) use ($tagsM, &$tagCache, $normalize) {
            $name = trim($name);
            if ($name === '') return null;
            if (isset($tagCache[$name])) return $tagCache[$name];
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $normalize($name));
            $row  = $tagsM->where('slug', $slug)->first();
            $id   = $row ? $row['id'] : $tagsM->insert(['nombre' => $name, 'slug' => $slug]);
            return $tagCache[$name] = (int)$id;
        };

        // Contadores
        $total = 0; $importadas = 0; $saltadasSinURL = 0; $errores = 0;

        // --- Importación sin transacción global ---
        while (($row = fgetcsv($fh, 0, $sep)) !== false) {
            $total++;

            try {
                $titulo = trim((string)($row[$idxTitulo] ?? '')) ?: null;
                $url    = trim((string)($row[$idxURL] ?? '')) ?: null;

                // Saltar si no hay URL (tu petición)
                if (!$url) { $saltadasSinURL++; continue; }

                if (!$titulo) $titulo = $url;

                // Fecha
                $fecha = date('Y-m-d');
                if ($idxFecha !== false) {
                    $raw = trim((string)($row[$idxFecha] ?? ''));
                    if ($raw !== '') {
                        $ts = strtotime($raw);
                        if ($ts) $fecha = date('Y-m-d', $ts);
                    }
                }

                // Relevancia
                $relevancia = 0;
                if ($idxPrio !== false) {
                    $prioRaw = mb_strtolower(trim((string)($row[$idxPrio] ?? '')));
                    $mapText = ['alta'=>5,'alto'=>5,'media'=>3,'medio'=>3,'baja'=>1,'bajo'=>1];
                    if ($prioRaw === '') {
                        $relevancia = 0;
                    } elseif (is_numeric($prioRaw)) {
                        $relevancia = max(0, min(5, (int)$prioRaw));
                    } elseif (isset($mapText[$prioRaw])) {
                        $relevancia = $mapText[$prioRaw];
                    } else {
                        $stars = preg_match_all('/★|\*/u', $prioRaw);
                        $relevancia = max(0, min(5, $stars ?: 0));
                    }
                }

                // Visto
                $visto = 0;
                if ($idxVisto !== false) {
                    $revRaw = mb_strtolower(trim((string)($row[$idxVisto] ?? '')));
                    $visto = in_array($revRaw, ['1','true','sí','si','y','yes','checked','ok','done'], true) ? 1 : 0;
                }

                // Inserta item; si tu modelo exige URL NOT NULL/required, ya la tenemos:
                $itemId = $enlaces->insert([
                    'titulo'     => $titulo,
                    'url'        => $url,
                    'visto'      => $visto,
                    'relevancia' => $relevancia,
                    'fecha'      => $fecha,
                    'extra'      => null,
                ]);

                if (!$itemId) {
                    $errores++;
                    CLI::error('Fallo insert item fila '.$total.' -> '.json_encode($enlaces->errors()));
                    continue;
                }

                // --- Categorías (dedupe por fila) ---
                if ($idxCat !== false) {
                    $catField = (string)($row[$idxCat] ?? '');
                    if ($catField !== '') {
                        $parts = array_unique(array_filter(array_map('trim', preg_split('/[;,|]/', $catField))));
                        foreach ($parts as $cname) {
                            $cid = $getCatId($cname);
                            if (!$cid) continue;
                            // INSERT IGNORE en pivote
                            $db->table('enlaces_item_categorias')->ignore(true)->insert([
                                'item_id'      => (int)$itemId,
                                'categoria_id' => (int)$cid
                            ]);
                        }
                    }
                }

                // --- Tags (dedupe por fila) ---
                if ($idxTags !== false) {
                    $tagsField = (string)($row[$idxTags] ?? '');
                    if ($tagsField !== '') {
                        $parts = array_unique(array_filter(array_map('trim', preg_split('/[;,]/', $tagsField))));
                        foreach ($parts as $tname) {
                            $tid = $getTagId($tname);
                            if (!$tid) continue;
                            $db->table('enlaces_item_etiquetas')->ignore(true)->insert([
                                'item_id'     => (int)$itemId,
                                'etiqueta_id' => (int)$tid
                            ]);
                        }
                    }
                }

                $importadas++;

            } catch (\Throwable $e) {
                $errores++;
                CLI::error("Error fila {$total}: ".$e->getMessage());
            }
        }

        fclose($fh);

        CLI::write("Filas leídas: {$total}", 'yellow');
        CLI::write("Importadas: {$importadas}", 'green');
        CLI::write("Saltadas sin URL: {$saltadasSinURL}", 'yellow');
        CLI::write("Con error: {$errores}", $errores ? 'red' : 'green');
    }
}
