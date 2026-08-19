<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Los renders dejaban de poder subirse hasta la primera promoción: colgaban
 * solo de `version_id`, y antes de promocionar por primera vez no existe
 * ninguna fila en `piezas_versiones` (spec fase 31). `variante_id` pasa a
 * ser el ancla real — existe desde que se crea la pieza, igual que
 * `familia_id` para las referencias — y `version_id` pasa a ser opcional:
 * se sigue rellenando cuando el render se sube desde el historial de una
 * versión concreta, donde sigue significando "así salió esa iteración".
 */
class AllowRendersWithoutVersion extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_renders', [
            'variante_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);

        // Rellena variante_id de los renders que ya existen a partir de su versión.
        $this->db->query(
            'UPDATE piezas_renders r
             JOIN piezas_versiones v ON v.id = r.version_id
             SET r.variante_id = v.variante_id'
        );

        $this->forge->modifyColumn('piezas_renders', [
            'version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('piezas_renders', [
            'version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
        $this->forge->dropColumn('piezas_renders', 'variante_id');
    }
}
