<?php
/**
 * Created by PhpStorm.
 * User: gbarral
 * Date: 28/08/2014
 * Time: 17:19
 */

namespace OrderCreation\EventListeners;


use OrderCreation\Event\OrderCreationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\Order\OrderManualEvent;
use Thelia\Core\Event\Order\OrderPaymentEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\Base\AddressQuery;
use Thelia\Model\Base\CustomerQuery;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Cart;
use Thelia\Model\CartItem;
use Thelia\Model\Currency;
use Thelia\Model\Lang;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderPostage;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\ProductPriceQuery;

class OrderCreationListener implements EventSubscriberInterface
{

    const ADMIN_ORDER_CREATE = "action.admin.order.create";
    const ADMIN_ORDER_BEFORE_ADD_CART = "action.admin.order.before.add.cart";
    const ADMIN_ORDER_AFTER_CREATE_MANUAL = "action.admin.order.after.create.manual";

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            self::ADMIN_ORDER_CREATE => array('adminOrderCreate', 128)
        );
    }

    public function adminOrderCreate(OrderCreationEvent $event)
    {
        $pseIds = $event->getProductSaleElementIds();
        $quantities = $event->getQuantities();

        /** @var \Thelia\Model\Address $deliveryAddress */
        $deliveryAddress = AddressQuery::create()->findPk($event->getDeliveryAddressId());
        /** @var \Thelia\Model\Address $invoiceAddress */
        $invoiceAddress = AddressQuery::create()->findPk($event->getInvoiceAddressId());
        /** @var \Thelia\Model\Module $deliveryModule */
        $deliveryModule = ModuleQuery::create()->findPk($event->getDeliveryModuleId());
        /** @var \Thelia\Model\Module $paymentModule */
        $paymentModule = ModuleQuery::create()->findPk($event->getPaymentModuleId());

        /** @var \Thelia\Model\Currency $currency */
        $currency = Currency::getDefaultCurrency();
        $lang = new Lang();

        /** @var \Thelia\Model\Customer $customer */
        $customer = CustomerQuery::create()->findPk($event->getCustomerId());

        $order = new Order();
        $order
            ->setCustomerId($customer->getId())
            ->setCurrencyId($currency->getId())
            ->setCurrencyRate($currency->getRate())
            ->setStatusId(OrderStatusQuery::getNotPaidStatus()->getId())
            ->setLangId($lang->getDefaultLanguage()->getId())
            ->setChoosenDeliveryAddress($deliveryAddress)
            ->setChoosenInvoiceAddress($invoiceAddress)
        ;

        $cart = new Cart();
        $cart->setToken(uniqid("createorder", true))
            ->setCustomer($customer)
            ->setCurrency($currency->getDefaultCurrency())
            ->save()
        ;

        foreach ($pseIds as $key => $pseId) {

            /** @var \Thelia\Model\ProductSaleElements $productSaleElements */
            if (null != $productSaleElements = ProductSaleElementsQuery::create()->findOneById($pseId)) {

                /** @var \Thelia\Model\ProductPrice $productPrice */
                if (null != $productPrice = ProductPriceQuery::create()
                        ->filterByProductSaleElementsId($productSaleElements->getId())
                        ->filterByCurrencyId($currency->getDefaultCurrency()->getId())
                        ->findOne()) {

                    $cartItem = new CartItem();
                    $cartItem
                        ->setCart($cart)
                        ->setProduct($productSaleElements->getProduct())
                        ->setProductSaleElements($productSaleElements)
                        ->setQuantity($quantities[$key])
                        ->setPrice($productPrice->getPrice())
                        ->setPromoPrice($productPrice->getPromoPrice())
                        ->setPromo($productSaleElements->getPromo())
                        ->setPriceEndOfLife(time() + 60*60*24*30);

                    $event->setCartItem($cartItem);

                    $event->getDispatcher()->dispatch(self::ADMIN_ORDER_BEFORE_ADD_CART, $event);

                    $cartItem->save();

                }
            }
        }

        //If someone is connected in FRONT, stock it
        $oldCustomer = $this->request->getSession()->getCustomerUser();

        //Do the same for his cart
        $oldCart = $this->request->getSession()->getSessionCart($event->getDispatcher());

        $this->request->getSession()->setCustomerUser($customer);

        $this->request->getSession()->set("thelia.cart_id", $cart->getId());

        $orderEvent = new OrderEvent($order);
        $orderEvent->setDeliveryAddress($deliveryAddress->getId());
        $orderEvent->setInvoiceAddress($invoiceAddress->getId());

        $moduleInstance = $deliveryModule->getModuleInstance($event->getContainer());
        $postage = OrderPostage::loadFromPostage(
            $moduleInstance->getPostage($deliveryAddress->getCountry())
        );
        $orderEvent->setPostage($postage->getAmount());
        $orderEvent->setPostageTax($postage->getAmountTax());
        $orderEvent->setPostageTaxRuleTitle($postage->getTaxRuleTitle());
        $orderEvent->setDeliveryModule($deliveryModule->getId());
        $orderEvent->setPaymentModule($paymentModule->getId());

        $event->getDispatcher()->dispatch(TheliaEvents::ORDER_SET_DELIVERY_ADDRESS, $orderEvent);
        $event->getDispatcher()->dispatch(TheliaEvents::ORDER_SET_INVOICE_ADDRESS, $orderEvent);
        $event->getDispatcher()->dispatch(TheliaEvents::ORDER_SET_POSTAGE, $orderEvent);
        $event->getDispatcher()->dispatch(TheliaEvents::ORDER_SET_DELIVERY_MODULE, $orderEvent);
        $event->getDispatcher()->dispatch(TheliaEvents::ORDER_SET_PAYMENT_MODULE, $orderEvent);

        //DO NOT FORGET THAT THE DISCOUNT ORDER HAS TO BE PLACED IN CART
        if ($this->request->getSession()->getSessionCart($event->getDispatcher()) != null) {
            $cart->setCartItems($this->request->getSession()->getSessionCart($event->getDispatcher())->getCartItems());
            $cart->setDiscount($this->request->getSession()->getSessionCart($event->getDispatcher())->getDiscount());
        }

        $cart->save();

        $orderManualEvent = new OrderManualEvent(
            $orderEvent->getOrder(),
            $orderEvent->getOrder()->getCurrency(),
            $orderEvent->getOrder()->getLang(),
            $cart,
            $customer
        );

        $this->request->getSession()->set("thelia.cart_id", $cart->getId());


        $event->getDispatcher()->dispatch(TheliaEvents::ORDER_CREATE_MANUAL, $orderManualEvent);

        $event->getDispatcher()->dispatch(
            TheliaEvents::ORDER_BEFORE_PAYMENT,
            new OrderEvent($orderManualEvent->getPlacedOrder())
        );

        /* but memorize placed order */
        $orderEvent->setOrder(new Order());
        $orderEvent->setPlacedOrder($orderManualEvent->getPlacedOrder());

        /* call pay method */
        $payEvent = new OrderPaymentEvent($orderManualEvent->getPlacedOrder());

        $event->getDispatcher()->dispatch(TheliaEvents::MODULE_PAY, $payEvent);

        if ($payEvent->hasResponse()) {
            $event->setResponse($payEvent->getResponse());
        }

        $event->setPlacedOrder($orderManualEvent->getPlacedOrder());
        $event->getDispatcher()->dispatch(self::ADMIN_ORDER_AFTER_CREATE_MANUAL, $event);

        //Reconnect the front user
        if ($oldCustomer != null) {
            $this->request->getSession()->setCustomerUser($oldCustomer);

            //And fill his cart
            if ($oldCart != null) {
                $this->request->getSession()->set("thelia.cart_id", $oldCart->getId());
            }
        } else {
            $this->request->getSession()->clearCustomerUser();
        }
    }
}
