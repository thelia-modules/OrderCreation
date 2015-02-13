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

            ->add('customer_id', 'integer', array(
                'constraints' => array(
                    new NotBlank()
                ),
                'label' => 'customer_id',
                'label_attr' => array(
                    'for' => 'customer_id_form'
                )
            ))
            ->add('delivery_address_id', 'integer', array(
                'constraints' => array(
                    new NotBlank()
                ),
                'label' => Translator::getInstance()->trans("Delivery address", array(), OrderCreation::MESSAGE_DOMAIN),
                'label_attr' => array(
                    'for' => 'delivery_address_id_form'
                )
            ))
            ->add('invoice_address_id', 'integer', array(
                'constraints' => array(
                    new NotBlank()
                ),
                'label' => Translator::getInstance()->trans("Invoice address", array(), OrderCreation::MESSAGE_DOMAIN),
                'label_attr' => array(
                    'for' => 'invoice_address_id_form'
                )
            ))
            ->add('delivery_module_id', 'integer', array(
                'constraints' => array(
                    new NotBlank()
                ),
                'label' => Translator::getInstance()->trans("Transport solution", array(), OrderCreation::MESSAGE_DOMAIN),
                'label_attr' => array(
                    'for' => 'delivery_module_id_form'
                )
            ))
            ->add('payment_module_id', 'integer', array(
                'constraints' => array(
                    new NotBlank()
                ),
                'label' => Translator::getInstance()->trans("Payment solution", array(), OrderCreation::MESSAGE_DOMAIN),
                'label_attr' => array(
                    'for' => 'payment_module_id_form'
                )
            ))
            ->add('product_sale_element_id', 'collection', array(
                'type'         => 'number',
                'label'        => Translator::getInstance()->trans('Product', array(), OrderCreation::MESSAGE_DOMAIN),
                'label_attr'   => array('for' => 'product_sale_element_id_form'),
                'allow_add'    => true,
                'allow_delete' => true,
            ))
            ->add('quantity', 'collection', array(
                'type'         => 'number',
                'label'        => Translator::getInstance()->trans('Quantity', array(), OrderCreation::MESSAGE_DOMAIN),
                'label_attr'   => array('for' => 'quantity_form'),
                'allow_add'    => true,
                'allow_delete' => true,
                'options'      => array(
                    'constraints' => array(
                        new NotBlank(),
                        new GreaterThan(
                            array('value' => 0)
                        )
                    )
                )
            ))
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return "admin_order_create";
    }
}
