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
    const SOCOLISSIMO_MODE = 'order_creation_socolissimo_mode';

    /**
     * @param $moduleId integer | null
     */
    public static function setDeliveryModuleId($moduleId)
    {
        OrderCreation::setConfigValue(self::CONFIG_KEY_DELIVERY_MODULE_ID, $moduleId);
    }

    public static function setSoColissimoMode($mode)
    {
        OrderCreation::setConfigValue(self::SOCOLISSIMO_MODE, $mode);
    }

    public static function getSoColissimoMode()
    {
        return OrderCreation::getConfigValue(self::SOCOLISSIMO_MODE, null);
    }

    /**
     * @return integer | null
     */
    public static function getDeliveryModuleId()
    {
        return OrderCreation::getConfigValue(self::CONFIG_KEY_DELIVERY_MODULE_ID, null);
    }


}