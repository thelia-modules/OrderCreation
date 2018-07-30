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
use OrderCreation\OrderCreation;
use OrderCreation\OrderCreationConfiguration;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\Propel;
use Symfony\Component\Security\Acl\Exception\Exception;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\CustomerUpdateForm;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\AddressQuery;
use Thelia\Model\Base\CustomerQuery;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Customer;
use Thelia\Model\Exception\InvalidArgumentException;
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

    public function getConfigurationAjaxAction()
    {
        $moduleId = OrderCreationConfiguration::getDeliveryModuleId();
        return JsonResponse::create(["moduleId" => $moduleId]);
    }

    public function configureAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ucfirst(OrderCreation::MESSAGE_DOMAIN), AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm('admin.order.creation.form.configure');

        try {
            $form = $this->validateForm($configurationForm, "POST");
            $data = $form->getData();
            OrderCreationConfiguration::setDeliveryModuleId($data['order_creation_delivery_module_id']);
            $this->adminLogAppend(
                OrderCreation::MESSAGE_DOMAIN . ".configuration.message",
                AccessManager::UPDATE,
                sprintf("OrderCreation configuration updated")
            );

            if ($this->getRequest()->get('save_mode') == 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $url = '/admin/module/OrderCreation';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $url = '/admin/modules';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url));
        } catch (FormValidationException $ex) {
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            $this->getTranslator()->trans("OrderCreation configuration", [], OrderCreation::MESSAGE_DOMAIN),
            $error_msg,
            $configurationForm,
            $ex
        );


        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/OrderCreation'));
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

        $moduleId = OrderCreationConfiguration::getDeliveryModuleId();
        if ($moduleId !== null) {
            $orderDeliveryParameters = $form->getRequest()->request->get("thelia_order_delivery");
            $orderDeliveryParameters[OrderCreationCreateForm::FIELD_NAME_DELIVERY_MODULE_ID] = $moduleId;
            $form->getRequest()->request->set("thelia_order_delivery", $orderDeliveryParameters);
        }

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
                ->setDiscountPrice($formValidate->get(OrderCreationCreateForm::FIELD_DISCOUNT_PRICE)->getData())
                ->setDiscountType($formValidate->get(OrderCreationCreateForm::FIELD_DISCOUNT_TYPE)->getData())
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

        } catch (\Exception $e) {
            $con->rollBack();
            $error_message = $e->getMessage();
            $form->setErrorMessage($error_message);
            $this->getParserContext()
                ->addForm($form)
                ->setGeneralError($error_message);
            return $this->generateErrorRedirect($form);
        }
        return $this->generateSuccessRedirect($form);
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

    /**
     * @param null $categoryId
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Propel\Runtime\Exception\PropelException
     */
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

    /**
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function updateCountryInRequest()
    {
        $response = JsonResponse::create([], 200);
        try {
            $addressId = $this->getRequest()->request->get('address_id');
            if (null === $addressId) {
                throw new InvalidArgumentException(Translator::getInstance()->trans("You must pass address_id"));
            }
            $address = AddressQuery::create()->findPk($addressId);
            if (null === $address) {
                throw new Exception(Translator::getInstance()->trans("Cannot find address with id %addressId", ["%addressId" => $addressId]));
            }
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
        } catch (\Exception $e) {
            $response = JsonResponse::create(["error"=>$e->getMessage()], 500);
        }
        return $response;
    }
}
