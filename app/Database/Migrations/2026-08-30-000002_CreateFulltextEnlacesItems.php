<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Índice FULLTEXT sobre `enlaces_items(titulo, url, extra)` para poder
 * ordenar la búsqueda por relevancia real (`MATCH ... AGAINST`) en vez de
 * solo por fecha. El filtrado de "qué casa" sigue siendo el LIKE
 * multi-palabra del controlador (que además mira nombres de categoría y
 * etiqueta, cosa que este índice no cubre); el FULLTEXT solo puntúa.
 *
 * Idempotente: en algún entorno el índice ya se creó a mano con este mismo
 * nombre, así que solo se añade si no existe. El controlador comprueba su
 * presencia antes de usar `MATCH`, de modo que desplegar el código sin
 * haber pasado la migración no rompe nada (cae al orden por fecha).
 */
class CreateFulltextEnlacesItems extends Migration
{
    private const NOMBRE = 'ft_enlaces_items_titulo_url_extra';

    public function up()
    {
        $existe = (int) $this->db->query(
            "SELECT COUNT(*) c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enlaces_items'
               AND INDEX_NAME = " . $this->db->escape(self::NOMBRE)
        )->getRow()->c;

        if ($existe === 0) {
            $this->db->query(
                'ALTER TABLE `enlaces_items` ADD FULLTEXT `' . self::NOMBRE . '` (`titulo`, `url`, `extra`)'
            );
        }
    }

    public function down()
    {
        $existe = (int) $this->db->query(
            "SELECT COUNT(*) c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enlaces_items'
               AND INDEX_NAME = " . $this->db->escape(self::NOMBRE)
        )->getRow()->c;

        if ($existe > 0) {
            $this->db->query('ALTER TABLE `enlaces_items` DROP INDEX `' . self::NOMBRE . '`');
        }
    }
}
