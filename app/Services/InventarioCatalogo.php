<?php

namespace App\Services;

use App\Models\PiezaCategoriaModel;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaHuecoModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;

/**
 * Datos de solo lectura para la sección Inventario (usuarios del grupo
 * 'inventario'). Solo entra lo que se publica en sterclicks: la pieza, su
 * familia y su categoría con el interruptor encendido (misma cascada que
 * ExistenciasController::index y SterclicksApi::catalogo). Y de eso, solo
 * lo que tiene algo sólido: definitiva, para imprimir o modificándose (ver
 * esSolida()); fuera las sin empezar, las descartadas y las de la nevera.
 */
class InventarioCatalogo
{
    private ?array $piezas = null;

    /**
     * @return list<array{id:int, nombre:string, categoria_id:?int, stock:int, hueco_id:?int, codigo:?string, estuche_id:?int, img:?string, img_grande:?string}>
     */
    public function piezas(): array
    {
        if ($this->piezas !== null) {
            return $this->piezas;
        }

        $categoriasVisibles = [];
        foreach ((new PiezaCategoriaModel())->findAll() as $c) {
            $categoriasVisibles[(int) $c['id']] = !empty($c['visible_sterclicks']);
        }

        $familias = [];
        foreach ((new PiezaFamiliaModel())->where('borrado_en', null)->findAll() as $f) {
            $cat = $f['categoria_id'] !== null ? (int) $f['categoria_id'] : null;
            if (empty($f['visible_sterclicks']) || ($cat !== null && !($categoriasVisibles[$cat] ?? false))) {
                continue;
            }
            $familias[(int) $f['id']] = $f;
        }

        $variantes = [];
        foreach ((new PiezaVarianteModel())->where('borrado_en', null)->where('congelado_en', null)->where('visible_sterclicks', 1)->findAll() as $v) {
            if (isset($familias[(int) $v['familia_id']])) {
                $variantes[(int) $v['id']] = $v;
            }
        }
        $variantes = $this->soloSolidas($variantes);
        if ($variantes === []) {
            return $this->piezas = [];
        }

        $ids    = array_keys($variantes);
        $stock  = (new PiezaInventario())->stockPorVariante($ids);
        $huecos = $this->codigosDeHueco();
        $fotos  = $this->fotos($ids);

        $piezas = [];
        foreach ($variantes as $id => $v) {
            $familia = $familias[(int) $v['familia_id']];
            $huecoId = $v['hueco_predeterminado_id'] !== null ? (int) $v['hueco_predeterminado_id'] : null;
            $piezas[] = [
                'id'           => $id,
                'nombre'       => $this->nombreVisible($familia['nombre'] . ' ' . $v['nombre']),
                'categoria_id' => $familia['categoria_id'] !== null ? (int) $familia['categoria_id'] : null,
                'stock'        => max(0, $stock[$id] ?? 0),
                'hueco_id'     => $huecoId,
                'codigo'       => $huecos[$huecoId]['codigo'] ?? null,
                'estuche_id'   => $huecos[$huecoId]['estuche_id'] ?? null,
                'img'          => $fotos[$id]['t'] ?? null,
                'img_grande'   => $fotos[$id]['v'] ?? null,
            ];
        }
        usort($piezas, static fn (array $a, array $b) => strnatcasecmp($a['nombre'], $b['nombre']));

        return $this->piezas = $piezas;
    }

    /** Sin la palabra "base": es un nombre interno de edición de piezas. */
    private function nombreVisible(string $nombre): string
    {
        return preg_replace('/\s+base$/iu', '', trim($nombre));
    }

    /**
     * Quita las que no tienen nada sólido. Se queda con: versión validada
     * (definitiva), última versión en borrador o impresa (para imprimir /
     * sin validar) o trabajo en curso (modificándose). Mismo criterio que
     * Web::rangoMadurezVariante(): sin versiones ni trabajo = sin empezar;
     * última descartada sin validada = no sirve.
     */
    private function soloSolidas(array $variantes): array
    {
        if ($variantes === []) {
            return [];
        }

        $validada = [];
        $ultima   = [];
        $versiones = (new PiezaVersionModel())->select('variante_id, numero, estado')
            ->whereIn('variante_id', array_keys($variantes))->orderBy('numero')->findAll();
        foreach ($versiones as $ver) {
            $vid = (int) $ver['variante_id'];
            $ultima[$vid] = $ver['estado'];
            if ($ver['estado'] === 'validada') {
                $validada[$vid] = true;
            }
        }

        $sync = new PiezaSyncService();
        foreach ($variantes as $id => $v) {
            if (isset($validada[$id]) || in_array($ultima[$id] ?? null, ['borrador', 'impresa'], true)) {
                continue;
            }
            $estado = $sync->estadoDeSincronizacion($id);
            if ($estado['sesion_abierta'] === null && $estado['ultima_subida'] === null) {
                unset($variantes[$id]);
            }
        }

        return $variantes;
    }

    /**
     * Categorías visibles que tienen alguna pieza, con cuántas y una foto
     * de muestra (la primera pieza con foto).
     *
     * @return list<array{id:int, nombre:string, total:int, img:?string}>
     */
    public function categorias(): array
    {
        $porCategoria = [];
        foreach ($this->piezas() as $p) {
            if ($p['categoria_id'] !== null) {
                $porCategoria[$p['categoria_id']][] = $p;
            }
        }

        $salida = [];
        foreach ((new PiezaCategoriaModel())->ordenadas() as $c) {
            $piezas = $porCategoria[(int) $c['id']] ?? [];
            if ($piezas === []) {
                continue;
            }
            $conFoto  = array_values(array_filter($piezas, static fn (array $p) => $p['img'] !== null));
            $salida[] = [
                'id'     => (int) $c['id'],
                'nombre' => $c['nombre'],
                'total'  => count($piezas),
                'img'    => $conFoto[0]['img_grande'] ?? $conFoto[0]['img'] ?? null,
            ];
        }

        return $salida;
    }

    /** @return list<array{estuche:array, huecos:int, piezas:int}> */
    public function estuches(): array
    {
        $porHueco = $this->piezasPorHueco();
        $huecos   = new PiezaHuecoModel();

        $salida = [];
        foreach ((new PiezaEstucheModel())->ordenados() as $e) {
            $lista  = $huecos->deEstuche((int) $e['id']);
            $piezas = 0;
            foreach ($lista as $h) {
                $piezas += count($porHueco[(int) $h['id']] ?? []);
            }
            $salida[] = ['estuche' => $e, 'huecos' => count($lista), 'piezas' => $piezas];
        }

        return $salida;
    }

    /** @return list<array{hueco:array, codigo:string, piezas:list<array>}> */
    public function huecosDeEstuche(array $estuche): array
    {
        $porHueco = $this->piezasPorHueco();

        $salida = [];
        foreach ((new PiezaHuecoModel())->deEstuche((int) $estuche['id']) as $h) {
            $salida[] = [
                'hueco'  => $h,
                'codigo' => PiezaHuecoModel::codigoCompleto($estuche, $h),
                'piezas' => $porHueco[(int) $h['id']] ?? [],
            ];
        }

        return $salida;
    }

    /** @return array<int, list<array>> huecoId => piezas */
    private function piezasPorHueco(): array
    {
        $porHueco = [];
        foreach ($this->piezas() as $p) {
            if ($p['hueco_id'] !== null) {
                $porHueco[$p['hueco_id']][] = $p;
            }
        }

        return $porHueco;
    }

    /** @return array<int, array{codigo:string, estuche_id:int}> huecoId => "E1H2" y su estuche */
    private function codigosDeHueco(): array
    {
        $huecos = new PiezaHuecoModel();
        $mapa   = [];
        foreach ((new PiezaEstucheModel())->ordenados() as $e) {
            foreach ($huecos->deEstuche((int) $e['id']) as $h) {
                $mapa[(int) $h['id']] = ['codigo' => PiezaHuecoModel::codigoCompleto($e, $h), 'estuche_id' => (int) $e['id']];
            }
        }

        return $mapa;
    }

    /**
     * La foto más reciente de cada pieza, en miniatura y en grande. Si la
     * copia pública no existe, se sirve por inventario/imagen (las rutas
     * de imagen de piezas/ son solo de admin).
     *
     * @return array<int, array{t:string, v:string}>
     */
    private function fotos(array $ids): array
    {
        $publicas = new PiezaImagenesPublicas();
        $fotos    = [];
        foreach ((new PiezaRenderModel())->whereIn('variante_id', $ids)->orderBy('subida_en', 'DESC')->findAll() as $r) {
            $vid = (int) $r['variante_id'];
            if (isset($fotos[$vid]) || (empty($r['ruta_imagen']) && empty($r['hash_imagen']))) {
                continue;
            }
            $respaldo    = inventario_url('imagen/' . (int) $r['id']);
            $fotos[$vid] = [
                't' => $publicas->url($r['hash_imagen'] ?? null, PiezaImagenesPublicas::MINIATURA) ?? $respaldo,
                'v' => $publicas->url($r['hash_imagen'] ?? null, PiezaImagenesPublicas::VISTA) ?? $respaldo,
            ];
        }

        return $fotos;
    }
}
