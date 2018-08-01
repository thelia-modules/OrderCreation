<?php
/**
 * Created by PhpStorm.
 * User: audreymartel
 * Date: 27/07/2018
 * Time: 16:27
 */

namespace OrderCreation;


class OrderCreationConfiguration
{
    const CONFIG_KEY_DELIVERY_MODULE_ID = 'order_creation_delivery_module_id';

    /**
     * @param $moduleId integer | null
     */
    public static function setDeliveryModuleId($moduleId)
    {
        OrderCreation::setConfigValue(self::CONFIG_KEY_DELIVERY_MODULE_ID, $moduleId);
    }

    /**
     * @return integer | null
     */
    public static function getDeliveryModuleId()
    {
        return OrderCreation::getConfigValue(self::CONFIG_KEY_DELIVERY_MODULE_ID, null);
    }

}