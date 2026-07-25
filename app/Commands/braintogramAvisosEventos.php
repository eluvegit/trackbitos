<?php

namespace App\Commands;

use App\Services\GoogleCalendarService;
use App\Services\TelegramService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Pensado para lanzarse cada 10-15 min vía cron (ver hPanel -> Avanzado ->
 * Cron Jobs): mira qué eventos empiezan dentro de los próximos 30 minutos y
 * avisa por Telegram. Cada evento se avisa una sola vez, aunque el cron
 * corra varias veces antes de que empiece, gracias a una marca en caché.
 */
class BraintogramAvisosEventos extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'braintogram:avisos-eventos';
    protected $description = 'Avisa por Telegram de los eventos de Google Calendar que empiezan en breve.';

    private const MINUTOS_ANTELACION = 30;
    private const CACHE_TTL          = 3 * 3600; // 3h: de sobra para no repetir el aviso en ejecuciones siguientes

    public function run(array $params)
    {
        $ahora  = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid'));
        $limite = $ahora->modify('+' . self::MINUTOS_ANTELACION . ' minutes');

        $eventos = (new GoogleCalendarService())->listarEventos($ahora, $limite);

        if ($eventos === null) {
            CLI::error('No se pudo consultar el calendario.');

            return;
        }

        if (empty($eventos)) {
            CLI::write('Nada que avisar en los próximos ' . self::MINUTOS_ANTELACION . ' min.', 'yellow');

            return;
        }

        $telegram      = new TelegramService();
        $destinatarios = $telegram->chatIdsAutorizados();

        if (empty($destinatarios)) {
            CLI::error('braintogram.chatIdsAutorizados no está configurado: no hay a quién avisar.');

            return;
        }

        $cache    = \Config\Services::cache();
        $avisados = 0;

        foreach ($eventos as $evento) {
            if ($evento['todo_el_dia']) {
                continue; // los de "todo el día" no tienen una hora concreta que avisar
            }

            $clave = 'braintogram_aviso_evento_' . $evento['id'];
            if ($cache->get($clave) !== null) {
                continue; // ya avisado en una ejecución anterior del cron
            }

            $hora  = (new \DateTimeImmutable($evento['inicio']))->format('H:i');
            $texto = "🔔 En breve: {$evento['titulo']} a las {$hora}";

            foreach ($destinatarios as $chatId) {
                $telegram->enviarMensaje($chatId, $texto);
            }

            $cache->save($clave, true, self::CACHE_TTL);
            $avisados++;
        }

        CLI::write("Avisos enviados: {$avisados}.", 'green');
    }
}
