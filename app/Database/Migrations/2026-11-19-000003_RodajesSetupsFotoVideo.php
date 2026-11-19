<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Setups de foto y de vídeo. setup_id de la toma = setup principal (foto; o
 * vídeo si la toma es solo vídeo). setup_video_id = el de vídeo en las «ambas».
 */
class RodajesSetupsFotoVideo extends Migration
{
    private array $setup = [
        'tipo'      => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'after' => 'nombre'],
        'apertura'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'soporte'],
        'velocidad' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'apertura'],
        'iso'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'velocidad'],
        'nd'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'fps'],
    ];

    public function up()
    {
        foreach ($this->setup as $c => $def) {
            if (!$this->db->fieldExists($c, 'rodajes_setups')) $this->forge->addColumn('rodajes_setups', [$c => $def]);
        }
        if (!$this->db->fieldExists('setup_video_id', 'rodajes_escenas')) {
            $this->forge->addColumn('rodajes_escenas', [
                'setup_video_id' => ['type' => 'INT', 'null' => true, 'after' => 'setup_id'],
            ]);
        }
    }

    public function down()
    {
        foreach (array_keys($this->setup) as $c) {
            if ($this->db->fieldExists($c, 'rodajes_setups')) $this->forge->dropColumn('rodajes_setups', $c);
        }
        if ($this->db->fieldExists('setup_video_id', 'rodajes_escenas')) $this->forge->dropColumn('rodajes_escenas', 'setup_video_id');
    }
}
