<?php
/**
 * Created by PhpStorm.
 * User: gbarral
 * Date: 28/08/2014
 * Time: 10:58
 */
namespace OrderCreation\Controller\Admin;

use OrderCreation\Event\OrderCreationEvent;
use OrderCreation\EventListeners\OrderCreationListener;
use OrderCreation\Form\OrderCreationCreateForm;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\Propel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\CustomerUpdateForm;
use Thelia\Model\AddressQuery;
use Thelia\Model\Base\CustomerQuery;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Customer;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\Map\ProductCategoryTableMap;
use Thelia\Model\Map\ProductI18nTableMap;
use Thelia\Model\Map\ProductSaleElementsTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class OrderCreationAdminController extends BaseAdminController
{
    public function addItemAction($position)
    {
        return $this->render(
            "ajax/add-cart-item",
            array("position" => $position)
        );
    }

    public function createOrderAction()
    {
        $response = $this->checkAuth(array(AdminResources::MODULE), array('OrderCreation'), AccessManager::CREATE);
        if (null !== $response) {
            return $response;
        }

        $con = Propel::getConnection(OrderTableMap::DATABASE_NAME);
        $con->beginTransaction();

        $form = new OrderCreationCreateForm($this->getRequest());

        try {

            $formValidate = $this->validateForm($form);

            $event = new OrderCreationEvent();

            $event
                ->setContainer($this->getContainer())
                ->setCustomerId($formValidate->get(OrderCreationCreateForm::FIELD_NAME_CUSTOMER_ID)->getData())
                ->setDeliveryAddressId($formValidate->get(OrderCreationCreateForm::FIELD_NAME_DELIVERY_ADDRESS_ID)->getData())
                ->setDeliveryModuleId($formValidate->get(OrderCreationCreateForm::FIELD_NAME_DELIVERY_MODULE_ID)->getData())
                ->setInvoiceAddressId($formValidate->get(OrderCreationCreateForm::FIELD_NAME_INVOICE_ADDRESS_ID)->getData())
                ->setPaymentModuleId($formValidate->get(OrderCreationCreateForm::FIELD_NAME_PAYMENT_MODULE_ID)->getData())
                ->setProductSaleElementIds($formValidate->get(OrderCreationCreateForm::FIELD_NAME_PRODUCT_SALE_ELEMENT_ID)->getData())
                ->setQuantities($formValidate->get(OrderCreationCreateForm::FIELD_NAME_QUANTITY)->getData())
            ;

            $this->dispatch(OrderCreationListener::ADMIN_ORDER_CREATE, $event);

            if (null != $event->getResponse()) {
                $con->commit();
                return $event->getResponse();
            }

            //Don't forget to fill the Customer form
            if (null != $customer = CustomerQuery::create()->findPk($formValidate->get('customer_id')->getData())) {
                $customerForm = $this->hydrateCustomerForm($customer);
                $this->getParserContext()->addForm($customerForm);
            }

            $con->commit();
            return RedirectResponse::create(
                URL::getInstance()->absoluteUrl(
                    '/admin/customer/update?customer_id='.$formValidate->get('customer_id')->getData()
                )
            );

        } catch (\Exception $e) {
            $con->rollBack();
            $form->setErrorMessage($e->getMessage());

            $this->getParserContext()
                ->addForm($form)
                ->setGeneralError($e->getMessage())
            ;

            //Don't forget to fill the Customer form
            if (null != $customer = CustomerQuery::create()
                    ->findPk($this->getRequest()->request->get('admin_order_create')['customer_id'])) {

                $customerForm = $this->hydrateCustomerForm($customer);
                $this->getParserContext()->addForm($customerForm);

            }

            return $this->render('customer-edit', array(
                'customer_id' => $this->getRequest()->request->get('admin_order_create')['customer_id'],
                "order_creation_error" => $e->getMessage()
            ));
        }
    }

    protected function hydrateCustomerForm(Customer $customer)
    {
        // Get default adress of the customer
        $address = $customer->getDefaultAddress();

        // Prepare the data that will hydrate the form
        $data = array(
            'id'        => $customer->getId(),
            'firstname' => $customer->getFirstname(),
            'lastname'  => $customer->getLastname(),
            'email'     => $customer->getEmail(),
            'title'     => $customer->getTitleId(),
            'discount'  => $customer->getDiscount(),
            'reseller'  => $customer->getReseller(),
        );

        if ($address !== null) {
            $data['company']   = $address->getCompany();
            $data['address1']  = $address->getAddress1();
            $data['address2']  = $address->getAddress2();
            $data['address3']  = $address->getAddress3();
            $data['phone']     = $address->getPhone();
            $data['cellphone'] = $address->getCellphone();
            $data['zipcode']   = $address->getZipcode();
            $data['city']      = $address->getCity();
            $data['country']   = $address->getCountryId();
        }

        // A loop is used in the template
        return new CustomerUpdateForm($this->getRequest(), 'form', $data);
    }

    public function getAvailableProductAction($categoryId = null)
    {
        $result = array();

        if ($categoryId !== null) {

            $pseQuery = ProductSaleElementsQuery::create();

            $productJoin = new Join(ProductSaleElementsTableMap::PRODUCT_ID, ProductTableMap::ID, Criteria::INNER_JOIN);
            $pseQuery->addJoinObject($productJoin);

            $productI18nJoin = new Join(ProductTableMap::ID, ProductI18nTableMap::ID, Criteria::INNER_JOIN);
            $pseQuery->addJoinObject($productI18nJoin, 'productI18n_JOIN');
            $pseQuery->addJoinCondition(
                "productI18n_JOIN",
                "product_i18n.locale = '".$this->getCurrentEditionLocale()."'",
                null,
                null,
                \PDO::PARAM_STR
            );

            $productCategoryJoin = new Join(
                ProductTableMap::ID,
                ProductCategoryTableMap::PRODUCT_ID,
                Criteria::INNER_JOIN
            );
            $pseQuery->addJoinObject($productCategoryJoin, "productCategory_JOIN");
            $pseQuery->addJoinCondition(
                "productCategory_JOIN",
                "product_category.default_category = ?",
                1,
                null,
                \PDO::PARAM_INT
            );

            $pseQuery->addJoinCondition(
                "productCategory_JOIN",
                "product_category.category_id = ?",
                $categoryId,
                null,
                \PDO::PARAM_INT
            );

            $pseQuery->addAscendingOrderByColumn('product_i18n.TITLE');

            $pseQuery->withColumn("product_i18n.title", "PRODUCT_TITLE");
            $pseQuery->withColumn("product.id", "PRODUCT_ID");

            $pses = $pseQuery->find();

            /** @var \Thelia\Model\ProductSaleElements $pse */
            foreach ($pses as $pse) {
                $result[] = array(
                    'id' => $pse->getId(),
                    'product_id' => $pse->getVirtualColumns()["PRODUCT_ID"],
                    'ref' => $pse->getRef(),
                    'title' => $pse->getVirtualColumns()["PRODUCT_TITLE"],
                    'quantity' => $pse->getQuantity()
                );
            }
        }

        return $this->jsonResponse(json_encode($result));
    }

    public function updateCountryInRequest()
    {
        if (null != $addressId = $this->getRequest()->request->get('address_id')) {

            if (null != $address = AddressQuery::create()->findPk($addressId)) {
                $order = new Order();
                $order
                    ->setCustomer()
                    ->setChoosenDeliveryAddress($addressId);

                $this->getRequest()->getSession()->set(
                    "thelia.order",
                    $order
                );

                $this->getRequest()->getSession()->set(
                    "thelia.customer_user",
                    $address->getCustomer()
                );
            }

        }

        return null;
    }
}
