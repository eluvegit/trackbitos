<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bitácora de placa (fase 38): una placa deja de ser solo "qué STL llevaba"
 * y pasa a ser el cuaderno de esa impresión — cuántas copias de cada pieza,
 * qué se estaba probando y cómo salió, cuánta resina se fue, y qué hacer
 * distinto la próxima vez.
 *
 * Todo es opcional: la placa se sigue anotando sola al descargar, y la
 * bitácora se rellena después, cuando la impresión ya ha terminado y hay algo
 * que contar. Por eso todas las columnas admiten NULL en vez de traer valores
 * por defecto que fingirían un dato que nadie ha medido.
 */
class AddBitacoraToPiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            // Cuándo se imprimió de verdad, que no tiene por qué ser el día
            // que se armó la placa y se bajó el zip.
            'impresa_en'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'creado_en'],
            // Texto libre a propósito: cada resina y cada máquina lo cuentan a
            // su manera ("3.2s capa / 30s base, 0.05mm"), y encorsetarlo en
            // números obligaría a inventarse un modelo de datos por impresora.
            'exposicion'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // En gramos, con decimales: la báscula de resina da 1234.56.
            'peso_antes'   => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'peso_despues' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'notas'        => ['type' => 'TEXT', 'null' => true],
            'conclusiones' => ['type' => 'TEXT', 'null' => true],
        ]);

        $this->forge->addColumn('piezas_placas_versiones', [
            // Cuántas copias de esa pieza iban en la placa. Es anotación: el
            // zip sigue llevando el STL una sola vez, que es lo que el
            // laminador necesita para luego duplicarla en la bandeja.
            'cantidad' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 1],
            // Lo que se probó en ESA pieza y no en la placa entera: cómo se
            // orientó, qué soportes llevaba, si repitió por un fallo suyo.
            'notas'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);

        // Las pruebas van en su propia tabla y no en un campo de texto porque
        // tienen dos tiempos distintos: la pregunta se escribe ANTES de
        // imprimir ("¿aguanta la espada sin soporte en la punta?") y la
        // respuesta DESPUÉS, mirando la pieza. Con un solo campo de notas, al
        // volver semanas después no se distingue qué se preguntaba de qué se
        // averiguó.
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'placa_id'  => ['type' => 'INT', 'unsigned' => true],
            'pregunta'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'respuesta' => ['type' => 'TEXT', 'null' => true],
            'orden'     => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('placa_id');
        $this->forge->addForeignKey('placa_id', 'piezas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_placa_pruebas');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_placa_pruebas');
        $this->forge->dropColumn('piezas_placas_versiones', ['cantidad', 'notas']);
        $this->forge->dropColumn('piezas_placas', [
            'impresa_en', 'exposicion', 'peso_antes', 'peso_despues', 'notas', 'conclusiones',
        ]);
    }
}
