<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RodajesCodigoVacioNull extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE rodajes_proyectos MODIFY codigo VARCHAR(' . $this->longitud() . ') NULL');
        $this->db->query("UPDATE rodajes_proyectos SET codigo = NULL WHERE TRIM(codigo) = ''");
    }

    public function down()
    {
    }

    private function longitud(): int
    {
        foreach ($this->db->getFieldData('rodajes_proyectos') as $f) {
            if ($f->name === 'codigo') return (int) ($f->max_length ?: 50);
        }
        return 50;
    }
}
