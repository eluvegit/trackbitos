<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Una versión puede tener varios STL (fase 21).
 *
 * Un modelo se imprime a trozos más veces de lo que parece: los brazos van
 * por separado aunque estén en el mismo .blend, y una pieza más alta que la
 * placa se corta y se monta. Con una sola columna `ruta_stl` eso obligaba a
 * elegir cuál de los trozos se guardaba, o a inventarse versiones falsas
 * para meter los demás.
 *
 * El `.blend` sigue siendo uno solo: ahí están todas las partes juntas, que
 * es justo lo que lo hace la fuente de la versión.
 *
 * Las columnas viejas se retiran DESPUÉS de copiar su contenido a la tabla
 * nueva: dejarlas sería tener dos sitios donde mirar y ninguna forma de
 * saber cuál manda.
 */
class CreatePiezasVersionStls extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'version_id'   => ['type' => 'INT', 'unsigned' => true],
            // Qué trozo es ("brazo izquierdo", "base", "torso superior").
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'ruta_stl'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'hash_stl'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'subido_en'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('version_id');
        // Dos trozos con el mismo nombre en la misma versión no se pueden
        // distinguir al bajarlos: el nombre es lo único que los identifica.
        $this->forge->addUniqueKey(['version_id', 'nombre']);
        $this->forge->addForeignKey('version_id', 'piezas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_version_stls');

        // Los que ya había: un STL suelto por versión, que era la pieza
        // entera. "completo" es lo que de verdad era, no un relleno.
        $this->db->query(
            "INSERT INTO piezas_version_stls (version_id, nombre, ruta_stl, hash_stl, subido_en)
             SELECT id, 'completo', ruta_stl, hash_stl, promocionada_en
             FROM piezas_versiones
             WHERE ruta_stl IS NOT NULL AND ruta_stl <> ''"
        );

        $this->forge->dropColumn('piezas_versiones', ['ruta_stl', 'hash_stl']);
    }

    public function down()
    {
        $this->forge->addColumn('piezas_versiones', [
            'ruta_stl' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'ruta_blend'],
            'hash_stl' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'ruta_stl'],
        ]);

        // Solo cabe uno: se devuelve el más antiguo de cada versión y los
        // demás se pierden como registro (sus ficheros siguen en disco).
        $this->db->query(
            'UPDATE piezas_versiones v
             JOIN (SELECT version_id, MIN(id) AS id FROM piezas_version_stls GROUP BY version_id) primero
               ON primero.version_id = v.id
             JOIN piezas_version_stls s ON s.id = primero.id
             SET v.ruta_stl = s.ruta_stl, v.hash_stl = s.hash_stl'
        );

        $this->forge->dropTable('piezas_version_stls');
    }
}
