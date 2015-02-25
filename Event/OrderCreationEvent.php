<?php
/**
 * Created by PhpStorm.
 * User: gbarral
 * Date: 28/08/2014
 * Time: 17:13
 */

namespace OrderCreation\Event;


use Thelia\Core\Event\ActionEvent;

class OrderCreationEvent extends ActionEvent
{

    /** @var  \Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /** @var  int $customerId */
    protected $customerId;

    /** @var  int $deliveryAddressId */
    protected $deliveryAddressId;

    /** @var  int $invoiceAddressId */
    protected $invoiceAddressId;

    /** @var  int $deliveryModuleId */
    protected $deliveryModuleId;

    /** @var  int $paymentModuleId */
    protected $paymentModuleId;

    /** @var  array $productSaleElementIds */
    protected $productSaleElementIds;

    /** @var  array $quantities */
    protected $quantities;

    /** @var  \Thelia\Model\CartItem $cartItem */
    protected $cartItem;

    /** @var  \Thelia\Model\Order $customerId */
    protected $placedOrder;

    protected $response;

    public function __construct()
    {
        //
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @return OrderCreationEvent
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param \Thelia\Model\CartItem $cartItem
     *
     * @return OrderCreationEvent
     */
    public function setCartItem($cartItem)
    {
        $this->cartItem = $cartItem;

        return $this;
    }

    /**
     * @return \Thelia\Model\CartItem
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * @param int $customerId
     *
     * @return OrderCreationEvent
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $deliveryAddressId
     *
     * @return OrderCreationEvent
     */
    public function setDeliveryAddressId($deliveryAddressId)
    {
        $this->deliveryAddressId = $deliveryAddressId;

        return $this;
    }

    /**
     * @return int
     */
    public function getDeliveryAddressId()
    {
        return $this->deliveryAddressId;
    }

    /**
     * @param int $deliveryModuleId
     *
     * @return OrderCreationEvent
     */
    public function setDeliveryModuleId($deliveryModuleId)
    {
        $this->deliveryModuleId = $deliveryModuleId;

        return $this;
    }

    /**
     * @return int
     */
    public function getDeliveryModuleId()
    {
        return $this->deliveryModuleId;
    }

    /**
     * @param int $invoiceAddressId
     *
     * @return OrderCreationEvent
     */
    public function setInvoiceAddressId($invoiceAddressId)
    {
        $this->invoiceAddressId = $invoiceAddressId;

        return $this;
    }

    /**
     * @return int
     */
    public function getInvoiceAddressId()
    {
        return $this->invoiceAddressId;
    }

    /**
     * @param int $paymentModuleId
     *
     * @return OrderCreationEvent
     */
    public function setPaymentModuleId($paymentModuleId)
    {
        $this->paymentModuleId = $paymentModuleId;

        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentModuleId()
    {
        return $this->paymentModuleId;
    }

    /**
     * @param \Thelia\Model\Order $placedOrder
     *
     * @return OrderCreationEvent
     */
    public function setPlacedOrder($placedOrder)
    {
        $this->placedOrder = $placedOrder;

        return $this;
    }

    /**
     * @return \Thelia\Model\Order
     */
    public function getPlacedOrder()
    {
        return $this->placedOrder;
    }

    /**
     * @param array $productSaleElementIds
     *
     * @return OrderCreationEvent
     */
    public function setProductSaleElementIds($productSaleElementIds)
    {
        $this->productSaleElementIds = $productSaleElementIds;

        return $this;
    }

    /**
     * @return array
     */
    public function getProductSaleElementIds()
    {
        return $this->productSaleElementIds;
    }

    /**
     * @param array $quantities
     *
     * @return OrderCreationEvent
     */
    public function setQuantities($quantities)
    {
        $this->quantities = $quantities;

        return $this;
    }

    /**
     * @return array
     */
    public function getQuantities()
    {
        return $this->quantities;
    }

    /**
     * @param mixed $response
     *
     * @return OrderCreationEvent
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
