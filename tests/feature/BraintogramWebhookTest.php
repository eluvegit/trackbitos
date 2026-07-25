<?php

use App\Models\BraintogramMensajeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Verifica la ingesta del webhook de Braintogram sin depender de una llamada
 * HTTP real ni de un bot de Telegram: invoca el controlador a través del
 * enrutador de CodeIgniter y comprueba lo que queda guardado en BD.
 *
 * No usa DatabaseTestTrait porque su migrate() intenta correr TODAS las
 * migraciones de la app (incluidas las que hacen ALTER sobre tablas legacy
 * que solo existen en la MySQL de producción, ver CLAUDE.md), así que aquí
 * se crea/destruye únicamente la tabla de este módulo contra la conexión de
 * pruebas (SQLite, seleccionada automáticamente por ENVIRONMENT=testing).
 *
 * @internal
 */
final class BraintogramWebhookTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private static function db()
    {
        return \Config\Database::connect();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $forge = \Config\Database::forge(self::db());
        $forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'update_id'      => ['type' => 'BIGINT', 'null' => true],
            'tipo'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'chat_id'        => ['type' => 'BIGINT', 'null' => true],
            'chat_type'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'from_id'        => ['type' => 'BIGINT', 'null' => true],
            'from_username'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'from_nombre'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'texto'          => ['type' => 'TEXT', 'null' => true],
            'fecha_telegram' => ['type' => 'DATETIME', 'null' => true],
            'ip_origen'      => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'secret_valido'  => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'chat_autorizado' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'rate_limited'    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'raw_json'       => ['type' => 'LONGTEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('braintogram_mensajes', true);
    }

    public static function tearDownAfterClass(): void
    {
        \Config\Database::forge(self::db())->dropTable('braintogram_mensajes', true);
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void
    {
        self::db()->table('braintogram_mensajes')->truncate();
        parent::tearDown();
    }

    public function testMensajeDeTextoSeParseaYGuarda(): void
    {
        $payload = [
            'update_id' => 123456789,
            'message'   => [
                'message_id' => 1,
                'from'       => [
                    'id'         => 987654321,
                    'is_bot'     => false,
                    'first_name' => 'Eluve',
                    'username'   => 'eluve_tester',
                ],
                'chat' => [
                    'id'   => 987654321,
                    'type' => 'private',
                ],
                'date' => 1753412345,
                'text' => 'Prueba de ingesta',
            ],
        ];

        $result = $this->withBodyFormat('json')->post('braintogram/webhook', $payload);

        $result->assertStatus(200);

        $model = new BraintogramMensajeModel();
        $fila  = $model->orderBy('id', 'DESC')->first();

        $this->assertNotNull($fila);
        $this->assertSame(123456789, (int) $fila['update_id']);
        $this->assertSame('message', $fila['tipo']);
        $this->assertSame(987654321, (int) $fila['chat_id']);
        $this->assertSame('private', $fila['chat_type']);
        $this->assertSame('eluve_tester', $fila['from_username']);
        $this->assertSame('Prueba de ingesta', $fila['texto']);
        $this->assertSame(date('Y-m-d H:i:s', 1753412345), $fila['fecha_telegram']);
        $this->assertNull($fila['secret_valido']);
        $this->assertJson($fila['raw_json']);
    }

    public function testJsonInvalidoSeGuardaComoInvalido(): void
    {
        $result = $this->withBody('esto-no-es-json')->call('POST', 'braintogram/webhook');

        $result->assertStatus(200);

        $model = new BraintogramMensajeModel();
        $fila  = $model->orderBy('id', 'DESC')->first();

        $this->assertNotNull($fila);
        $this->assertSame('invalido', $fila['tipo']);
        $this->assertSame('esto-no-es-json', $fila['raw_json']);
    }

    public function testSecretIncorrectoDevuelve403PeroGuardaElIntento(): void
    {
        putenv('braintogram.webhookSecret=el-secreto-correcto');
        $_ENV['braintogram.webhookSecret']    = 'el-secreto-correcto';
        $_SERVER['braintogram.webhookSecret'] = 'el-secreto-correcto';

        try {
            $result = $this->withBodyFormat('json')
                ->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'un-secreto-falso'])
                ->post('braintogram/webhook', ['update_id' => 1, 'message' => ['chat' => ['id' => 1, 'type' => 'private'], 'date' => 1753412345, 'text' => 'x']]);

            $result->assertStatus(403);

            $model = new BraintogramMensajeModel();
            $fila  = $model->orderBy('id', 'DESC')->first();

            $this->assertNotNull($fila);
            $this->assertSame(0, (int) $fila['secret_valido']);
        } finally {
            putenv('braintogram.webhookSecret');
            unset($_ENV['braintogram.webhookSecret'], $_SERVER['braintogram.webhookSecret']);
        }
    }

    public function testChatNoAutorizadoSeCortaSinLlegarAlRateLimit(): void
    {
        putenv('braintogram.chatIdsAutorizados=111111111,222222222');
        $_ENV['braintogram.chatIdsAutorizados']    = '111111111,222222222';
        $_SERVER['braintogram.chatIdsAutorizados'] = '111111111,222222222';

        try {
            $result = $this->withBodyFormat('json')->post('braintogram/webhook', [
                'update_id' => 2,
                'message'   => ['chat' => ['id' => 999999999, 'type' => 'private'], 'date' => 1753412345, 'text' => 'intruso'],
            ]);

            // 200 para que Telegram no reintente, aunque no se procese.
            $result->assertStatus(200);

            $model = new BraintogramMensajeModel();
            $fila  = $model->orderBy('id', 'DESC')->first();

            $this->assertNotNull($fila);
            $this->assertSame(0, (int) $fila['chat_autorizado']);
            // No se llega a evaluar el rate limit si el chat ya está rechazado.
            $this->assertNull($fila['rate_limited']);
        } finally {
            putenv('braintogram.chatIdsAutorizados');
            unset($_ENV['braintogram.chatIdsAutorizados'], $_SERVER['braintogram.chatIdsAutorizados']);
        }
    }

    public function testRateLimitBloqueaTrasSuperarElMaximo(): void
    {
        $chatId = 424242424;
        $key    = 'braintogram_rate_' . $chatId;
        $cache  = \Config\Services::cache();
        // Ya al límite (20 en la ventana), la siguiente debe bloquear.
        $cache->save($key, 20, 60);

        try {
            $result = $this->withBodyFormat('json')->post('braintogram/webhook', [
                'update_id' => 3,
                'message'   => ['chat' => ['id' => $chatId, 'type' => 'private'], 'date' => 1753412345, 'text' => 'spam'],
            ]);

            $result->assertStatus(200);

            $model = new BraintogramMensajeModel();
            $fila  = $model->orderBy('id', 'DESC')->first();

            $this->assertNotNull($fila);
            $this->assertNull($fila['chat_autorizado']); // sin whitelist configurada en este test
            $this->assertSame(1, (int) $fila['rate_limited']);
        } finally {
            $cache->delete($key);
        }
    }
}
