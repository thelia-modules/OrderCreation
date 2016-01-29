<?php
/**
 * Created by PhpStorm.
 * User: gbarral
 * Date: 28/08/2014
 * Time: 11:02
 */

namespace OrderCreation\Form;


use OrderCreation\OrderCreation;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class OrderCreationCreateForm extends BaseForm
{

    const FIELD_NAME_CUSTOMER_ID = 'customer_id';
    const FIELD_NAME_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const FIELD_NAME_INVOICE_ADDRESS_ID = 'invoice_address_id';
    const FIELD_NAME_DELIVERY_MODULE_ID = 'delivery-module';
    const FIELD_NAME_PAYMENT_MODULE_ID = 'payment_module_id';
    const FIELD_NAME_PRODUCT_SALE_ELEMENT_ID = 'product_sale_element_id';
    const FIELD_NAME_QUANTITY = 'quantity';

    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     * $this->formBuilder->add("name", "text")
     *   ->add("email", "email", array(
     *           "attr" => array(
     *               "class" => "field"
     *           ),
     *           "label" => "email",
     *           "constraints" => array(
     *               new \Symfony\Component\Validator\Constraints\NotBlank()
     *           )
     *       )
     *   )
     *   ->add('age', 'integer');
     *
     * @return null
     */
    protected function buildForm()
    {
        $this->formBuilder

            ->add(
                self::FIELD_NAME_CUSTOMER_ID,
                'integer',
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'label' => Translator::getInstance()->trans("Customer", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_NAME_CUSTOMER_ID . '_form'
                    ]
                ]
            )
            ->add(
                self::FIELD_NAME_DELIVERY_ADDRESS_ID,
                'integer',
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'label' => Translator::getInstance()->trans("Delivery address", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_NAME_DELIVERY_ADDRESS_ID . '_form'
                    ]
                ]
            )
            ->add(
                self::FIELD_NAME_INVOICE_ADDRESS_ID,
                'integer',
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'label' => Translator::getInstance()->trans("Invoice address", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_NAME_INVOICE_ADDRESS_ID . '_form'
                    ]
                ]
            )
            ->add(
                self::FIELD_NAME_DELIVERY_MODULE_ID,
                'integer',
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'label' => Translator::getInstance()->trans("Transport solution", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_NAME_DELIVERY_MODULE_ID . '_form'
                    ]
                ]
            )
            ->add(
                self::FIELD_NAME_PAYMENT_MODULE_ID,
                'integer',
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'label' => Translator::getInstance()->trans("Payment solution", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_NAME_PAYMENT_MODULE_ID . '_form'
                    ]
                ]
            )
            ->add(
                self::FIELD_NAME_PRODUCT_SALE_ELEMENT_ID,
                'collection',
                [
                    'type'         => 'number',
                    'label'        => Translator::getInstance()->trans('Product', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr'   => [
                        'for' => self::FIELD_NAME_PRODUCT_SALE_ELEMENT_ID . '_form'
                    ],
                    'allow_add'    => true,
                    'allow_delete' => true,
                ]
            )
            ->add(
                self::FIELD_NAME_QUANTITY,
                'collection',
                [
                    'type'         => 'number',
                    'label'        => Translator::getInstance()->trans('Quantity', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr'   => [
                        'for' => self::FIELD_NAME_QUANTITY . '_form'
                    ],
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'options'      => [
                        'constraints' => [
                            new NotBlank(),
                            new GreaterThan(
                                ['value' => 0]
                            )
                        ]
                    ]
                ]
            )
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        //This name MUST be the same that the form OrderDelivery (because of ajax delivery module return)
        return "thelia_order_delivery";
    }
}
