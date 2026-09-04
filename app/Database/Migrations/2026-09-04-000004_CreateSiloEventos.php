<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Log para el panel de alertas de Silo: nada de lo que hace el escaneo
 * queda en silencio (plan/doc "Nada silencioso"). `tipo`: `carpeta_saltada`
 * (con `motivo`: prefijo/lista_negra/no_es_pieza/no_es_carpeta),
 * `id_duplicado` (dos carpetas con el mismo ID de negocio en un mismo
 * escaneo) o `escaneo` (resumen de una pasada). `pieza_id` sin FK a
 * propósito: el evento sobrevive aunque la pieza se borre después.
 */
class CreateSiloEventos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tipo'        => ['type' => 'VARCHAR', 'constraint' => 40],
            'unidad_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'pieza_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'referencia'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'motivo'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'detalle'     => ['type' => 'TEXT', 'null' => true],
            'creado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('unidad_id');
        $this->forge->addKey('tipo');
        $this->forge->addForeignKey('unidad_id', 'silo_unidades', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('silo_eventos');
    }

    public function down()
    {
        $this->forge->dropTable('silo_eventos', true);
    }
}
