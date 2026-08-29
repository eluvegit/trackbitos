<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Revisión de malla de una versión (fase 54): si al abrirla en el laminador
 * tiene fallos que arreglar antes de imprimir — no manifold, normales
 * invertidas, agujeros... — o si ya se ha revisado y está limpia.
 *
 * NULL = nadie la ha mirado todavía. Es por VERSIÓN y no por STL: en el
 * índice se quiere el "¿está lista para el laminador?" de un vistazo, no el
 * detalle trozo a trozo. Las medidas de placa sí siguen siendo por STL
 * (piezas_version_stls.ancho_mm/fondo_mm) — ese dato es geométrico de cada
 * trozo; este es un juicio sobre la iteración entera.
 */
class AddRevisionMallaAPiezasVersiones extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_versiones', [
            'revision_malla' => [
                'type'       => 'ENUM',
                'constraint' => ['ok', 'fallos'],
                'null'       => true,
                'after'      => 'resultado',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_versiones', 'revision_malla');
    }
}
