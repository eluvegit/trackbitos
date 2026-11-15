<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Una sola lista de recordatorios para Recordatorios, Coche y Lentillas.
 *
 * Hasta ahora había tres sistemas: `recordatorios` (fecha + período),
 * `car_reminders` (última acción + intervalo) y `lentillas_avisos` (última
 * sustitución + período). Esta migración pasa los dos últimos a
 * `recordatorios`; Coche muestra los de categoría «vehiculo» y Lentillas los
 * de categoría «lentillas», así que un cambio en un sitio se ve en el otro
 * porque es la misma fila.
 *
 * - recordatorios.persistente: 1 = se queda vencido hasta que se hace (los que
 *   dependen de una acción: aceite, lentillas). 0 = salta solo al siguiente
 *   ciclo tras 30 días, como hasta ahora (ITV, DNI).
 * - recordatorios.avisar: si sale en el panel y en el resumen de Telegram.
 * - recordatorios.intervalo_km: dato informativo del coche; no calcula nada.
 * - recordatorios.ultima_vez: última fecha en que se hizo.
 * - recordatorios.elemento: en Lentillas, qué sustitución lo renueva.
 * - recordatorios.fecha_evento pasa a admitir NULL (aviso sin plazo en días o
 *   que aún no se ha hecho nunca).
 * - car_actions.recordatorio_id y lentillas_sustituciones.recordatorio_id:
 *   enlace del historial con su recordatorio, por id y no por texto.
 *
 * Un aviso de Coche/Lentillas que se llame igual que un recordatorio que ya
 * existe se une a él (manda el plazo del módulo, que es el ligado al
 * historial). Las tablas `car_reminders` y `lentillas_avisos` NO se borran:
 * quedan como copia, pero el código ya no las usa.
 */
class RecordatoriosUnificados extends Migration
{
    public function up()
    {
        $this->forge->addColumn('recordatorios', [
            'persistente'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'periodo_dias'],
            'avisar'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'persistente'],
            'intervalo_km' => ['type' => 'INT', 'null' => true, 'after' => 'avisar'],
            'ultima_vez'   => ['type' => 'DATE', 'null' => true, 'after' => 'intervalo_km'],
            'elemento'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'ultima_vez'],
        ]);
        $this->db->query('ALTER TABLE recordatorios MODIFY fecha_evento DATE NULL');

        $this->forge->addColumn('car_actions', [
            'recordatorio_id' => ['type' => 'INT', 'null' => true, 'after' => 'reminder_id'],
        ]);
        $this->db->query('ALTER TABLE car_actions ADD INDEX idx_car_actions_recordatorio (recordatorio_id)');

        $this->forge->addColumn('lentillas_sustituciones', [
            'recordatorio_id' => ['type' => 'INT', 'null' => true],
        ]);
        $this->db->query('ALTER TABLE lentillas_sustituciones ADD INDEX idx_lentillas_sust_recordatorio (recordatorio_id)');

        $this->migrarCoche();
        $this->migrarLentillas();
    }

    public function down()
    {
        // Los avisos que vinieron de Coche/Lentillas siguen en sus tablas de origen.
        $this->db->query("DELETE FROM recordatorios WHERE fecha_evento IS NULL OR categoria = 'lentillas'");
        $this->db->query('ALTER TABLE recordatorios MODIFY fecha_evento DATE NOT NULL');
        $this->forge->dropColumn('recordatorios', ['persistente', 'avisar', 'intervalo_km', 'ultima_vez', 'elemento']);
        $this->forge->dropColumn('car_actions', 'recordatorio_id');
        $this->forge->dropColumn('lentillas_sustituciones', 'recordatorio_id');
    }

    private function migrarCoche(): void
    {
        if (!$this->db->tableExists('car_reminders')) return;

        $existentes = $this->db->table('recordatorios')->orderBy('id', 'ASC')->get()->getResultArray();
        $usados     = [];
        $ahora      = date('Y-m-d H:i:s');

        foreach ($this->db->table('car_reminders')->orderBy('id', 'ASC')->get()->getResultArray() as $cr) {
            $dias   = (int) ($cr['interval_days'] ?? 0);
            $km     = (int) ($cr['interval_km'] ?? 0);
            $ultima = $this->db->table('car_actions')->selectMax('date', 'f')->where('reminder_id', $cr['id'])->get()->getRow('f');
            $ultima = $ultima ? substr((string) $ultima, 0, 10) : null;

            // 365 días = 12 meses: los plazos anuales se guardan en meses, como en Recordatorios.
            [$meses, $soloDias] = $dias > 0 && $dias % 365 === 0 ? [intdiv($dias, 365) * 12, null] : [null, $dias ?: null];

            $datos = [
                'categoria'     => 'vehiculo',
                'icono'         => 'car-front',
                'persistente'   => 1,
                'intervalo_km'  => $km ?: null,
                'ultima_vez'    => $ultima,
                'updated_at'    => $ahora,
            ];
            if ($dias > 0) {
                $datos['periodo_meses'] = $meses;
                $datos['periodo_dias']  = $soloDias;
                if ($ultima) $datos['fecha_evento'] = date('Y-m-d', strtotime($meses ? "$ultima +$meses months" : "$ultima +$dias days"));
            }

            // ¿Ya hay un recordatorio con el mismo nombre? Se unen.
            $igual = null;
            foreach ($existentes as $r) {
                if (!isset($usados[$r['id']]) && $this->norm($r['titulo']) === $this->norm($cr['title'])) { $igual = $r; break; }
            }

            if ($igual) {
                $usados[$igual['id']] = true;
                if (trim((string) ($cr['notes'] ?? '')) !== '' && trim((string) $igual['notas']) === '') $datos['notas'] = $cr['notes'];
                $this->db->table('recordatorios')->where('id', $igual['id'])->update($datos);
                $rid = (int) $igual['id'];
            } else {
                $this->db->table('recordatorios')->insert($datos + [
                    'titulo'       => $cr['title'],
                    'fecha_evento' => null,
                    'notas'        => $cr['notes'] ?? null,
                    // Los frecuentes (menos de un año) nacen sin avisar en panel/Telegram.
                    'avisar'       => $dias > 0 && $dias < 365 ? 0 : 1,
                    'created_at'   => $ahora,
                ]);
                $rid = (int) $this->db->insertID();
            }

            $this->db->table('car_actions')->where('reminder_id', $cr['id'])->update(['recordatorio_id' => $rid]);
        }
    }

    private function migrarLentillas(): void
    {
        if (!$this->db->tableExists('lentillas_avisos')) return;

        $sustituciones = $this->db->table('lentillas_sustituciones')->get()->getResultArray();
        $existentes    = $this->db->table('recordatorios')->orderBy('id', 'ASC')->get()->getResultArray();
        $ahora         = date('Y-m-d H:i:s');

        foreach ($this->db->table('lentillas_avisos')->orderBy('id', 'ASC')->get()->getResultArray() as $av) {
            $clave = $this->norm($av['item']);
            // «lentillas» también se renueva al cambiar solo una de las dos.
            $claves = $clave === 'LENTILLAS' ? ['LENTILLAS', 'LENTILLA IZQUIERDA', 'LENTILLA DERECHA'] : [$clave];

            $ids = [];
            $ultima = null;
            $elemento = mb_strtolower(trim((string) $av['item']));
            foreach ($sustituciones as $s) {
                $n = $this->norm($s['elemento']);
                if (!in_array($n, $claves, true)) continue;
                $ids[] = (int) $s['id'];
                $f = substr((string) $s['fecha'], 0, 10);
                if ($ultima === null || $f > $ultima) $ultima = $f;
                if ($n === $clave) $elemento = mb_strtolower(trim((string) $s['elemento']));   // la grafía real («presión»)
            }

            $dias  = (int) $av['periodo_dias'];
            $datos = [
                'categoria'     => 'lentillas',
                'icono'         => 'eye',
                'periodo_meses' => null,
                'periodo_dias'  => $dias ?: null,
                'persistente'   => 1,
                'ultima_vez'    => $ultima,
                'elemento'      => $elemento,
                'updated_at'    => $ahora,
            ];
            if ($ultima && $dias > 0) $datos['fecha_evento'] = date('Y-m-d', strtotime("$ultima +$dias days"));

            $titulo = mb_strtoupper(mb_substr($elemento, 0, 1)) . mb_substr($elemento, 1);
            $igual  = null;
            foreach ($existentes as $r) {
                if ($this->norm($r['titulo']) === $this->norm($titulo)) { $igual = $r; break; }
            }

            if ($igual) {
                $this->db->table('recordatorios')->where('id', $igual['id'])->update($datos);
                $rid = (int) $igual['id'];
            } else {
                $this->db->table('recordatorios')->insert($datos + [
                    'titulo'       => $titulo,
                    'fecha_evento' => $datos['fecha_evento'] ?? null,
                    'avisar'       => $dias > 0 && $dias < 365 ? 0 : 1,
                    'created_at'   => $ahora,
                ]);
                $rid = (int) $this->db->insertID();
            }

            if ($ids) $this->db->table('lentillas_sustituciones')->whereIn('id', $ids)->update(['recordatorio_id' => $rid]);
        }
    }

    /** Mayúsculas, sin tildes y sin espacios sobrantes, para comparar nombres. */
    private function norm(?string $s): string
    {
        $s = strtr(trim((string) $s), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        return mb_strtoupper(preg_replace('/\s+/', ' ', $s));
    }
}
