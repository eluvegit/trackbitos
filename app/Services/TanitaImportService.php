<?php
// app/Services/TanitaImportService.php
namespace App\Services;

use App\Models\ComidasPesoModel;

class TanitaImportService
{
    /**
     * Importa un CSV exportado de una báscula Tanita a comida_pesos.
     * Si un día tiene varias pesadas, se queda con la más cercana al mediodía.
     * Si ya existe un registro para esa fecha, lo actualiza en vez de duplicar.
     *
     * @return array{total:int,dias:int,insertadas:int,actualizadas:int,errores:list<string>}
     */
    public function importFromCsv(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('El archivo no existe: ' . $path);
        }

        $normalize = function (string $s): string {
            $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // BOM
            $s = mb_strtolower($s);
            $s = preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200F}\x{2028}\x{202F}\x{205F}\x{3000}]+/u', ' ', $s);
            $s = preg_replace('/\p{C}|\p{Cf}/u', '', $s);
            $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
            $s = str_replace(['"', "'"], '', $s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return trim($s);
        };

        $findIndex = static function (array $headersNorm, array $aliases): int|false {
            foreach ($aliases as $a) {
                $i = array_search($a, $headersNorm, true);
                if ($i !== false) return $i;
            }
            return false;
        };

        $toFloat = static function ($v) {
            $v = trim((string) ($v ?? ''));
            if ($v === '' || $v === '-') return null;
            return (float) str_replace(',', '.', $v);
        };
        $toInt = static function ($v) use ($toFloat) {
            $f = $toFloat($v);
            return $f === null ? null : (int) round($f);
        };

        $fh = fopen($path, 'r');
        if (!$fh) {
            throw new \RuntimeException('No se pudo abrir el archivo.');
        }

        $firstLine = fgets($fh) ?: '';
        rewind($fh);
        $sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $header = fgetcsv($fh, 0, $sep);
        if (!$header) {
            fclose($fh);
            throw new \RuntimeException('CSV sin cabeceras.');
        }
        $headersNorm = array_map($normalize, $header);

        $idx = [
            'fecha'         => $findIndex($headersNorm, ['date', 'fecha']),
            'peso'          => $findIndex($headersNorm, ['weight (kg)', 'peso']),
            'imc'           => $findIndex($headersNorm, ['bmi', 'imc']),
            'grasa'         => $findIndex($headersNorm, ['body fat (%)']),
            'visc_fat'      => $findIndex($headersNorm, ['visc fat']),
            'masa_muscular' => $findIndex($headersNorm, ['muscle mass (kg)']),
            'masa_osea'     => $findIndex($headersNorm, ['bone mass (kg)']),
            'bmr'           => $findIndex($headersNorm, ['bmr (kcal)']),
            'edad_metab'    => $findIndex($headersNorm, ['metab age']),
            'agua'          => $findIndex($headersNorm, ['body water (%)']),
            'fisica'        => $findIndex($headersNorm, ['physique rating']),
        ];

        if ($idx['fecha'] === false || $idx['peso'] === false) {
            fclose($fh);
            throw new \RuntimeException('Faltan columnas obligatorias (Date y/o Weight (kg)) en el CSV.');
        }

        // Agrupar por día, quedándonos con la pesada más cercana al mediodía
        $porDia = [];
        $total = 0;

        while (($row = fgetcsv($fh, 0, $sep)) !== false) {
            $total++;

            $rawFecha = trim((string) ($row[$idx['fecha']] ?? ''));
            if ($rawFecha === '') continue;

            $ts = strtotime($rawFecha);
            if (!$ts) continue;

            $dia = date('Y-m-d', $ts);
            $segundos = (int) date('H', $ts) * 3600 + (int) date('i', $ts) * 60 + (int) date('s', $ts);
            $distanciaMediodia = abs($segundos - 12 * 3600);

            if (!isset($porDia[$dia]) || $distanciaMediodia < $porDia[$dia]['dist']) {
                $porDia[$dia] = ['dist' => $distanciaMediodia, 'row' => $row];
            }
        }
        fclose($fh);

        $model = new ComidasPesoModel();
        $insertadas = 0;
        $actualizadas = 0;
        $errores = [];

        ksort($porDia);

        foreach ($porDia as $dia => $info) {
            $row = $info['row'];

            $peso = $toFloat($row[$idx['peso']] ?? null);
            if ($peso === null) {
                $errores[] = "Sin peso válido para {$dia}, se omite.";
                continue;
            }

            $payload = [
                'fecha'                  => $dia,
                'peso'                   => $peso,
                'imc'                    => $idx['imc'] !== false ? $toFloat($row[$idx['imc']] ?? null) : null,
                'grasa_corporal_pct'     => $idx['grasa'] !== false ? $toFloat($row[$idx['grasa']] ?? null) : null,
                'grasa_visceral'         => $idx['visc_fat'] !== false ? $toFloat($row[$idx['visc_fat']] ?? null) : null,
                'masa_muscular_kg'       => $idx['masa_muscular'] !== false ? $toFloat($row[$idx['masa_muscular']] ?? null) : null,
                'masa_osea_kg'           => $idx['masa_osea'] !== false ? $toFloat($row[$idx['masa_osea']] ?? null) : null,
                'metabolismo_basal_kcal' => $idx['bmr'] !== false ? $toInt($row[$idx['bmr']] ?? null) : null,
                'edad_metabolica'        => $idx['edad_metab'] !== false ? $toInt($row[$idx['edad_metab']] ?? null) : null,
                'agua_corporal_pct'      => $idx['agua'] !== false ? $toFloat($row[$idx['agua']] ?? null) : null,
                'valoracion_fisica'      => $idx['fisica'] !== false ? $toInt($row[$idx['fisica']] ?? null) : null,
            ];

            try {
                $existente = $model->where('fecha', $dia)->first();
                if ($existente) {
                    $model->update($existente['id'], $payload);
                    $actualizadas++;
                } else {
                    $model->insert($payload);
                    $insertadas++;
                }
            } catch (\Throwable $e) {
                $errores[] = "Error en {$dia}: " . $e->getMessage();
            }
        }

        return [
            'total'        => $total,
            'dias'         => count($porDia),
            'insertadas'   => $insertadas,
            'actualizadas' => $actualizadas,
            'errores'      => $errores,
        ];
    }
}
