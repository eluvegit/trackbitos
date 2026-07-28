<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixCarTablesAutoIncrement extends Migration
{
    /**
     * car_actions, car_reminders y car_faults nunca tuvieron AUTO_INCREMENT
     * ni PRIMARY KEY en `id` (probablemente creadas a mano fuera de una
     * migración). Mientras nadie insertaba una fila nueva no se notaba,
     * pero en cuanto el modelo hace save() sin 'id', MySQL/MariaDB rellena
     * la columna NOT NULL con 0 en vez de fallar — y CodeIgniter rechaza
     * borrar id=0 como medida de seguridad ("Invalid primary key: '0' is
     * not allowed"), que es el error real que se veía al borrar acciones.
     */
    public function up()
    {
        // Renumera cualquier fila que ya haya quedado con id=0 antes de
        // fijar la clave primaria (evita choques de unicidad).
        foreach (['car_actions', 'car_reminders', 'car_faults'] as $tabla) {
            $maxId = (int) ($this->db->table($tabla)->selectMax('id')->get()->getRow('id') ?? 0);
            $rotas = $this->db->table($tabla)->where('id', 0)->get()->getResultArray();
            foreach ($rotas as $fila) {
                $maxId++;
                $this->db->table($tabla)
                    ->where('id', 0)
                    ->limit(1)
                    ->update(['id' => $maxId]);
            }
        }

        // MySQL exige que la columna AUTO_INCREMENT sea ya una clave, así
        // que MODIFY y ADD PRIMARY KEY van en la misma sentencia.
        foreach (['car_actions', 'car_reminders', 'car_faults'] as $tabla) {
            $this->db->query("ALTER TABLE {$tabla} MODIFY id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)");
        }
    }

    public function down()
    {
        foreach (['car_actions', 'car_reminders', 'car_faults'] as $tabla) {
            $this->db->query("ALTER TABLE {$tabla} MODIFY id INT NOT NULL, DROP PRIMARY KEY");
        }
    }
}
