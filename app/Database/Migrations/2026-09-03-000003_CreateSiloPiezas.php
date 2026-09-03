<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Piezas de Silo: material fotográfico/vídeo resultante (editado,
 * seleccionado o entregado), nunca brutos. `nombre_carpeta` se calcula y
 * se congela en el alta (plan Silo §3) — reclasificar después es un UPDATE
 * de `categoria_id`/atributos, nunca vuelve a tocar este campo ni renombra
 * nada en disco.
 *
 * `categoria_id` es nullable a propósito: NULL = sin_clasificar es un
 * estado legítimo, no un error (mismo patrón que
 * `piezas_familias.categoria_id`, ver CreatePiezasCategorias). El filtro
 * de que la fila de silo_vocabulario referenciada sea tipo='categoria' es
 * responsabilidad de la aplicación (SiloService), no del esquema.
 */
class CreateSiloPiezas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_negocio'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'fecha'            => ['type' => 'DATE', 'null' => true],
            'tipo'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'fuente'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'categoria_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'subido'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'subido_en'        => ['type' => 'DATETIME', 'null' => true],
            'fecha_generacion' => ['type' => 'DATETIME', 'null' => true],
            'tamano_bytes'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'bloque_semantico' => ['type' => 'TEXT', 'null' => true],
            'nombre_carpeta'   => ['type' => 'VARCHAR', 'constraint' => 500],
            'notas'            => ['type' => 'TEXT', 'null' => true],
            'creado_en'        => ['type' => 'DATETIME', 'null' => true],
            'actualizado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('id_negocio');
        $this->forge->addKey('categoria_id');
        $this->forge->addForeignKey('categoria_id', 'silo_vocabulario', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('silo_piezas');
    }

    public function down()
    {
        $this->forge->dropTable('silo_piezas', true);
    }
}
