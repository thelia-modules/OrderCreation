<?php
/**
 * Created by PhpStorm.
 * User: gbarral
 * Date: 28/08/2014
 * Time: 11:02
 */

namespace OrderCreation\Form;

use OrderCreation\OrderCreation;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
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
                    'label' => $this->translator->trans("Customer", [], OrderCreation::MESSAGE_DOMAIN),
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
                    'label' => $this->translator->trans("Delivery address", [], OrderCreation::MESSAGE_DOMAIN),
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
                    'label' => $this->translator->trans("Invoice address", [], OrderCreation::MESSAGE_DOMAIN),
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
                    'label' => $this->translator->trans("Transport solution", [], OrderCreation::MESSAGE_DOMAIN),
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
                    'label' => $this->translator->trans("Payment solution", [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_NAME_PAYMENT_MODULE_ID . '_form'
                    ]
                ]
            )
            ->add(
                self::FIELD_NAME_PRODUCT_SALE_ELEMENT_ID,
                CollectionType::class,
                [
                    'entry_type'         => NumberType::class,
                    'label'        => $this->translator->trans('Product', [], OrderCreation::MESSAGE_DOMAIN),
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
                    'entry_type'         => NumberType::class,
                    'label'        => $this->translator->trans('Quantity', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr'   => [
                        'for' => self::FIELD_NAME_QUANTITY . '_form'
                    ],
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'entry_options'      => [
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
                        $this->translator->trans('Constant amount', [], 'core') => Sale::OFFSET_TYPE_AMOUNT,
                        $this->translator->trans('Percentage', [], 'core') => Sale::OFFSET_TYPE_PERCENTAGE,
                    ],
                    'required'    => true,
                    'label'       => $this->translator->trans('Discount type', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr'  => [
                        'for'         => self::FIELD_DISCOUNT_TYPE,
                        'help'        => $this->translator->trans('Select the discount type that will be applied to the order price', [], OrderCreation::MESSAGE_DOMAIN),
                    ],
                    'attr' => []
                ]
            )
            ->add(
                self::FIELD_DISCOUNT_PRICE,
                NumberType::class,
                [
                    'required' => false,
                    'constraints' => [],
                    'label'        => $this->translator->trans('Discount value', [], OrderCreation::MESSAGE_DOMAIN),
                    'attr'         => [
                        'placeholder' => $this->translator->trans('Discount included taxes', [], OrderCreation::MESSAGE_DOMAIN)
                    ],
                    'label_attr'   => [
                        'for' => self::FIELD_DISCOUNT_PRICE,
                         'help'  => $this->translator->trans('You can define here a specific discount, as a percentage or a constant amount, depending on the selected discount type.', [], OrderCreation::MESSAGE_DOMAIN),

                    ],
                ]
            )
            ->add(
                self::FIELD_CHECK_REDIRECTS_PAYMENT,
                CheckboxType::class,
                [
                    "label" => $this->translator->trans('Go to payment page after order creation', [], OrderCreation::MESSAGE_DOMAIN),
                    'label_attr' => [
                        'for' => self::FIELD_CHECK_REDIRECTS_PAYMENT,
                        'help' => $this->translator->trans('Check this box if you want to pay the order with the selected payment module ', [], OrderCreation::MESSAGE_DOMAIN),

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
    public static function getName()
    {
        //This name MUST be the same that the form OrderDelivery (because of ajax delivery module return)
        return "thelia_order_delivery";
    }
}
