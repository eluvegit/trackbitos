<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Props propios de cada variante: lo que lleva ADEMÁS de los props de su toma
 * (p. ej. la toma lleva el móvil y una variante, además, una taza). JSON igual
 * que rodajes_escenas.props.
 */
class RodajesPropsVariantes extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('props', 'rodajes_variantes')) {
            $this->forge->addColumn('rodajes_variantes', [
                'props' => ['type' => 'TEXT', 'null' => true, 'after' => 'prioridad'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('props', 'rodajes_variantes')) {
            $this->forge->dropColumn('rodajes_variantes', 'props');
        }
    }
}
