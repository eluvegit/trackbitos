<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Formato de cada escena (foto / vídeo / ambas; NULL = sin definir) y un
 * segundo estado de toma para las escenas «ambas».
 *
 * toma_estado sigue siendo el estado único de las escenas de un solo formato
 * (o sin formato); en las «ambas» pasa a ser el del vídeo, y la foto va en
 * toma_estado_foto. Así los datos que ya había no cambian de significado.
 */
class RodajesFormatoEscena extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rodajes_escenas', [
            'formato'             => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'escena_bloque'],
            'toma_estado_foto'    => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true, 'after' => 'toma_estado_at'],
            'toma_estado_foto_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'toma_estado_foto'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rodajes_escenas', ['formato', 'toma_estado_foto', 'toma_estado_foto_at']);
    }
}
