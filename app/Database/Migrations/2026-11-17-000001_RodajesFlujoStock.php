<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RodajesFlujoStock extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE moodboard_items MODIFY origen ENUM('archivo','enlace','texto') NOT NULL");
        $this->forge->addColumn('moodboard_items', [
            'buscar_ref'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'nota'],
            'estado'      => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'pendiente', 'after' => 'buscar_ref'],
            'escena_id'   => ['type' => 'INT', 'null' => true, 'after' => 'estado'],
            'variante_id' => ['type' => 'INT', 'null' => true, 'after' => 'escena_id'],
        ]);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'proyecto_id' => ['type' => 'INT'],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'notas'       => ['type' => 'TEXT', 'null' => true],
            'imagen'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'orden'       => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('proyecto_id');
        $this->forge->createTable('rodajes_ubicaciones', true);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'ubicacion_id' => ['type' => 'INT'],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'x'            => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'y'            => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'orden'        => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ubicacion_id');
        $this->forge->createTable('rodajes_puntos', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'proyecto_id' => ['type' => 'INT'],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'optica'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'soporte'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'fps'         => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'luz'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notas'       => ['type' => 'TEXT', 'null' => true],
            'orden'       => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('proyecto_id');
        $this->forge->createTable('rodajes_setups', true);

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'auto_increment' => true],
            'escena_id'           => ['type' => 'INT'],
            'texto'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'prioridad'           => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'imp'],
            'moodboard_item_id'   => ['type' => 'INT', 'null' => true],
            'toma_estado'         => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true],
            'toma_estado_at'      => ['type' => 'DATETIME', 'null' => true],
            'toma_estado_foto'    => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true],
            'toma_estado_foto_at' => ['type' => 'DATETIME', 'null' => true],
            'orden'               => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('escena_id');
        $this->forge->createTable('rodajes_variantes', true);

        $this->forge->addColumn('rodajes_escenas', [
            'ubicacion_id' => ['type' => 'INT', 'null' => true, 'after' => 'escena_ubicacion'],
            'punto_id'     => ['type' => 'INT', 'null' => true, 'after' => 'ubicacion_id'],
            'setup_id'     => ['type' => 'INT', 'null' => true, 'after' => 'punto_id'],
            'franja'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'setup_id'],
            'prioridad'    => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'imp', 'after' => 'franja'],
            'props'        => ['type' => 'TEXT', 'null' => true, 'after' => 'prioridad'],
        ]);

        $this->migrarUbicacionesYSetups();
    }

    private function migrarUbicacionesYSetups(): void
    {
        $escenas = $this->db->table('rodajes_escenas')->where('deleted_at', null)
            ->orderBy('proyecto_id')->orderBy('orden')->orderBy('id')->get()->getResultArray();

        $zonas = [];
        $setups = [];
        foreach ($escenas as $e) {
            $pid = (int) $e['proyecto_id'];
            $datos = [];

            $ub = trim((string) $e['escena_ubicacion']);
            if ($ub !== '') {
                $k = $pid . '|' . mb_strtolower($ub);
                if (!isset($zonas[$k])) {
                    $orden = count(array_filter(array_keys($zonas), static fn ($z) => str_starts_with($z, $pid . '|')));
                    $this->db->table('rodajes_ubicaciones')->insert(['proyecto_id' => $pid, 'nombre' => mb_substr($ub, 0, 150), 'orden' => $orden]);
                    $zonas[$k] = (int) $this->db->insertID();
                }
                $datos['ubicacion_id'] = $zonas[$k];
            }

            $opt = trim((string) $e['camara_optica']);
            $sop = trim((string) $e['camara_soporte']);
            $fps = trim((string) $e['camara_fps']);
            if ($opt !== '' || $sop !== '') {
                $k = $pid . '|' . mb_strtolower("$opt|$sop|$fps");
                if (!isset($setups[$k])) {
                    $orden = count(array_filter(array_keys($setups), static fn ($z) => str_starts_with($z, $pid . '|')));
                    $nombre = implode(' · ', array_filter([$opt, $sop, is_numeric($fps) ? "$fps fps" : $fps]));
                    $this->db->table('rodajes_setups')->insert([
                        'proyecto_id' => $pid, 'nombre' => mb_substr($nombre, 0, 150),
                        'optica' => mb_substr($opt, 0, 60) ?: null, 'soporte' => mb_substr($sop, 0, 30) ?: null, 'fps' => mb_substr($fps, 0, 10) ?: null,
                        'orden' => $orden,
                    ]);
                    $setups[$k] = (int) $this->db->insertID();
                }
                $datos['setup_id'] = $setups[$k];
            }

            if ($datos) $this->db->table('rodajes_escenas')->where('id', $e['id'])->update($datos);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('rodajes_escenas', ['ubicacion_id', 'punto_id', 'setup_id', 'franja', 'prioridad', 'props']);
        $this->forge->dropTable('rodajes_variantes', true);
        $this->forge->dropTable('rodajes_setups', true);
        $this->forge->dropTable('rodajes_puntos', true);
        $this->forge->dropTable('rodajes_ubicaciones', true);
        $this->forge->dropColumn('moodboard_items', ['buscar_ref', 'estado', 'escena_id', 'variante_id']);
        $this->db->query("DELETE FROM moodboard_items WHERE origen = 'texto'");
        $this->db->query("ALTER TABLE moodboard_items MODIFY origen ENUM('archivo','enlace') NOT NULL");
    }
}
