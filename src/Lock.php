<?php declare(strict_types = 1);
namespace MondidoPayments;

use Db;
use PrestaShopDatabaseException;

class Lock
{
    const DB_TABLE = 'mondidopayments_lock';

    public static function install()
    {
        $table = _DB_PREFIX_ . self::DB_TABLE;
        return Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `$table` (
                `id` INT(10) UNSIGNED NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`, `type`)
            ) DEFAULT CHARSET=utf8;
        ");
    }

    public static function aquire(string $data_type, int $id)
    {
        Db::getInstance()->delete(self::DB_TABLE, 'created < (NOW() - INTERVAL 10 MINUTE)');
        try {
            Db::getInstance()->insert(self::DB_TABLE, ['type' => $data_type, 'id' => $id], false, false);
            return true;
        } catch (PrestaShopDatabaseException $error) {
            if (strpos($error->getMessage(), 'Duplicate entry') !== false) {
                return false;
            }
            throw $error;
        }
    }

    public static function drop(string $data_type, int $id)
    {
        $instance = Db::getInstance();
        $data_type = $instance->escape($data_type);
        $id = $instance->escape((string) $id);
        Db::getInstance()->delete(self::DB_TABLE, "`type` = '$data_type' AND `id` = $id");
    }
}
