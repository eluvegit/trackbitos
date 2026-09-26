<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Vocabulario abierto de Silo (categoría/evento/lugar/persona/tema) en una
 * única tabla, diferenciado por `tipo`. El alta get-or-create vive en
 * SiloService::getOrCreateVocabulario(), no aquí — este modelo es solo
 * acceso a datos.
 */
class SiloVocabularioModel extends Model
{
    protected $table         = 'silo_vocabulario';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['tipo', 'nombre', 'descripcion', 'slug'];

    protected $validationRules = [
        'tipo'   => 'required|in_list[categoria,evento,lugar,persona,tema]',
        'nombre' => 'required|max_length[150]',
        'slug'   => 'required|max_length[150]',
    ];

    public function porTipo(string $tipo): array
    {
        return $this->where('tipo', $tipo)->orderBy('nombre', 'ASC')->findAll();
    }

    /**
     * Igual que porTipo() pero con cuántas piezas usa cada término ahora
     * mismo (`usos`), para que /silo/vocabulario pueda marcar "sin uso" los
     * que se quedaron huérfanos (reclasificación, o piezas borradas por
     * Silo\Agente::escaneo() al desaparecer su carpeta) — nunca se borran
     * solos, el borrado es manual (Web::borrarVocabulario()).
     */
    public function porTipoConUsos(string $tipo): array
    {
        $items = $this->porTipo($tipo);
        if ($items === []) {
            return $items;
        }

        $usos = $this->conteoUsos(array_column($items, 'id'));

        foreach ($items as &$item) {
            $item['usos'] = $usos[(int) $item['id']] ?? 0;
        }

        return $items;
    }

    /**
     * Categorías (tipo 'categoria') que tiene al menos una pieza — para el
     * filtro de /silo, que no debe ofrecer categorías huérfanas.
     */
    public function categoriasEnUso(): array
    {
        return $this->select('silo_vocabulario.*')
            ->join('silo_piezas', 'silo_piezas.categoria_id = silo_vocabulario.id')
            ->where('silo_vocabulario.tipo', 'categoria')
            ->groupBy('silo_vocabulario.id')
            ->orderBy('silo_vocabulario.nombre', 'ASC')
            ->findAll();
    }

    /**
     * Cuántas piezas usa cada id de vocabulario dado, sumando las dos formas
     * en que se referencia (`silo_piezas.categoria_id` — cardinalidad única
     * — y `silo_pieza_atributo` — el resto de tipos). Un id de vocabulario
     * solo puede aparecer en una de las dos fuentes según su `tipo`, así que
     * sumar ambas no duplica nada.
     *
     * @param array<int, int|string> $ids
     * @return array<int, int> vocabulario_id => nº de piezas
     */
    public function conteoUsos(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $usos = [];

        $porCategoria = $this->db->table('silo_piezas')
            ->select('categoria_id AS vocabulario_id, COUNT(*) AS n')
            ->whereIn('categoria_id', $ids)
            ->groupBy('categoria_id')
            ->get()->getResultArray();
        foreach ($porCategoria as $fila) {
            $usos[(int) $fila['vocabulario_id']] = (int) $fila['n'];
        }

        $porAtributo = $this->db->table('silo_pieza_atributo')
            ->select('vocabulario_id, COUNT(*) AS n')
            ->whereIn('vocabulario_id', $ids)
            ->groupBy('vocabulario_id')
            ->get()->getResultArray();
        foreach ($porAtributo as $fila) {
            $usos[(int) $fila['vocabulario_id']] = (int) $fila['n'];
        }

        return $usos;
    }
}
