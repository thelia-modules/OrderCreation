<?php
/**
 * Created by PhpStorm.
 * User: audreymartel
 * Date: 27/07/2018
 * Time: 15:51
 */

namespace OrderCreation\Form;

use OrderCreation\OrderCreation;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;

class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'order_creation_delivery_module_id',
                TextType::class,
                [
                    'label' => $this->translator->trans("Delivery module use to create order in back office", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => array(
                        'help' =>  $this->translator->trans('Leave blank to select delivery module on each order', [], OrderCreation::MESSAGE_DOMAIN)
                    ),
                    'data' => OrderCreation::getConfigValue('order_creation_delivery_module_id'),
                    'constraints' => [],
                ]
            );
    }
}
