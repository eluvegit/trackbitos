<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pasa las categorías de Silo ya guardadas a estilo Título (petición
 * 2026-09-27): el get-or-create casa por slug sin mirar mayúsculas, así que
 * una categoría dada de alta en minúsculas se quedaba así aunque luego se
 * escribiera con mayúsculas en la carpeta. A partir de ahora
 * SiloService::nombreVocabulario() las normaliza al guardar; esto arregla
 * las que ya había. Solo cambia `nombre` — el slug (y con él los cubos de
 * Copia 3) no depende de las mayúsculas.
 */
class CategoriasSiloEnTitulo extends Migration
{
    public function up()
    {
        $filas = $this->db->table('silo_vocabulario')->select('id, nombre')->where('tipo', 'categoria')->get()->getResultArray();

        foreach ($filas as $f) {
            $titulo = mb_convert_case(trim((string) preg_replace('/\s+/u', ' ', $f['nombre'])), MB_CASE_TITLE, 'UTF-8');
            if ($titulo !== $f['nombre']) {
                $this->db->table('silo_vocabulario')->where('id', $f['id'])->update(['nombre' => $titulo]);
            }
        }
    }

    public function down()
    {
        // Sin vuelta atrás: la grafía anterior no se guarda.
    }
}
