<?php

namespace App\Services;

use App\Models\GimnasioCatalogoModel;

/**
 * Importa el dataset hasaneyldrm/exercises-dataset a `gimnasio_catalogo`.
 * Es idempotente (upsert por ext_id) y nunca toca `gimnasio_ejercicios`.
 * Las imágenes/GIF no se descargan: se sirven desde jsDelivr (ver
 * gim_catalogo_media()).
 */
class GimnasioCatalogoService
{
    public const URL_JSON = 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/data/exercises.json';

    /**
     * @param string|null $origen Ruta local o URL del exercises.json; null = GitHub.
     * @return array{nuevos:int, actualizados:int}
     */
    public function importar(?string $origen = null): array
    {
        $origen ??= self::URL_JSON;

        if (preg_match('#^https?://#', $origen)) {
            $resp = \Config\Services::curlrequest(['timeout' => 180])->get($origen);
            $json = $resp->getBody();
        } else {
            if (!is_file($origen)) {
                throw new \RuntimeException("No existe el fichero: $origen");
            }
            $json = file_get_contents($origen);
        }

        $datos = json_decode($json, true);
        if (!is_array($datos) || !$datos) {
            throw new \RuntimeException('El JSON del catálogo no es válido o está vacío.');
        }

        $model = new GimnasioCatalogoModel();
        $existentes = array_column(
            $model->select('id, ext_id')->findAll(),
            'id',
            'ext_id'
        );

        $nuevos = $actualizados = 0;
        $ahora  = date('Y-m-d H:i:s');
        $insertar = $actualizar = [];

        foreach ($datos as $e) {
            if (empty($e['id']) || empty($e['name'])) {
                continue;
            }

            $fila = [
                'ext_id'           => (string) $e['id'],
                'nombre'           => mb_substr(trim($e['name']), 0, 120),
                'parte'            => $e['category'] ?? null,
                'equipo'           => $e['equipment'] ?? null,
                'objetivo'         => $e['target'] ?? null,
                'secundarios'      => mb_substr(implode(', ', $e['secondary_muscles'] ?? []), 0, 255),
                'instrucciones_es' => $e['instructions']['es'] ?? null,
                'pasos_es'         => isset($e['instruction_steps']['es'])
                    ? json_encode($e['instruction_steps']['es'], JSON_UNESCAPED_UNICODE)
                    : null,
                'instrucciones_en' => $e['instructions']['en'] ?? null,
                'imagen'           => $e['image'] ?? null,
                'gif'              => $e['gif_url'] ?? null,
                'updated_at'       => $ahora,
            ];

            if (isset($existentes[$fila['ext_id']])) {
                $actualizar[] = $fila;
                $actualizados++;
            } else {
                $fila['created_at'] = $ahora;
                $insertar[] = $fila;
                $nuevos++;
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();
        foreach (array_chunk($insertar, 200) as $lote) {
            $db->table('gimnasio_catalogo')->insertBatch($lote);
        }
        foreach (array_chunk($actualizar, 200) as $lote) {
            $db->table('gimnasio_catalogo')->updateBatch($lote, 'ext_id');
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            throw new \RuntimeException('Error guardando el catálogo en la base de datos.');
        }

        return ['nuevos' => $nuevos, 'actualizados' => $actualizados];
    }

    /**
     * Candidatos del catálogo para cada uno de mis ejercicios sin vincular.
     * Traduce el nombre propio (español) a palabras clave en inglés y puntúa
     * por palabras en común (Dice) con un extra si la zona encaja con el
     * grupo. Solo sugiere: la vinculación la confirma el usuario.
     *
     * @return array<int, array{ejercicio: array, candidatos: list<array>}>
     */
    public function sugerencias(int $max = 3, float $minimo = 0.5): array
    {
        $db = \Config\Database::connect();
        $mios = $db->table('gimnasio_ejercicios')
            ->select('id, nombre, grupo_muscular')
            ->where('catalogo_id IS NULL')
            ->orderBy('grupo_muscular')->orderBy('nombre')
            ->get()->getResultArray();

        $catalogo = $this->catalogoConTokens();

        $resultado = [];
        foreach ($mios as $e) {
            $candidatos = $this->puntuar($e, $catalogo, $max, $minimo);
            if ($candidatos) {
                $resultado[] = ['ejercicio' => $e, 'candidatos' => $candidatos];
            }
        }

        return $resultado;
    }

    /** Candidatos del catálogo para un solo ejercicio propio (vinculado o no). */
    public function candidatosPara(array $ejercicio, int $max = 5, float $minimo = 0.4): array
    {
        return $this->puntuar($ejercicio, $this->catalogoConTokens(), $max, $minimo);
    }

    private function catalogoConTokens(): array
    {
        $catalogo = \Config\Database::connect()->table('gimnasio_catalogo')
            ->select('id, nombre, parte, equipo, objetivo, imagen')
            ->get()->getResultArray();
        foreach ($catalogo as &$c) {
            $c['_tokens'] = self::tokens($c['nombre']);
        }
        return $catalogo;
    }

    private function puntuar(array $e, array $catalogo, int $max, float $minimo): array
    {
        $tokens = self::tokens(self::traducir($e['nombre']));
        if (!$tokens) {
            return [];
        }

        $puntuados = [];
        foreach ($catalogo as $c) {
            $comunes = count(array_intersect($tokens, $c['_tokens']));
            if (!$comunes) {
                continue;
            }
            $score = 2 * $comunes / (count($tokens) + count($c['_tokens']));
            if (self::grupoEncaja($e['grupo_muscular'], $c)) {
                $score += 0.1;
            }
            if ($score >= $minimo) {
                $c['score'] = round($score, 2);
                unset($c['_tokens']);
                $puntuados[] = $c;
            }
        }

        usort($puntuados, fn ($a, $b) => [$b['score'], strlen($a['nombre'])] <=> [$a['score'], strlen($b['nombre'])]);
        return array_slice($puntuados, 0, $max);
    }

    /**
     * Búsqueda libre en el catálogo con los mismos términos que el buscador
     * de la página del catálogo (acepta español y plurales).
     */
    public function buscar(string $q, int $max = 30): array
    {
        helper('gimnasio');
        $q = strtr(mb_strtolower(trim($q)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $palabras = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
        if (!$palabras) {
            return [];
        }

        $filas = \Config\Database::connect()->table('gimnasio_catalogo')
            ->select('id, nombre, parte, equipo, objetivo, imagen')
            ->orderBy('nombre')->get()->getResultArray();

        $out = [];
        foreach ($filas as $c) {
            $texto = gim_catalogo_terminos($c);
            foreach ($palabras as $p) {
                $ok = str_contains($texto, $p)
                    || (strlen($p) > 4 && str_ends_with($p, 's') && str_contains($texto, substr($p, 0, -1)));
                if (!$ok) {
                    continue 2;
                }
            }
            $out[] = $c;
            if (count($out) >= $max) {
                break;
            }
        }
        return $out;
    }

    /** Palabras significativas, sin tildes ni plurales simples. */
    private static function tokens(string $texto): array
    {
        $texto = strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $texto = str_replace(['pull-up', 'push-up', 'chin-up', 'sit-up', 'step-up'], ['pullup', 'pushup', 'chinup', 'situp', 'stepup'], $texto);
        $vacias = ['de', 'del', 'la', 'el', 'en', 'con', 'a', 'al', 'y', 'o', 'the', 'with', 'on', 'and', 'v', 'male', 'female', 'version'];

        $out = [];
        foreach (preg_split('/[^a-z0-9]+/', $texto, -1, PREG_SPLIT_NO_EMPTY) as $t) {
            if (in_array($t, $vacias, true) || ctype_digit($t)) {
                continue;
            }
            if (strlen($t) > 3 && str_ends_with($t, 's') && !str_ends_with($t, 'ss')) {
                $t = substr($t, 0, -1);
            }
            $out[$t] = true;
        }
        return array_keys($out);
    }

    /** Traduce términos de gimnasio en español a los del dataset (inglés). */
    private static function traducir(string $nombre): string
    {
        // Frases antes que palabras sueltas (strtr prioriza las claves más largas)
        static $dic = [
            'press banca' => 'bench press', 'press de banca' => 'bench press', 'peso muerto' => 'deadlift',
            'press militar' => 'military press', 'remo al menton' => 'upright row', 'jalon al pecho' => 'pulldown',
            'elevaciones laterales' => 'lateral raise', 'elevaciones frontales' => 'front raise',
            'elevaciones posteriores' => 'rear delt raise', 'elevaciones pajaro' => 'rear delt raise',
            'elevacion de talones' => 'calf raise', 'elevacion de tal' => 'calf raise', 'puente de gluteos' => 'glute bridge',
            'curl femoral' => 'leg curl', 'extension de piernas' => 'leg extension', 'prensa de piernas' => 'leg press',
            'sentadilla frontal' => 'front squat', 'sentadilla sumo' => 'sumo squat', 'sentadilla goblet' => 'goblet squat',
            'buenos dias' => 'good morning', 'farmer carry' => 'farmers walk', 'aperturas en polea' => 'cable fly',
            'press mancuerna' => 'dumbbell press', 'curl martillo' => 'hammer curl', 'curl predicador' => 'preacher curl',
            'curl concentrado' => 'concentration curl', 'curl con barra' => 'barbell curl', 'curl en polea' => 'cable curl',
            'curl alterno' => 'alternate curl', 'curl inclinado' => 'incline curl', 'curl de muneca' => 'wrist curl',
            'curl inverso de muneca' => 'reverse wrist curl', 'extension en polea' => 'cable pushdown',
            'flexiones cerradas' => 'close grip pushup', 'press banca cerrado' => 'close grip bench press',
            'rueda de pie' => 'standing wheel rollout', 'rueda de rodillas' => 'kneeling wheel rollout',
            'plancha lateral' => 'side plank', 'planchas laterales' => 'side plank', 'remo con barra' => 'barbell row',
            'remo con mancuerna' => 'dumbbell row', 'remo en maquina' => 'lever row', 'chest press maquina' => 'lever chest press',
            'leg curl en maquina' => 'lever leg curl', 'leg extension en maquina' => 'lever leg extension',
            'sentadilla' => 'squat', 'sentadillas' => 'squat', 'bulgaras' => 'split squat', 'zancadas' => 'lunge',
            'dominadas' => 'pullup', 'flexiones' => 'pushup', 'fondos' => 'dip', 'aperturas' => 'fly',
            'mancuernas' => 'dumbbell', 'mancuerna' => 'dumbbell', 'barra' => 'barbell', 'polea' => 'cable',
            'maquina' => 'lever', 'banda' => 'band', 'gomas' => 'band', 'remo' => 'row', 'planchas' => 'plank',
            'plancha' => 'plank', 'inclinado' => 'incline', 'declinado' => 'decline', 'encogimientos' => 'shrug',
            'hiperextensiones' => 'hyperextension', 'abduccion' => 'abduction', 'adduccion' => 'adduction',
            'aduccion' => 'adduction', 'cadera' => 'hip', 'gemelos' => 'calf', 'estiramiento' => 'stretch',
            'tumbado' => 'lying', 'sentado' => 'seated', 'de pie' => 'standing', 'unilateral' => 'one arm',
            'saltos' => 'jump', 'salto' => 'jump', 'comba' => 'rope', 'zancada' => 'lunge', 'gluteo' => 'glute',
            'triceps' => 'triceps', 'biceps' => 'biceps', 'hombro' => 'shoulder', 'pecho' => 'chest',
            'muneca' => 'wrist', 'cuello' => 'neck', 'isometric' => 'isometric',
        ];

        $n = strtr(mb_strtolower($nombre), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return strtr($n, $dic);
    }

    private static function grupoEncaja(?string $grupo, array $c): bool
    {
        return match ($grupo) {
            'biceps'      => $c['objetivo'] === 'biceps',
            'triceps'     => $c['objetivo'] === 'triceps',
            'hombros'     => $c['parte'] === 'shoulders',
            'espalda'     => $c['parte'] === 'back',
            'pecho'       => $c['parte'] === 'chest',
            'abdominales' => $c['parte'] === 'waist',
            'piernas'     => in_array($c['parte'], ['upper legs', 'lower legs'], true),
            'maquinas'    => in_array($c['equipo'], ['leverage machine', 'sled machine', 'smith machine'], true),
            'cardio'      => $c['parte'] === 'cardio',
            default       => false,
        };
    }
}
