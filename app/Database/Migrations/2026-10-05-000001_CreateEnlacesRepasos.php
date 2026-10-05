<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Repaso diario de Enlaces (`enlaces/repaso`): una tarjeta al azar cada vez,
 * una decisión por tarjeta. Cada decisión es una fila de `enlaces_repasos`;
 * de ahí salen la racha, los días, el "hoy" y qué enlaces quedan por repasar
 * — no hay tabla de racha ni contadores aparte.
 *
 * `enlaces_items.archivado_at`: "Fuera" ya no borra, archiva. Los archivados
 * no salen en el listado ni en la búsqueda salvo con el filtro Archivados.
 */
class CreateEnlacesRepasos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_id'          => ['type' => 'INT', 'unsigned' => true],
            // visto ("Me lo quedo") | fuera | luego
            'accion'           => ['type' => 'VARCHAR', 'constraint' => 10],
            // Solo "luego": a partir de qué día vuelve a salir.
            'volver_en'        => ['type' => 'DATE', 'null' => true],
            // Relevancia que tenía si se puntuó con estrellas, para poder deshacer.
            'relevancia_antes' => ['type' => 'TINYINT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('item_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('enlaces_repasos', true);

        if (! $this->db->fieldExists('archivado_at', 'enlaces_items')) {
            $this->forge->addColumn('enlaces_items', [
                'archivado_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('enlaces_items', 'archivado_at');
        $this->forge->dropTable('enlaces_repasos', true);
    }
}
