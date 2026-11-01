<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Resinas definidas una vez y elegidas en cada placa (resina_id). La columna
 * texto piezas_placas.resina se queda como copia del nombre para lo que ya la
 * muestra. Siembra una resina por cada texto distinto que ya había.
 */
class CreatePiezasResinas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'    => ['type' => 'VARCHAR', 'constraint' => 120],
            'notas'     => ['type' => 'TEXT', 'null' => true],
            'archivada' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'creado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_resinas');

        $this->forge->addColumn('piezas_placas', [
            'resina_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'resina'],
        ]);

        $textos = $this->db->table('piezas_placas')->select('resina, MIN(creado_en) AS desde')
            ->where('resina IS NOT NULL')->where("resina <> ''")
            ->groupBy('resina')->orderBy('desde')->get()->getResultArray();
        foreach ($textos as $t) {
            $this->db->table('piezas_resinas')->insert(['nombre' => $t['resina'], 'creado_en' => $t['desde'] ?? date('Y-m-d H:i:s')]);
            $this->db->table('piezas_placas')->where('resina', $t['resina'])->update(['resina_id' => $this->db->insertID()]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'resina_id');
        $this->forge->dropTable('piezas_resinas', true);
    }
}
