<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Módulo Piezas: versionado de modelos 3D (familia → variante → version,
 * con rama/sesion para el trabajo en curso y descarga como asiento de
 * sincronización entre máquinas). Ver especificación completa en el chat;
 * los invariantes 1-4 se aplican en los modelos, no aquí.
 */
class CreatePiezasTables extends Migration
{
    public function up()
    {
        // ---- Máquinas ----
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uuid'        => ['type' => 'VARCHAR', 'constraint' => 36],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'hostname'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'so'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'primera_vez' => ['type' => 'DATETIME', 'null' => true],
            'ultima_vez'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->createTable('piezas_maquinas');

        // ---- Familias ----
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'notas'      => ['type' => 'TEXT', 'null' => true],
            'creado_en'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_familias');

        // ---- Variantes ----
        // origen_version_id apunta a piezas_versiones, que todavía no existe
        // (dependencia circular familia→variante→version→variante). La FK
        // se añade más abajo, una vez creada piezas_versiones.
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'familia_id'        => ['type' => 'INT', 'unsigned' => true],
            'nombre'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'origen_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notas'             => ['type' => 'TEXT', 'null' => true],
            'creado_en'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('familia_id');
        $this->forge->addForeignKey('familia_id', 'piezas_familias', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_variantes');

        // ---- Versiones ----
        // Congeladas: ruta_blend/hash_blend/ruta_stl/hash_stl/numero no se
        // editan tras crearse (invariante 4, aplicado en VersionModel).
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'variante_id'      => ['type' => 'INT', 'unsigned' => true],
            'numero'           => ['type' => 'SMALLINT', 'unsigned' => true],
            'estado'           => ['type' => 'ENUM', 'constraint' => ['borrador', 'impresa', 'validada', 'superada', 'descartada'], 'default' => 'borrador'],
            'promocionada_en'  => ['type' => 'DATETIME', 'null' => true],
            'ruta_blend'       => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_blend'       => ['type' => 'VARCHAR', 'constraint' => 64],
            'ruta_stl'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'hash_stl'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            // Obligatorio a nivel de modelo (invariante 7): qué se cambió.
            'cambio'           => ['type' => 'TEXT'],
            'medidas'          => ['type' => 'TEXT', 'null' => true],
            'params_impresion' => ['type' => 'TEXT', 'null' => true],
            'resultado'        => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['variante_id', 'numero']);
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_versiones');

        // Ahora que piezas_versiones existe, cierra el círculo con variantes.
        $this->db->query(
            'ALTER TABLE `piezas_variantes` ADD CONSTRAINT `piezas_variantes_origen_version_fk` '
            . 'FOREIGN KEY (`origen_version_id`) REFERENCES `piezas_versiones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
        );

        // ---- Ramas ----
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'variante_id'            => ['type' => 'INT', 'unsigned' => true],
            'desde_version_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'abierta'                => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'cerrada_por_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'abierta_en'             => ['type' => 'DATETIME', 'null' => true],
            'cerrada_en'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('variante_id');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('desde_version_id', 'piezas_versiones', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('cerrada_por_version_id', 'piezas_versiones', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('piezas_ramas');

        // ---- Sesiones de trabajo (Blender) ----
        // OJO: nombre de tabla prefijado a propósito. Ya existe una tabla
        // "sesiones" (SesionModel) para el módulo de rodajes fotográficos,
        // completamente distinto — no confundir ni reutilizar ese modelo.
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rama_id'      => ['type' => 'INT', 'unsigned' => true],
            'numero'       => ['type' => 'SMALLINT', 'unsigned' => true],
            'maquina_id'   => ['type' => 'INT', 'unsigned' => true],
            'abierta_en'   => ['type' => 'DATETIME', 'null' => true],
            'cerrada_en'   => ['type' => 'DATETIME', 'null' => true],
            'ruta_blend'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'hash_blend'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            // Hash del que se partió al descargar (encadena con la sesión anterior).
            'hash_padre'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'subida_en'    => ['type' => 'DATETIME', 'null' => true],
            'log'          => ['type' => 'TEXT', 'null' => true],
            'purgada'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('rama_id');
        $this->forge->addUniqueKey(['rama_id', 'numero']);
        $this->forge->addForeignKey('rama_id', 'piezas_ramas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('maquina_id', 'piezas_maquinas', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('piezas_sesiones');

        // ---- Descargas (asiento de sincronización) ----
        // Append-only: nunca se borra, solo se cierra. Ver invariante 8.
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sesion_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'variante_id'        => ['type' => 'INT', 'unsigned' => true],
            'rama_id'            => ['type' => 'INT', 'unsigned' => true],
            'maquina_id'         => ['type' => 'INT', 'unsigned' => true],
            'motivo'             => ['type' => 'ENUM', 'constraint' => ['trabajo', 'consulta']],
            'descargado_en'      => ['type' => 'DATETIME', 'null' => true],
            'hash_entregado'     => ['type' => 'VARCHAR', 'constraint' => 64],
            'cerrada'            => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'cerrada_en'         => ['type' => 'DATETIME', 'null' => true],
            'cerrada_por'        => ['type' => 'ENUM', 'constraint' => ['subida', 'sin_cambios', 'forzado'], 'null' => true],
            'cerrada_sesion_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'motivo_forzado'     => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('maquina_id');
        $this->forge->addForeignKey('sesion_id', 'piezas_sesiones', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('rama_id', 'piezas_ramas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('maquina_id', 'piezas_maquinas', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('cerrada_sesion_id', 'piezas_sesiones', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('piezas_descargas');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_descargas', true);
        $this->forge->dropTable('piezas_sesiones', true);
        $this->forge->dropTable('piezas_ramas', true);
        $this->db->query('ALTER TABLE `piezas_variantes` DROP FOREIGN KEY `piezas_variantes_origen_version_fk`');
        $this->forge->dropTable('piezas_versiones', true);
        $this->forge->dropTable('piezas_variantes', true);
        $this->forge->dropTable('piezas_familias', true);
        $this->forge->dropTable('piezas_maquinas', true);
    }
}
