<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Proxies de contenido: hasta 3 fotos + 3 vídeos por carpeta, para poder
 * identificar de un vistazo qué hay dentro sin abrir el fichero original
 * (mencionado en el resumen inicial del módulo Silo). Por ahora simulados
 * (URL de placeholder) — cuando exista la API real, esto se generará a
 * partir de miniaturas reales del fichero.
 */
class CreateSiloProxies extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pieza_id'   => ['type' => 'INT', 'unsigned' => true],
            'fichero_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tipo'       => ['type' => 'ENUM', 'constraint' => ['foto', 'video']],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'orden'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'creado_en'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pieza_id');
        $this->forge->addForeignKey('pieza_id', 'silo_piezas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('fichero_id', 'silo_ficheros', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('silo_proxies');
    }

    public function down()
    {
        $this->forge->dropTable('silo_proxies', true);
    }
}
