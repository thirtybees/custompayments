<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    0RS <admin@prestalab.ru>
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2009-2017 PrestaLab.Ru
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * This module is based on the original `universalpay` module
 * which you can find on https://github.com/universalpay/universalpay
 *
 * Credits go to PrestaLab.Ru (http://www.prestalab.ru) for making the initial version
 */

namespace CustomPaymentsModule;

use Context;
use Db;
use DbQuery;
use ObjectModel;
use Tools;
use Validate;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class CustomPaymentMethod
 *
 * @sinc 1.0.0
 */
class CustomPaymentMethod extends ObjectModel
{
    const CART_BOTH = 0;
    const CART_VIRTUAL = 1;
    const CART_REAL = 2;

    // @codingStandardsIgnoreStart
    public static $definition = [
        'table' => 'custom_payment_method',
        'primary' => 'id_custom_payment_method',
        'multilang' => true,
        'multishop' => true,
        'fields' => [
            'active'              => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool',       'required' => true,                 'db_type' => 'TINYINT(1)',       'default' => '0'],
            'date_add'            => ['type' => self::TYPE_DATE,                   'validate' => 'isDateFormat',                                     'db_type' => 'DATETIME'                          ],
            'date_upd'            => ['type' => self::TYPE_DATE,                   'validate' => 'isDateFormat',                                     'db_type' => 'DATETIME'                          ],
            'id_order_state'      => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedId', 'required' => true,                 'db_type' => 'INT(11) UNSIGNED', 'default' => '0'],
            'id_cart_rule'        => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedId', 'required' => true,                 'db_type' => 'INT(11) UNSIGNED', 'default' => '0'],
            'cart_type'           => ['type' => self::TYPE_INT,                    'validate' => 'isInt',                                            'db_type' => 'TINYINT(4)',       'default' => '0'],
            'name'                => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'db_type' => 'VARCHAR(128)'                      ],
            'description'         => ['type' => self::TYPE_HTML,   'lang' => true, 'validate' => 'isString',                                         'db_type' => 'TEXT'                              ],
            'description_success' => ['type' => self::TYPE_HTML,   'lang' => true, 'validate' => 'isString',                                         'db_type' => 'TEXT'                              ],
            'description_short'   => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255, 'db_type' => 'VARCHAR(255)'                      ],
        ],
    ];
    public $id;
    public $active = 1;
    public $id_order_state = 3;
    public $id_cart_rule = 0;
    public $cart_type = 0;
    public $position;
    public $date_add;
    public $date_upd;
    public $name;
    public $description_short;
    public $description;
    public $description_success;
    public $image_dir;
    public $carrier_box;
    public $group_box;
    // @codingStandardsIgnoreEnd

    /**
     * CustomPaymentMethod constructor.
     *
     * @param null $id
     * @param null $idLang
     *
     * @since 1.0.0
     */
    public function __construct($id = null, $idLang = null)
    {
        $this->image_dir = _PS_IMG_DIR_.'pay/';

        return parent::__construct($id, $idLang);

    }

    /**
     * @param int      $idLang
     * @param bool     $active
     * @param int|bool $idCarrier
     * @param array    $groups
     *
     * @return array|false|null|\PDOStatement|resource
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @internal param bool|int $idCarrier
     * @since    1.0.0
     */
    public static function getCustomPaymentMethods($idLang, $active = true, $idCarrier = false, $groups = [])
    {
        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        if (!empty($groups)) {
            foreach ($groups as &$group) {
                $group = (int) $group;
            }
        }

        $sql = new DbQuery();
        $sql->select('cpm.*, cpml.`id_lang`, cpms.`id_shop`');
        if (\Group::isFeatureActive()) {
            $sql->select('cpmg.`id_group`');
        }
        $sql->select('cpml.`name`, cpml.`description`, cpml.`description_success`, cpml.`description_short`');
        if ($idCarrier) {
            $sql->select('cpmc.`id_carrier`');
        }
        $sql->from(bqSQL(static::$definition['table']), 'cpm');
        $sql->leftJoin(
            bqSQL(static::$definition['table']).'_lang',
            'cpml',
            'cpml.`'.bqSQL(static::$definition['primary']).
            '` = cpm.`'.bqSQL(static::$definition['primary']).'` AND cpml.`id_lang` = '.(int) $idLang
        );
        $sql->leftJoin(
            bqSQL(static::$definition['table']).'_shop',
            'cpms',
            'cpms.`'.bqSQL(static::$definition['primary'])
            .'` = cpm.`'.bqSQL(static::$definition['primary'])
            .'` AND cpms.`id_shop` = '.(int) Context::getContext()->shop->id
        );
        if ($idCarrier) {
            $sql->innerJoin(
                'custom_payment_method_carrier',
                'cpmc',
                'cpmc.`id_carrier` = '.(int) $idCarrier
            );
        }
        if (!empty($groups) && \Group::isFeatureActive()) {
            $sql->innerJoin(
                'custom_payment_method_group',
                'cpmg',
                'cpmg.`id_group` IN ('.implode($groups, ',').')'
            );
        }
        if ($active) {
            $sql->where('`active` = 1');
        }
        $sql->groupBy('cpm.`'.bqSQL(static::$definition['primary']).'`');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return $result;
    }

    /**
     * @param string $name
     *
     * @return false|null|string
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getIdByName($name)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select(bqSQL(static::$definition['primary']))
                ->from(bqSQL(static::$definition['table']).'_lang')
                ->where('`name` = \''.pSQL($name).'\'')
        );
    }

    /**
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function getCarriers()
    {
        $carriers = [];
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cpmc.`id_carrier`')
                ->from('custom_payment_method_carrier', 'cpmc')
                ->where('cpmc.`'.bqSQL(static::$definition['primary']).'` = '.(int) $this->id)
        );
        foreach ($result as $carrier) {
            $carriers[] = $carrier['id_carrier'];
        }

        return $carriers;
    }

    /**
     * Add Carrier
     *
     * @since 1.0.0
     */
    public function addCarriers($carriers)
    {
        foreach ($carriers as $carrier) {
            $row = ['id_custom_payment_method' => (int) $this->id, 'id_carrier' => (int) $carrier];
            try {
                Db::getInstance()->insert('custom_payment_method_carrier', $row);
            } catch (\PrestaShopException $e) {
                \Logger::addLog("Custom payments module  - could not add carriers: {$e->getMessage()}");
            }
        }
    }

    /**
     * Update Carrier
     *
     * @since 1.0.0
     *
     * @param int $oldCarrierId
     * @param int $newCarrierId
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function updateCarrier($oldCarrierId, $newCarrierId)
    {
        Db::getInstance()->update(
            'custom_payment_method_carrier',
            ['id_carrier' => (int) $newCarrierId],
            'id_carrier ='.(int) $oldCarrierId
        );
    }

    /**
     * Delete Carrier
     *
     * @since 1.0.0
     *
     * @param int|bool $idCarrier
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function deleteCarrier($idCarrier = false)
    {
        return Db::getInstance()->delete(
            'custom_payment_method_carrier',
            '`id_custom_payment_method` = '.(int) $this->id.($idCarrier ? 'AND `id_carrier` = '.(int) $idCarrier.' LIMIT 1' : '')
        );
    }

    /**
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function getGroups()
    {
        $carriers = [];
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cpmg.`id_group`')
                ->from('custom_payment_method_group', 'cpmg')
                ->where('cpmg.`id_custom_payment_method` = '.(int) $this->id)
        );
        foreach ($result as $carrier) {
            $carriers[] = $carrier['id_group'];
        }

        return $carriers;
    }

    /**
     * @param array $groups
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function addGroups($groups)
    {
        foreach ($groups as $group) {
            $row = ['id_custom_payment_method' => (int) $this->id, 'id_group' => (int) $group];
            Db::getInstance()->insert('custom_payment_method_group', $row);
        }
    }

    /**
     * @param bool $idGroup
     *
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function deleteGroup($idGroup = false)
    {
        return Db::getInstance()->delete(
            'custom_payment_method_group',
            '`id_custom_payment_method` = '.(int) $this->id.($idGroup ? 'AND `id_group` = '.(int) $idGroup.' LIMIT 1' : '')
        );
    }

    /**
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function delete()
    {
        return ($this->deleteCarrier()
            && $this->deleteGroup()
            && parent::delete()
        );
    }

    /**
     * @param array $list
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function updateCarriers($list)
    {
        $this->deleteCarrier();
        if ($list && !empty($list)) {
            $this->addCarriers($list);
        }
    }

    /**
     * @param array $list
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function updateGroups($list)
    {
        $this->deleteGroup();
        if ($list && !empty($list)) {
            $this->addGroups($list);
        }
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public function add($autodate = true, $nullValues = false)
    {
        $ret = parent::add($autodate, $nullValues);
        $this->updateCarriers($this->carrier_box);
        $this->updateGroups($this->group_box);

        return $ret;
    }

    /**
     * Create database
     *
     * @param string|null $className
     *
     * @return void
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function createDatabase($className = null)
    {
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method` (
               `id_custom_payment_method` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
               `active`                   TINYINT(1) UNSIGNED  NOT NULL,
               `date_add`                 DATETIME,
               `date_upd`                 DATETIME,
               `id_order_state`           INT(11) UNSIGNED NOT NULL,
               `id_cart_rule`             INT(11) UNSIGNED NOT NULL,
               `cart_type`                TINYINT(4) UNSIGNED NOT NULL,
               PRIMARY KEY (`id_custom_payment_method`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method_lang` (
               `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
               `name`                  VARCHAR(128),
               `description`           TEXT,
               `description_success`   TEXT,
               `description_short`     VARCHAR(255),
               `id_lang`               INT(11) UNSIGNED NOT NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        Db::getInstance()->execute('CREATE INDEX `id_custom_payment_method_lang_lang` ON `'._DB_PREFIX_.'custom_payment_method_lang` (`id_lang`)');
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method_shop` (
               `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
               `id_shop`                  INT(11) UNSIGNED  NOT NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        Db::getInstance()->execute('CREATE INDEX `id_custom_payment_method_shop_shop` ON `'._DB_PREFIX_.'custom_payment_method_shop` (`id_shop`)');
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method_carrier` (
               `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
               `id_carrier`               INT(11) UNSIGNED NOT NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        Db::getInstance()->execute('CREATE INDEX `custom_payment_method_carrier_carrier` ON `'._DB_PREFIX_.'custom_payment_method_carrier` (`id_carrier`)');
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method_group` (
               `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
               `id_group`                 INT(11) UNSIGNED NOT NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        Db::getInstance()->execute('CREATE INDEX `custom_payment_method_group_group` ON `'._DB_PREFIX_.'custom_payment_method_group` (`id_group`)');
    }

    /**
     * Drop databases
     *
     * @param string|null $className
     *
     * @return void
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function dropDatabase($className = null)
    {
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method`');
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_lang`');
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_carrier`');
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_group`');
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_shop`');
    }

    /**
     * Get local image path
     *
     * @param int    $id
     * @param string $type
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getImagePath($id, $type = 'original')
    {
        $baseLocation = _PS_IMG_DIR_.'pay/';

        if ($type === 'original') {
            if (file_exists("{$baseLocation}{$id}.png")) {
                return "{$baseLocation}{$id}.png";
            } else {
                return "{$baseLocation}{$id}.jpg";
            }
        }

        if (file_exists("{$baseLocation}{$id}-{$type}.png")) {
            return "{$baseLocation}{$id}-{$type}.png";
        } else {
            return "{$baseLocation}{$id}-{$type}.jpg";
        }
    }
}
