<?php
/**
 * Created by PhpStorm.
 * User: gbarral
 * Date: 28/08/2014
 * Time: 11:02
 */

namespace OrderCreation\Form;


use OrderCreation\OrderCreation;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\Sale;

class OrderCreationCreateForm extends BaseForm
{

    const FIELD_NAME_CUSTOMER_ID = 'customer_id';
    const FIELD_NAME_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const FIELD_NAME_INVOICE_ADDRESS_ID = 'invoice_address_id';
    const FIELD_NAME_DELIVERY_MODULE_ID = 'delivery-module';
    const FIELD_NAME_PAYMENT_MODULE_ID = 'payment_module_id';
    const FIELD_NAME_PRODUCT_SALE_ELEMENT_ID = 'product_sale_element_id';
    const FIELD_NAME_QUANTITY = 'quantity';
    const FIELD_DISCOUNT_TYPE = 'discount_type';
    const FIELD_DISCOUNT_PRICE = 'discount_price';
    const FIELD_CHECK_REDIRECTS_PAYMENT = 'redirects_payment';

    /**
     *
     * in this function you add all the fields you need for your Form.
     * @return null
     */
    protected function buildForm()
    {
        $this->formBuilder

            ->add(
                self::FIELD_NAME_CUSTOMER_ID,
                IntegerType::class,
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
                IntegerType::class,
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
                IntegerType::class,
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
                IntegerType::class,
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
                IntegerType::class,
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
                CollectionType::class,
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
                CollectionType::class,
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
            ->add(
                self::FIELD_DISCOUNT_TYPE,
                ChoiceType::class,
                [
                    'constraints' => [ new NotBlank() ],
                    'choices'     => [
                        Sale::OFFSET_TYPE_AMOUNT     => Translator::getInstance()->trans('Constant amount', [], 'core'),
                        Sale::OFFSET_TYPE_PERCENTAGE => Translator::getInstance()->trans('Percentage', [], 'core'),
                    ],
                    'required'    => true,
                    'label'       => Translator::getInstance()->trans('Discount type', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr'  => [
                        'for'         => self::FIELD_DISCOUNT_TYPE,
                        'help'        => Translator::getInstance()->trans('Select the discount type that will be applied to the order price', [], OrderCreation::MESSAGE_DOMAIN),
                    ],
                    'attr' => []
                ]
            )
            ->add(
                self::FIELD_DISCOUNT_PRICE,
                NumberType::class,
                [
                    'constraints' => [],
                    'label'        => Translator::getInstance()->trans('Discount value', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr'   => [
                        'for' => self::FIELD_DISCOUNT_PRICE,
                         'help'        => Translator::getInstance()->trans('You can define here a specific discount, as a percentage or a constant amount, depending on the selected discount type.', [], OrderCreation::MESSAGE_DOMAIN),
                    ],
                ]
            )
            ->add(
                self::FIELD_CHECK_REDIRECTS_PAYMENT,
                "checkbox",
                [
                    "label" => $this->translator->trans('Auto redirects payment', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_CHECK_REDIRECTS_PAYMENT,
                    ],
                    "required" => false,
                    "value" => false,
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
