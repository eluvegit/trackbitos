<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Segunda tanda de la bitácora (fase 38): con qué se imprimió y cuánto se
 * desvió de lo prometido.
 *
 * Los tiempos van en minutos enteros y no como texto ni como HH:MM: son la
 * mitad interesante de la bitácora —lo que el laminador prometía, lo que la
 * máquina dijo al empezar y lo que tardó de verdad—, y para restar uno de
 * otro tienen que ser números. El formulario deja escribirlos como
 * "2h 35", "2:35" o "155", que es como los lee uno de la pantalla.
 *
 * No se guarda con qué impresora se hizo: hay una sola, y una columna que
 * siempre dice lo mismo no informa de nada. (Ojo si algún día hicieran falta
 * dos: `piezas_maquinas` NO vale para eso, son los ordenadores entre los que
 * viajan las sesiones del CLI.)
 */
class AddTiemposYResinaABitacora extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            // Marca, color y lote de la resina: si una tanda sale mal, esto es
            // lo primero que se mira al comparar con la placa de la semana pasada.
            'resina'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'temperatura' => ['type' => 'DECIMAL', 'constraint' => '4,1', 'null' => true],

            // Lo prometido, por duplicado: el laminador y la propia máquina no
            // siempre dicen lo mismo, y saber cuál de los dos acierta más es
            // parte de lo que se aprende placa a placa.
            'minutos_estimados' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'minutos_previstos' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'minutos_reales'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            // En gramos, para poder compararla con la gastada de verdad, que
            // sale de restar los dos pesos del tanque.
            'resina_estimada'   => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],

            // Cómo salió, en una palabra: es lo que permite repasar el
            // histórico sin abrir placa por placa. Null mientras no se juzgue,
            // igual que una versión impresa pendiente de veredicto.
            'veredicto' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', [
            'resina', 'temperatura',
            'minutos_estimados', 'minutos_previstos', 'minutos_reales', 'resina_estimada',
            'veredicto',
        ]);
    }
}
