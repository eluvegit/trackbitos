<?php

namespace App\Commands;

use App\Controllers\Recordatorios;
use App\Models\RecordatorioModel;
use App\Services\TelegramService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Pensado para lanzarse a diario vía cron (ver hPanel de Hostinger ->
 * Avanzado -> Cron Jobs): recopila los recordatorios de hoy y de los
 * próximos 7 días y los manda por Telegram a la whitelist de chat_id
 * configurada en braintogram.chatIdsAutorizados.
 */
class BraintogramRecordatorios extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'braintogram:recordatorios';
    protected $description = 'Envía por Telegram el resumen de recordatorios de hoy y de esta semana.';

    public function run(array $params)
    {
        helper('recordatorio');

        $model         = new RecordatorioModel();
        $recordatorios = $model->findAll();

        $hoy    = [];
        $semana = [];

        foreach ($recordatorios as $r) {
            $periodo       = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
            $fechaEfectiva = recordatorio_fecha_efectiva($r['fecha_evento'], $periodo);
            $estado        = recordatorio_estado($fechaEfectiva);

            $r['dias']  = $estado['dias'];
            $r['texto'] = $estado['texto'];

            if ($estado['dias'] <= 0) {
                $hoy[] = $r;
            } elseif ($estado['dias'] <= 7) {
                $semana[] = $r;
            }
        }

        if (empty($hoy) && empty($semana)) {
            CLI::write('Sin recordatorios para hoy ni para esta semana. No se envía nada.', 'yellow');

            return;
        }

        usort($hoy, static fn (array $a, array $b) => $a['dias'] <=> $b['dias']);
        usort($semana, static fn (array $a, array $b) => $a['dias'] <=> $b['dias']);

        $telegram      = new TelegramService();
        $destinatarios = $telegram->chatIdsAutorizados();

        if (empty($destinatarios)) {
            CLI::error('braintogram.chatIdsAutorizados no está configurado: no hay a quién enviar.');

            return;
        }

        $texto = $this->formatearMensaje($hoy, $semana);

        foreach ($destinatarios as $chatId) {
            $telegram->enviarMensaje($chatId, $texto);
        }

        CLI::write(sprintf(
            'Enviado a %d chat(s): %d de hoy, %d de esta semana.',
            count($destinatarios),
            count($hoy),
            count($semana)
        ), 'green');
    }

    private function formatearMensaje(array $hoy, array $semana): string
    {
        $lineas = ['📅 *Recordatorios*', ''];

        $lineas[] = $hoy ? '*Hoy:*' : '*Hoy:* nada 🎉';
        foreach ($hoy as $r) {
            $lineas[] = '• ' . $this->lineaRecordatorio($r);
        }

        $lineas[] = '';
        $lineas[] = $semana ? '*Esta semana:*' : '*Esta semana:* nada';
        foreach ($semana as $r) {
            $lineas[] = '• ' . $this->lineaRecordatorio($r);
        }

        return implode("\n", $lineas);
    }

    private function lineaRecordatorio(array $r): string
    {
        $label = Recordatorios::CATEGORIAS[$r['categoria']][0] ?? 'Otro';

        return "{$r['titulo']} ({$label}) — {$r['texto']}";
    }
}
