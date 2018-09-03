<?php

namespace OrderCreation\Service;


use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;

class ModuleDataService
{
    public function getModuleCode($code)
    {
        /** @var Module $module */
        $module = ModuleQuery::create()
            ->filterById($code)
            ->findOne();

        if(null !== $module){
            return $module->getCode();
        }
    }
}