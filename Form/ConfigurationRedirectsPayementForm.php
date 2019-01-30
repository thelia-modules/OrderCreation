<?php
/**
 * Created by PhpStorm.
 * User: audreymartel
 * Date: 27/07/2018
 * Time: 15:51
 */

namespace OrderCreation\Form;

use OrderCreation\OrderCreation;
use OrderCreation\OrderCreationConfiguration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Thelia\Form\BaseForm;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;

class ConfigurationRedirectsPayementForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'order_creation_redirects_payment',
                ChoiceType::class,
                [
                    'label' => $this->translator->trans(
                        "Select all redirectable payment modules",
                        [],
                        OrderCreation::MESSAGE_DOMAIN
                    ),
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $this->getPaymentModuleList(),
                    'data' => $this->getSelectedModule(),
                ]
            );
    }

    private function getPaymentModuleList()
    {
        $modules = ModuleQuery::create()
            ->filterByType(3)
            ->filterByActivate(1)
            ->find();

        if (0 != count($modules->getData())) {
            $tabChoices = [];

            /** @var Module $module */
            foreach ($modules->getData() as $module) {
                $tabChoices[$module->getId()] = $module->getCode();
            }

            return $tabChoices;
        } else {
            return [];
        }
    }

    private function getSelectedModule()
    {
        $listModules = OrderCreationConfiguration::getlistPaymentModule();

        return json_decode($listModules);
    }
}
