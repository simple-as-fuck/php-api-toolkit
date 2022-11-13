<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::connection($this->connection)->statement('
            CREATE TABLE `Webhook` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `type` VARCHAR(255) NOT NULL,
                `listeningUrl` VARCHAR(2000) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
                `priority` SMALLINT(6) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `webhook_unique_idx` (`type` ASC, `listeningUrl` ASC)
            )
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci
        ');

        DB::connection($this->connection)->statement('
            CREATE TABLE `WebhookAttribute` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `key` VARCHAR(255) NOT NULL,
                `value` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `value_idx` (`value` ASC)
            )
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci
        ');

        DB::connection($this->connection)->statement('
            CREATE TABLE `WebhookRequiredAttribute` (
                `webhookId` BIGINT UNSIGNED NOT NULL,
                `webhookAttributeId` BIGINT UNSIGNED NOT NULL,
                INDEX `fk_WebhookWithAttribute_WebhookAttribute_idx` (`webhookAttributeId` ASC),
                INDEX `fk_WebhookWithAttribute_Webhook1_idx` (`webhookId` ASC),
                CONSTRAINT `fk_WebhookWithAttribute_WebhookAttribute`
                    FOREIGN KEY (`webhookAttributeId`)
                    REFERENCES `WebhookAttribute` (`id`),
                CONSTRAINT `fk_WebhookWithAttribute_Webhook1`
                    FOREIGN KEY (`webhookId`)
                    REFERENCES `Webhook` (`id`)
            )
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci
        ');

        DB::connection($this->connection)->statement('
            CREATE TABLE `WebhookWithoutAttribute` (
                `webhookId` BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (`webhookId`),
                CONSTRAINT `fk_WebhookWithoutAttribute_Webhook1`
                    FOREIGN KEY (`webhookId`)
                    REFERENCES `Webhook` (`id`)
            )
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci
        ');
    }

    public function down(): void
    {
        DB::connection($this->connection)->statement('DROP TABLE WebhookWithoutAttribute');
        DB::connection($this->connection)->statement('DROP TABLE WebhookRequiredAttribute');
        DB::connection($this->connection)->statement('DROP TABLE WebhookAttribute');
        DB::connection($this->connection)->statement('DROP TABLE Webhook');
    }
};
