<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;

final class LaravelMysqlWebhookTablesTest extends TestCase
{
    public function test(): void
    {
        $pdo = new \PDO('mysql:dbname=database;host=database', 'root', 'password');
        $connection = new MySqlConnection($pdo);
        $connectionResolver = $this->createMock(ConnectionResolverInterface::class);
        $connectionResolver->method('connection')->willReturn($connection);

        DB::swap($connectionResolver);

        $migration = include __DIR__.'/../../../src/Database/Migration/LaravelMysqlWebhookTables.php';

        $migration->up();

        self::assertNull($connection->selectOne('select * from Webhook'));
        self::assertNull($connection->selectOne('select * from WebhookAttribute'));
        self::assertNull($connection->selectOne('select * from WebhookRequiredAttribute'));
        self::assertNull($connection->selectOne('select * from WebhookWithoutAttribute'));

        $migration->down();

        try {
            $connection->selectOne('select * from Webhook');
        } catch (\Throwable $e) {
            self::assertInstanceOf(\PDOException::class, $e);
        }
        try {
            $connection->selectOne('select * from WebhookAttribute');
        } catch (\Throwable $e) {
            self::assertInstanceOf(\PDOException::class, $e);
        }
        try {
            $connection->selectOne('select * from WebhookRequiredAttribute');
        } catch (\Throwable $e) {
            self::assertInstanceOf(\PDOException::class, $e);
        }
        try {
            $connection->selectOne('select * from WebhookWithoutAttribute');
        } catch (\Throwable $e) {
            self::assertInstanceOf(\PDOException::class, $e);
        }

        $migration->up();

        self::assertNull($connection->selectOne('select * from Webhook'));
        self::assertNull($connection->selectOne('select * from WebhookAttribute'));
        self::assertNull($connection->selectOne('select * from WebhookRequiredAttribute'));
        self::assertNull($connection->selectOne('select * from WebhookWithoutAttribute'));

        $migration->down();
    }
}
