<?php
/**
 * Copyright (C) 2017 thirty bees
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
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace CustomPaymentsModule;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class CustomPaymentMethod
 *
 * @sinc 1.0.0
 */
class CustomPaymentMethod extends \ObjectModel
{
    const CART_BOTH = 0;
    const CART_VIRTUAL = 1;
    const CART_REAL = 2;

    // @codingStandardsIgnoreStart
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

    public static $definition = [
        'table' => 'custom_payment_method',
        'primary' => 'id_custom_payment_method',
        'multilang' => true,
        'fields' => [
            'active'              => ['type' => self::TYPE_BOOL,                                   'validate' => 'isBool',       'required' => true                , 'db_type' => 'TINYINT(1)',       'default' => '0'],
            'date_add'            => ['type' => self::TYPE_DATE,                   'shop' => true, 'validate' => 'isDateFormat'                                    , 'db_type' => 'DATETIME'                          ],
            'date_upd'            => ['type' => self::TYPE_DATE,                   'shop' => true, 'validate' => 'isDateFormat'                                    , 'db_type' => 'DATETIME'                          ],
            'id_order_state'      => ['type' => self::TYPE_INT,                                    'validate' => 'isUnsignedId', 'required' => true                , 'db_type' => 'INT(11) UNSIGNED', 'default' => '0'],
            'id_cart_rule'        => ['type' => self::TYPE_INT,                                    'validate' => 'isUnsignedId', 'required' => true                , 'db_type' => 'INT(11) UNSIGNED', 'default' => '0'],
            'cart_type'           => ['type' => self::TYPE_INT,                                    'validate' => 'isInt'                                           , 'db_type' => 'TINYINT(4)',       'default' => '0'],
            'name'                => ['type' => self::TYPE_STRING, 'lang' => true,                 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'db_type' => 'VARCHAR(128)'                      ],
            'description'         => ['type' => self::TYPE_HTML,   'lang' => true,                 'validate' => 'isString'                                        , 'db_type' => 'TEXT'                              ],
            'description_success' => ['type' => self::TYPE_HTML,   'lang' => true,                 'validate' => 'isString'                                        , 'db_type' => 'TEXT'                              ],
            'description_short'   => ['type' => self::TYPE_STRING, 'lang' => true,                 'validate' => 'isGenericName', 'required' => true, 'size' => 255, 'db_type' => 'VARCHAR(255)'                      ],
        ],
    ];

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
     * @internal param bool|int $idCarrier
     * @since    1.0.0
     */
    public static function getCustomPaymentMethods($idLang, $active = true, $idCarrier = false, $groups = [])
    {
        if (!\Validate::isBool($active)) {
            die(\Tools::displayError());
        }

        if (!empty($groups)) {
            foreach ($groups as &$group) {
                $group = (int) $group;
            }
        }

        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(self::$definition['table']), 'cpm');
        $sql->leftJoin(bqSQL(self::$definition['table']).'_lang', 'cpml', 'cpml.`'.bqSQL(self::$definition['primary']).'` = cpm.`'.bqSQL(self::$definition['primary']).'`');
        $sql->leftJoin(bqSQL(self::$definition['table']).'_shop', 'cpms', 'cpms.`'.bqSQL(self::$definition['primary']).'` = cpm.`'.bqSQL(self::$definition['primary']).'`');
        if ($idCarrier) {
            $sql->innerJoin('custom_payment_method_carrier', 'cpmc', 'cpmc.`id_carrier` = '.(int) $idCarrier);
        }
        if (!empty($groups) && \Group::isFeatureActive()) {
            $sql->innerJoin('custom_payment_method_group', 'cmpg', 'cmpg.`id_group` IN ('.implode($groups, ',').')');
        }
        $sql->where('cpml.`id_lang` = '.(int) $idLang);
        $sql->where('cpms.`id_shop` = '.(int) \Context::getContext()->shop->id);
        if ($active) {
            $sql->where('`active` = 1');
        }
        $sql->groupBy('cpms.`'.bqSQL(self::$definition['primary']).'`');
        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return $result;
    }

    /**
     * @param string $name
     *
     * @return false|null|string
     *
     * @since 1.0.0
     */
    public static function getIdByName($name)
    {
        $sql = new \DbQuery();
        $sql->select(bqSQL(self::$definition['primary']));
        $sql->from(bqSQL(self::$definition['table']).'_lang');
        $sql->where('`name` = \''.pSQL($name).'\'');

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getCarriers()
    {
        $carriers = [];

        $sql = new \DbQuery();
        $sql->select('cpmc.`id_carrier`');
        $sql->from('custom_payment_method_carrier', 'cpmc');
        $sql->where('cpmc.`'.bqSQL(self::$definition['primary']).'` = '.(int) $this->id);

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
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
            \Db::getInstance()->insert('custom_payment_method_carrier', $row);
        }
    }

    /**
     * Update Carrier
     *
     * @since 1.0.0
     *
     * @param int $oldCarrierId
     * @param int $newCarrierId
     */
    public static function updateCarrier($oldCarrierId, $newCarrierId)
    {
        \Db::getInstance()->update(
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
     */
    public function deleteCarrier($idCarrier = false)
    {
        return \Db::getInstance()->delete(
            'custom_payment_method_carrier',
            '`id_custom_payment_method` = '.(int) $this->id.($idCarrier ? 'AND `id_carrier` = '.(int) $idCarrier.' LIMIT 1' : '')
        );
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getGroups()
    {
        $carriers = [];
        $sql = new \DbQuery();
        $sql->select('cpmg.`id_group`');
        $sql->from('custom_payment_method_group', 'cpmg');
        $sql->where('cmpg.`id_custom_payment_method` = '.(int) $this->id);

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($result as $carrier) {
            $carriers[] = $carrier['id_group'];
        }

        return $carriers;
    }

    /**
     * @param array $groups
     *
     * @since 1.0.0
     */
    public function addGroups($groups)
    {
        foreach ($groups as $group) {
            $row = ['id_custom_payment_method' => (int) $this->id, 'id_group' => (int) $group];
            \Db::getInstance()->insert('custom_payment_method_group', $row);
        }
    }

    /**
     * @param bool $idGroup
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function deleteGroup($idGroup = false)
    {
        return \Db::getInstance()->execute(
            'custom_payment_method_group',
            '`id_custom_payment_method` = '.(int) $this->id.($idGroup ? 'AND `id_group` = '.(int) $idGroup.' LIMIT 1' : '')
        );
    }

    /**
     * @return bool
     *
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
     * @since 1.0.0
     */
    public function add($autodate = true, $nullValues = false)
    {
        $ret = parent::add($autodate, $nullValues);
        $this->updateCarriers($this->carrier_box);
        $this->updateGroups($this->group_box);

        return $ret;
    }

    public static function createDatabase($className = null)
    {
        parent::createDatabase($className);

        \Db::getInstance()->execute(
            'CREATE TABLE `'._DB_PREFIX_.'custom_payment_method_shop` (
		       `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
		       `id_shop`                  INT(11) UNSIGNED  NOT NULL,
		       `date_add`                 DATETIME NOT NULL,
               `date_upd`                 DATETIME NOT NULL,
		       UNIQUE KEY `id_custom_payment_method_shop` (`id_custom_payment_method`,`id_shop`)
		     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        \Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method_carrier` (
		       `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
		       `id_carrier`               INT(11) UNSIGNED NOT NULL,
		       UNIQUE KEY `custom_payment_method_carrier` (`id_custom_payment_method`,`id_carrier`)
		     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        \Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'custom_payment_method_group` (
		       `id_custom_payment_method` INT(11) UNSIGNED NOT NULL,
		       `id_group`                 INT(11) UNSIGNED NOT NULL,
		       UNIQUE KEY `id_custom_payment_method_group` (`id_custom_payment_method`,`id_group`)
		     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        \Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'orders` ADD `up_fields` VARCHAR(255) NOT NULL DEFAULT ""');
    }

    public static function dropDatabase($className = null)
    {
        \Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method`');
        \Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_lang`');
        \Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_carrier`');
        \Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_group`');
        \Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'custom_payment_method_shop`');
        \Db::getInstance()->execute('ALTER TABLE `'. _DB_PREFIX_.'orders` DROP `up_fields`');
    }
}
