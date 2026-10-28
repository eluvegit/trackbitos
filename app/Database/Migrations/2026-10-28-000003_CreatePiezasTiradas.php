<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tiradas: planificar una producción grande (p. ej. 250 muñecos) en placas
 * repartidas por días y máquinas, con plazo, día de confirmación del diseño
 * (lo que espera a él no se imprime antes) y progreso de lo ya impreso.
 *
 * - piezas_tiradas: la tirada y sus ajustes del plan.
 * - piezas_tiradas_tipos: cada clase de pieza (brazos, torsos…) con su
 *   resina, cuántas caben por placa y cuántas lleva cada muñeco.
 * - piezas_tiradas_placas: las placas en su orden de cola; la máquina y el
 *   día salen de simularlo en la vista, no se guardan.
 *
 * Deja cargada la tirada de 250 muñecos con la que nació la sección.
 */
class CreatePiezasTiradas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'notas'            => ['type' => 'TEXT', 'null' => true],
            'fecha_inicio'     => ['type' => 'DATE', 'null' => true],
            'plazo_dias'       => ['type' => 'INT', 'unsigned' => true, 'default' => 14],
            'objetivo_h'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'objetivo_m'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'maquinas'         => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => "A: blanco\nB: gris"],
            'placas_dia'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 2],
            'placas_correo'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'dia_confirmacion' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'cambio_resina'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'dias_fuera'       => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'creado_en'        => ['type' => 'DATETIME', 'null' => true],
            'actualizado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_tiradas');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tirada_id'  => ['type' => 'INT', 'unsigned' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'resina'     => ['type' => 'VARCHAR', 'constraint' => 40],
            'por_placa'  => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'por_muneco' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'sexuado'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'bloqueado'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'color'      => ['type' => 'VARCHAR', 'constraint' => 9, 'default' => '#9aa5b3'],
            'orden'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tirada_id');
        $this->forge->addForeignKey('tirada_id', 'piezas_tiradas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_tiradas_tipos');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tirada_id'  => ['type' => 'INT', 'unsigned' => true],
            'tipo_id'    => ['type' => 'INT', 'unsigned' => true],
            'codigo'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'hombres'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'mujeres'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'unidades'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'motivo'     => ['type' => 'TEXT', 'null' => true],
            'orden'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'impresa_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tirada_id');
        $this->forge->addForeignKey('tirada_id', 'piezas_tiradas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tipo_id', 'piezas_tiradas_tipos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_tiradas_placas');

        $this->sembrar250();
    }

    public function down()
    {
        $this->forge->dropTable('piezas_tiradas_placas', true);
        $this->forge->dropTable('piezas_tiradas_tipos', true);
        $this->forge->dropTable('piezas_tiradas', true);
    }

    /** La tirada de 250 muñecos (125 H / 125 M) tal y como quedó planificada. */
    private function sembrar250(): void
    {
        $ahora = date('Y-m-d H:i:s');
        $this->db->table('piezas_tiradas')->insert([
            'nombre'           => 'Tirada 250 muñecos',
            'notas'            => implode("\n\n", [
                "Una resina por máquina\nLa máquina A va siempre con blanco (brazos, piernas, torsos) y la B con gris (estructuras, raquetas). Cada cambio supone vaciar y limpiar la cubeta, tiempo perdido y riesgo de mezclar resina. Son 18 placas blancas y 7 grises: si la confirmación llega pronto, entra en plazo sin cambiar nada.",
                "Primero lo que ya se puede imprimir\nAntes de la confirmación solo pueden salir brazos y estructuras, así que ocupan los primeros días. Así esos días no se pierden y, cuando llega la confirmación, la máquina blanca queda libre para piernas y torsos.",
                "Brazos de hombre antes que los de mujer\nLos brazos de hombre llevan 2 colores y los de mujer 1. Si salen antes, se pueden empezar a pintar antes, y la pintura es lo que más tarda.",
                "Piernas y torsos en pareja\nTras la confirmación, cada día la máquina A saca una placa de piernas y una de torsos del mismo sexo. Son 40 muñecos completos por día, en vez de 80 piernas que esperan a sus cuerpos. Primero hombres, luego una placa mixta (20 + 20) y después mujeres.",
                "Las piernas, cuanto antes\nLas piernas tienen 3 colores y 3 días de pintura, así que son el camino crítico del pintado. Por eso son lo primero que entra en cuanto se confirma el diseño.",
                "Correo y en casa\nDe cada máquina, la primera placa del día se manda por correo y la segunda necesita que estés en casa. Si un día no vas a estar, márcalo como «fuera»: ese día solo salen las de correo y el plan se reparte de nuevo.",
                "Cuándo cambiar la B a blanco\nSolo si la confirmación se retrasa o te quedas sin margen. Cuando la B termina el gris (o no tiene nada gris desbloqueado) pasa a blanco y ayuda con piernas y torsos. El aviso de arriba dice el último día de confirmación que aguanta cada opción.",
                "Margen extra\nPiernas y torsos llevan +12 % (15 de más por sexo), brazos +35 % (la 4.ª placa es repuesto y va al final), estructuras +28 % y raquetas +20 %. Con placas enteras no se puede afinar más: con 3 placas de brazos el margen sería de solo 7 piezas.",
            ]),
            'fecha_inicio'     => '2026-10-28',
            'plazo_dias'       => 14,
            'objetivo_h'       => 125,
            'objetivo_m'       => 125,
            'maquinas'         => "A: blanco\nB: gris",
            'placas_dia'       => 2,
            'placas_correo'    => 1,
            'dia_confirmacion' => 3,
            'cambio_resina'    => 1,
            'dias_fuera'       => '',
            'creado_en'        => $ahora,
            'actualizado_en'   => $ahora,
        ]);
        $tiradaId = (int) $this->db->insertID();

        $tipos = [
            'brazos'      => ['Brazos', 'blanco', 169, 2, 1, 0, '#3bbdb5'],
            'piernas'     => ['Piernas', 'blanco', 40, 1, 1, 1, '#a58af0'],
            'torsos'      => ['Torsos', 'blanco', 40, 1, 1, 1, '#6c9cf0'],
            'estructuras' => ['Estructuras', 'gris', 80, 1, 0, 0, '#9aa5b3'],
            'raquetas'    => ['Raquetas', 'gris', 100, 1, 0, 1, '#93c45a'],
        ];
        $ids = [];
        $orden = 0;
        foreach ($tipos as $clave => [$nombre, $resina, $porPlaca, $porMuneco, $sexuado, $bloqueado, $color]) {
            $this->db->table('piezas_tiradas_tipos')->insert([
                'tirada_id' => $tiradaId, 'nombre' => $nombre, 'resina' => $resina,
                'por_placa' => $porPlaca, 'por_muneco' => $porMuneco, 'sexuado' => $sexuado,
                'bloqueado' => $bloqueado, 'color' => $color, 'orden' => $orden++,
            ]);
            $ids[$clave] = (int) $this->db->insertID();
        }

        $placas = [
            'P1' => ['brazos', 169, 0, 0, 'Brazos de hombre. Van los primeros porque llevan 2 colores (más pintura) y no dependen de la confirmación del diseño.'],
            'P2' => ['brazos', 81, 88, 0, 'Completa los brazos de hombre (169 + 81 = 250) y empieza los de mujer con 88. Mezclar sexos en placa aprovecha la placa entera.'],
            'P3' => ['brazos', 0, 169, 0, 'Brazos de mujer: con los 88 de P2 suman 257 para 250 necesarios.'],
            'P4' => ['brazos', 85, 84, 0, 'Placa de repuesto. Sin ella ya hay brazos para los 250, pero con solo 7 de margen. Va al final del blanco porque solo cubre fallos.'],
        ];
        $mitades = [[40, 0], [40, 0], [40, 0], [20, 20], [0, 40], [0, 40], [0, 40]];
        foreach ($mitades as $i => [$h, $m]) {
            $n = $i + 1;
            $quien = $h && $m ? '20 hombre + 20 mujer' : ($h ? '40 hombre' : '40 mujer');
            $placas['L' . $n] = ['piernas', $h, $m, 0, "Piernas: {$quien}. Son la pieza de más colores (3) y necesitan 3 días de pintura, por eso salen en cuanto se confirma el diseño. Van en pareja con T{$n} (mismo sexo) para cerrar muñecos completos."];
            $placas['T' . $n] = ['torsos', $h, $m, 0, "Torsos: {$quien}. Pareja de L{$n}: el mismo día salen piernas y torsos del mismo sexo, así cada día de impresión son 40 muñecos completos."];
        }
        foreach (['E1', 'E2', 'E3', 'E4'] as $c) {
            $placas[$c] = ['estructuras', 0, 0, 80, 'Estructuras en gris. No dependen del diseño, así que ocupan la máquina B desde el primer día. 4 placas = 320 para 250 (+28 %), porque 3 placas se quedarían en 240.'];
        }
        foreach (['R1', 'R2', 'R3'] as $c) {
            $placas[$c] = ['raquetas', 0, 0, 100, 'Raquetas en gris. Esperan a la confirmación del diseño. 3 placas = 300 para 250 (+20 %).'];
        }

        // Orden de cola: dentro de cada resina, la máquina coge la primera
        // placa que no esté bloqueada ese día.
        $cola = ['P1', 'L1', 'T1', 'P2', 'L2', 'T2', 'L3', 'T3', 'L4', 'T4', 'P3', 'L5', 'T5', 'L6', 'T6', 'L7', 'T7', 'P4',
                 'E1', 'E2', 'E3', 'E4', 'R1', 'R2', 'R3'];
        foreach ($cola as $orden => $codigo) {
            [$tipo, $h, $m, $u, $motivo] = $placas[$codigo];
            $this->db->table('piezas_tiradas_placas')->insert([
                'tirada_id' => $tiradaId, 'tipo_id' => $ids[$tipo], 'codigo' => $codigo,
                'hombres' => $h, 'mujeres' => $m, 'unidades' => $u ?: $h + $m,
                'motivo' => $motivo, 'orden' => $orden,
            ]);
        }
    }
}
