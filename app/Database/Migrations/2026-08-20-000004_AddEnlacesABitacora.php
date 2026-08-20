<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tercera tanda de la bitácora (fase 39): los enlaces de fuera y de dónde
 * viene la placa.
 *
 * Los enlaces van en su propia tabla y no en una columna `enlace` porque de
 * una misma impresión cuelgan cosas distintas —el proyecto del laminador en
 * Drive, la carpeta de fotos del resultado, el hilo donde alguien explicaba
 * esa exposición— y con una sola columna acaban todos amontonados en un
 * campo de texto del que ya no se puede pinchar ninguno. Con título propio
 * se sabe qué hay al otro lado antes de abrirlo.
 *
 * `origen_placa_id` es el hilo entre una placa y la que la repite: al cargar
 * una placa vieja en la placa actual (fase 36) y volver a bajarla, la nueva
 * recuerda de cuál viene, y así la bitácora puede enseñar arriba las
 * conclusiones de aquella —que es justo lo que se quería leer antes de
 * repetirla— y heredar sus preguntas sin responder. ON DELETE SET NULL: si
 * la vieja se borra del histórico, la nueva sigue existiendo, solo se queda
 * huérfana de antecedente. Sin clave ajena de verdad —CI4 solo sabe
 * declararlas al crear la tabla, no al añadir una columna— así que de
 * dejarla en null cuando la placa vieja se borra se encarga el controlador.
 */
class AddEnlacesABitacora extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'placa_id' => ['type' => 'INT', 'unsigned' => true],
            // 700 y no 255: los enlaces de Drive y compañía llevan un id
            // largo y a veces media docena de parámetros detrás.
            'url'      => ['type' => 'VARCHAR', 'constraint' => 700],
            // Opcional: si no se pone, la bitácora enseña el dominio, que
            // para "drive.google.com" ya dice bastante.
            'titulo'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'orden'    => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('placa_id');
        $this->forge->addForeignKey('placa_id', 'piezas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_placa_enlaces');

        $this->forge->addColumn('piezas_placas', [
            'origen_placa_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'origen_placa_id');
        $this->forge->dropTable('piezas_placa_enlaces');
    }
}
