<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Params;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Priority;
use SimpleAsFuck\ApiToolkit\Model\Webhook\Webhook;
use SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelMysqlRepository;

/**
 * @covers \SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelMysqlRepository
 */
final class LaravelMysqlRepositoryTest extends TestCase
{
    private static ConnectionResolver $connectionResolver;

    private Repository $config;

    public static function setUpBeforeClass(): void
    {
        $pdo = new \PDO('mysql:dbname=database;host=database', 'root', 'password');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        $pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);
        $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        self::$connectionResolver = new ConnectionResolver();
        self::$connectionResolver->setDefaultConnection('default');
        self::$connectionResolver->addConnection('default', new MySqlConnection($pdo));
        DB::swap(self::$connectionResolver);

        $migration = include __DIR__.'/../../../src/Database/Migration/LaravelMysqlWebhookTables.php';
        $migration->up();
    }

    public function setUp(): void
    {
        self::$connectionResolver->connection('default')->beginTransaction();

        $this->config = $this->createMock(Repository::class);
        $this->config->method('get')->willReturn('default');
    }

    public function tearDown(): void
    {
        self::$connectionResolver->connection('default')->rollBack();
    }

    public static function tearDownAfterClass(): void
    {
        $migration = include __DIR__.'/../../../src/Database/Migration/LaravelMysqlWebhookTables.php';
        $migration->down();
    }

    /**
     * @dataProvider dataProviderLoadForDispatching
     *
     * @param array<non-empty-string> $expectedUrls
     * @param non-empty-string $type
     * @param array<non-empty-string, non-empty-string> $attributes
     */
    public function testLoadForDispatching(array $expectedUrls, string $type, array $attributes): void
    {
        $repository = new LaravelMysqlRepository(
            self::$connectionResolver,
            $this->config
        );

        $repository->save('test', new Params('http://test/with/attributes', Priority::NORMAL, [
            'test_key' => 'test_value',
        ]));
        $repository->save('test', new Params('http://test/with/two/attributes', Priority::NORMAL, [
            'test_key' => 'test_value',
            'second_key' => 'second_value',
        ]));
        $repository->save('test', new Params('http://test/without/attributes', Priority::HIGH, []));
        $repository->save('another_type', new Params('http://another/test/with/attributes', Priority::NORMAL, [
            'test_key' => 'test_value',
        ]));
        $repository->save('another_type', new Params('http://another/test/without/attributes', Priority::NORMAL, []));

        $connection = self::$connectionResolver->connection('default');

        $expectedWebhooks = array_map(function (string $url) use ($connection, $attributes): Webhook {
            /** @var \stdClass $dbWebhook */
            $dbWebhook = $connection->selectOne('select * from Webhook where listeningUrl = ?', [$url]);
            /** @var non-empty-string $webhookId */
            $webhookId = (string) $dbWebhook->id;
            return new Webhook(
                $webhookId,
                $dbWebhook->type,
                new Params(
                    $dbWebhook->listeningUrl,
                    $dbWebhook->priority,
                    $attributes
                )
            );
        }, $expectedUrls);

        $repository = new LaravelMysqlRepository(
            self::$connectionResolver,
            $this->config
        );

        $webhooks = $repository->loadForDispatching($type, $attributes);

        self::assertEquals(new \ArrayIterator($expectedWebhooks), $webhooks);
    }

    /**
     * @return non-empty-array<array{array<non-empty-string>, non-empty-string, array<string, string>}>
     */
    public static function dataProviderLoadForDispatching(): array
    {
        return [
            [
                ['http://test/without/attributes', 'http://test/with/attributes'],
                'test', ['test_key' => 'test_value', 'another_key' => 'another_value'],
            ],
            [
                ['http://test/without/attributes', 'http://test/with/two/attributes', 'http://test/with/attributes'],
                'test', ['second_key' => 'second_value', 'test_key' => 'test_value'],
            ],
            [
                ['http://test/without/attributes'],
                'test', ['second_key' => 'second_value'],
            ],
            [
                ['http://test/without/attributes'],
                'test', [],
            ],
            [
                ['http://test/without/attributes'],
                'test', ['test_key' => 'value_not_math', 'second_key' => 'second_value'],
            ],
            [
                ['http://another/test/with/attributes', 'http://another/test/without/attributes'],
                'another_type', ['test_key' => 'test_value'],
            ],
            [
                ['http://another/test/without/attributes'],
                'another_type', ['key_not_math' => 'test_value'],
            ],
            [
                [],
                'type_not_found', ['test_key' => 'test_value'],
            ],
            [
                [],
                'type_not_found', [],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderSaveAndDelete
     *
     * @param non-empty-string $type
     */
    public function testSave(string $type, Params $params): void
    {
        $repository = new LaravelMysqlRepository(
            self::$connectionResolver,
            $this->config
        );

        /** @var Webhook $webhook */
        $webhook = $repository->save($type, $params);
        self::assertNotNull($webhook);
        self::assertSame($type, $webhook->type());
        self::assertEquals($params, $webhook->params());

        $connection = self::$connectionResolver->connection('default');

        /** @var \stdClass $dbWebhook */
        $dbWebhook = $connection->selectOne('select * from Webhook where id = ?', [$webhook->id()]);
        self::assertSame($webhook->id(), (string) $dbWebhook->id);
        self::assertSame($type, $dbWebhook->type);
        self::assertSame($params->listeningUrl(), $dbWebhook->listeningUrl);
        self::assertSame($params->priority(), $dbWebhook->priority);

        $dbRequiredAttributeMaps = $connection->select('select * from WebhookRequiredAttribute where webhookId = ?', [$webhook->id()]);
        /** @var \stdClass $dbWithoutAttribute */
        $dbWithoutAttribute = $connection->selectOne('select * from WebhookWithoutAttribute where webhookId = ?', [$webhook->id()]);

        if (count($params->attributes()) === 0) {
            self::assertCount(0, $dbRequiredAttributeMaps);
            self::assertSame($webhook->id(), (string) $dbWithoutAttribute->webhookId);
        } else {
            self::assertCount(count($params->attributes()), $dbRequiredAttributeMaps);
            foreach ($dbRequiredAttributeMaps as $dbRequiredAttributeMap) {
                self::assertSame($webhook->id(), (string) $dbRequiredAttributeMap->webhookId);

                /** @var \stdClass $dbRequiredAttribute */
                $dbRequiredAttribute = $connection->selectOne('select * from WebhookAttribute where id = ?', [$dbRequiredAttributeMap->webhookAttributeId]);
                self::assertSame($params->attributes()[$dbRequiredAttribute->key], $dbRequiredAttribute->value);
            }
        }

        $webhook = $repository->save($type, $params);
        self::assertNull($webhook);
    }

    /**
     * @dataProvider dataProviderSaveAndDelete
     *
     * @param non-empty-string $type
     */
    public function testDelete(string $type, Params $params): void
    {
        $connection = self::$connectionResolver->connection('default');

        $repository = new LaravelMysqlRepository(
            self::$connectionResolver,
            $this->config
        );

        /** @var Webhook $webhook */
        $webhook = $repository->save($type, $params);
        $repository->delete($webhook->id());

        self::assertNull($connection->selectOne('select * from Webhook where id = ?', [$webhook->id()]));
        self::assertNull($connection->selectOne('select * from WebhookRequiredAttribute where webhookId = ?', [$webhook->id()]));
        self::assertNull($connection->selectOne('select * from WebhookWithoutAttribute where webhookId = ?', [$webhook->id()]));
    }

    /**
     * @return non-empty-array<array{non-empty-string, Params}>
     */
    public static function dataProviderSaveAndDelete(): array
    {
        return [
            ['test_with_attributes', new Params('http://test/url', Priority::NORMAL, ['test_key' => 'test_value'])],
            ['test_with_two_attributes', new Params(
                'http://test/url',
                Priority::NORMAL,
                ['test_key' => 'test_value', 'second_key' => 'second_value'],
            )],
            ['test_without_attributes', new Params('http://test/url', Priority::HIGH, [])],
        ];
    }
}
