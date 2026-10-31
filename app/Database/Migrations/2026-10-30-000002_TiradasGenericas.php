<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tiradas genéricas: un encargo son piezas con sus condiciones (resina,
 * necesarias, margen, por placa, prioridad de pintura, espera confirmación)
 * y cada placa lleva líneas de pieza con cantidad y fallidas. Desaparecen
 * muñecos, H/M y por_muneco. Los tipos con H/M se parten en dos piezas.
 */
class TiradasGenericas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tirada_id'           => ['type' => 'INT', 'unsigned' => true],
            'nombre'              => ['type' => 'VARCHAR', 'constraint' => 80],
            'resina'              => ['type' => 'VARCHAR', 'constraint' => 40],
            'necesarias'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'margen'              => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'por_placa'           => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'prioridad'           => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'espera_confirmacion' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'color'               => ['type' => 'VARCHAR', 'constraint' => 9, 'default' => '#9aa5b3'],
            'orden'               => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tirada_id');
        $this->forge->addForeignKey('tirada_id', 'piezas_tiradas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_tiradas_piezas');

        $this->forge->addField([
            'id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'placa_id' => ['type' => 'INT', 'unsigned' => true],
            'pieza_id' => ['type' => 'INT', 'unsigned' => true],
            'cantidad' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'fallidas' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('placa_id');
        $this->forge->addForeignKey('placa_id', 'piezas_tiradas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pieza_id', 'piezas_tiradas_piezas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_tiradas_lineas');

        $this->convertir();

        $this->forge->dropForeignKey('piezas_tiradas_placas', 'piezas_tiradas_placas_tipo_id_foreign');
        $this->forge->dropColumn('piezas_tiradas_placas', ['tipo_id', 'hombres', 'mujeres', 'unidades']);
        $this->forge->dropTable('piezas_tiradas_tipos', true);
        $this->forge->dropColumn('piezas_tiradas', ['objetivo_h', 'objetivo_m']);
    }

    public function down()
    {
        throw new \RuntimeException('TiradasGenericas no se puede deshacer: la copia está en writable/backups/tiradas_antes_genericas_2026-10-30.sql');
    }

    private function convertir(): void
    {
        foreach ($this->db->table('piezas_tiradas')->get()->getResultArray() as $tirada) {
            $placas = $this->db->table('piezas_tiradas_placas')->where('tirada_id', $tirada['id'])->get()->getResultArray();
            $orden  = 0;
            $piezas = [];
            $tipos  = $this->db->table('piezas_tiradas_tipos')->where('tirada_id', $tirada['id'])->orderBy('orden')->orderBy('id')->get()->getResultArray();
            foreach ($tipos as $tipo) {
                $de    = array_filter($placas, static fn (array $p) => (int) $p['tipo_id'] === (int) $tipo['id']);
                $lados = $tipo['sexuado']
                    ? ['h' => [' H', (int) $tirada['objetivo_h'], 'hombres'], 'm' => [' M', (int) $tirada['objetivo_m'], 'mujeres']]
                    : ['u' => ['', (int) $tirada['objetivo_h'] + (int) $tirada['objetivo_m'], 'unidades']];
                foreach ($lados as $lado => [$sufijo, $objetivo, $campo]) {
                    $necesarias = (int) $tipo['por_muneco'] * $objetivo;
                    $total      = array_sum(array_map(static fn (array $p) => (int) $p[$campo], $de));
                    $this->db->table('piezas_tiradas_piezas')->insert([
                        'tirada_id'           => $tirada['id'],
                        'nombre'              => $tipo['nombre'] . $sufijo,
                        'resina'              => $tipo['resina'],
                        'necesarias'          => $necesarias,
                        'margen'              => $necesarias ? max(0, (int) floor(($total - $necesarias) / $necesarias * 100)) : 0,
                        'por_placa'           => $tipo['por_placa'],
                        'espera_confirmacion' => $tipo['bloqueado'],
                        'color'               => $tipo['color'],
                        'orden'               => $orden++,
                    ]);
                    $piezas[$tipo['id']][$campo] = (int) $this->db->insertID();
                }
            }
            foreach ($placas as $placa) {
                foreach ($piezas[$placa['tipo_id']] ?? [] as $campo => $piezaId) {
                    if ((int) $placa[$campo] > 0) {
                        $this->db->table('piezas_tiradas_lineas')->insert([
                            'placa_id' => $placa['id'], 'pieza_id' => $piezaId, 'cantidad' => $placa[$campo], 'fallidas' => 0,
                        ]);
                    }
                }
            }
        }
    }
}
