<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Clasificación de placas en Guardada/Lista para imprimir/Impresa: hace
 * falta saber si el zip llegó a bajarse de verdad (`descargada_en`, solo se
 * pone al pulsar "Descargar" desde el carrito, nunca al "guardar para
 * después" ni al volver a descargar una placa ya del histórico) y de qué
 * pedido de sterclicks salió, para el circuito pedido -> placa -> piezas
 * completadas.
 */
class AddDescargadaYPedidoAPiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'descargada_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'creado_en'],
            'pedido_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'origen_placa_id'],
        ]);
        $this->forge->addForeignKey('pedido_id', 'piezas_pedidos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('piezas_placas');

        // Las placas ya existentes se consideran "descargadas" desde que se
        // crearon: no hay forma de recuperar la fecha real de descarga a
        // posteriori, y es mejor dato que dejarlas como "solo guardadas".
        $this->db->query('UPDATE piezas_placas SET descargada_en = creado_en WHERE descargada_en IS NULL');
    }

    public function down()
    {
        $this->forge->dropForeignKey('piezas_placas', 'piezas_placas_pedido_id_foreign');
        $this->forge->dropColumn('piezas_placas', ['descargada_en', 'pedido_id']);
    }
}
