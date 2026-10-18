<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Guías de elección para el TPV de inventario (p. ej. "Guía de brazos"):
 * una guía tiene pasos ordenados, cada paso sus opciones, y cada
 * combinación completa (una opción por paso) apunta a una variante.
 * La combinación se guarda como los ids de sus opciones ordenados
 * ("3-7-12"), así reordenar pasos no la invalida.
 */
class CreatePiezasGuias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'icono'     => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'bi-signpost-split'],
            'orden'     => ['type' => 'INT', 'default' => 0],
            'activa'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'creado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_guias');

        $this->forge->addField([
            'id'      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'guia_id' => ['type' => 'INT', 'unsigned' => true],
            'nombre'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'orden'   => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('guia_id', 'piezas_guias', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_guia_pasos');

        $this->forge->addField([
            'id'      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'paso_id' => ['type' => 'INT', 'unsigned' => true],
            'nombre'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'orden'   => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('paso_id', 'piezas_guia_pasos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_guia_opciones');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'guia_id'     => ['type' => 'INT', 'unsigned' => true],
            'combinacion' => ['type' => 'VARCHAR', 'constraint' => 255],
            'variante_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['guia_id', 'combinacion']);
        $this->forge->addForeignKey('guia_id', 'piezas_guias', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_guia_resultados');

        $this->sembrarGuiaDeBrazos();
    }

    private function sembrarGuiaDeBrazos(): void
    {
        $this->db->table('piezas_guias')->insert([
            'nombre' => 'Guía de brazos', 'icono' => 'svg-brazo', 'orden' => 1, 'activa' => 1, 'creado_en' => date('Y-m-d H:i:s'),
        ]);
        $guiaId = $this->db->insertID();

        $pasos = [
            'Cogida'        => ['Hombre', 'Mujer', 'Normal'],
            'Tipo de brazo' => ['Estándar', 'Con click'],
            'Manga'         => ['Manga larga', 'Manga corta', 'Manga corta con florituras', 'Manga corta muñeca estrecha', 'Manga corta Bullpadel', 'Desnudo'],
            'Talla'         => ['Básica', '5 mm', 'L', 'XL'],
        ];

        $ordenPaso = 1;
        foreach ($pasos as $paso => $opciones) {
            $this->db->table('piezas_guia_pasos')->insert(['guia_id' => $guiaId, 'nombre' => $paso, 'orden' => $ordenPaso++]);
            $pasoId = $this->db->insertID();
            foreach ($opciones as $i => $opcion) {
                $this->db->table('piezas_guia_opciones')->insert(['paso_id' => $pasoId, 'nombre' => $opcion, 'orden' => $i + 1]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('piezas_guia_resultados', true);
        $this->forge->dropTable('piezas_guia_opciones', true);
        $this->forge->dropTable('piezas_guia_pasos', true);
        $this->forge->dropTable('piezas_guias', true);
    }
}
