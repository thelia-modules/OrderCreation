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
use OrderCreation\Form\ConfigurationForm;
use OrderCreation\Form\ConfigurationRedirectsPayementForm;
use OrderCreation\Form\OrderCreationCreateForm;
use OrderCreation\OrderCreation;
use OrderCreation\OrderCreationConfiguration;
use Propel\Runtime\Propel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\AddressQuery;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Exception\InvalidArgumentException;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\Map\ProductI18nTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Model\Order;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/OrderCreation", name="admin_order_creation")
 */
class OrderCreationAdminController extends BaseAdminController
{
    /**
     * @Route("/add-item/{position}", name="_add_item", methods="GET")
     */
    public function addItemAction($position)
    {
        return $this->render(
            "ajax/add-cart-item",
            array("position" => $position)
        );
    }

    /**
     * @Route("/config/ajax", name="_config_ajax", methods="GET")
     */
    public function getConfigurationAjaxAction()
    {
        $tabResult = [];

        $moduleId = OrderCreationConfiguration::getDeliveryModuleId();
        $tabResult['moduleId'] = $moduleId;

        if (OrderCreationConfiguration::getSoColissimoMode()) {
            $mode = OrderCreationConfiguration::getDeliveryModuleId();
            $tabResult['modeTT'] = $mode;
        }

        return JsonResponse::create($tabResult);
    }

    /**
     * @Route("/configure", name="_configure", methods="POST")
     */
    public function configureAction(RequestStack $requestStack)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ucfirst(OrderCreation::MESSAGE_DOMAIN), AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm(ConfigurationForm::getName());

        try {
            $form = $this->validateForm($configurationForm, "POST");
            $data = $form->getData();
            OrderCreationConfiguration::setDeliveryModuleId($data['order_creation_delivery_module_id']);

            /** @var Module $module */
            $module = ModuleQuery::create()
                ->filterById($data['order_creation_delivery_module_id'])
                ->findOne();

            $codeModule = "";

            if (null !== $module) {
                $codeModule = $module->getCode();
            }

            if (OrderCreation::SOCOLISSIMO == $codeModule) {
                OrderCreationConfiguration::setSoColissimoMode('DOM');
            } else {
                OrderCreationConfiguration::setSoColissimoMode('');
            }

            $this->adminLogAppend(
                OrderCreation::MESSAGE_DOMAIN . ".configuration.message",
                AccessManager::UPDATE,
                sprintf("OrderCreation configuration updated")
            );

            if ($requestStack->getCurrentRequest()->get('save_mode') === 'stay') {
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
            Translator::getInstance()->trans("OrderCreation configuration", [], OrderCreation::MESSAGE_DOMAIN),
            $error_msg,
            $configurationForm,
            $ex
        );


        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/OrderCreation'));
    }

    /**
     * @Route("/order/create", name="_create", methods="POST")
     */
    public function createOrderAction(ParserContext $parserContext, RequestStack $requestStack, EventDispatcherInterface $dispatcher)
    {
        $response = $this->checkAuth(array(AdminResources::MODULE), array('OrderCreation'), AccessManager::CREATE);
        if (null !== $response) {
            return $response;
        }

        $con = Propel::getConnection(OrderTableMap::DATABASE_NAME);
        $con->beginTransaction();

        $data = $requestStack->getCurrentRequest()->get("thelia_order_delivery");
        $data["discount_price"] = !empty($data["discount_price"]) ? $data["discount_price"] : "0";
        if (array_key_exists("redirects_payment", $data)){
            $data["redirects_payment"] = true;
        }

        $moduleId = OrderCreationConfiguration::getDeliveryModuleId();

        if ($moduleId !== null) {
            $data[OrderCreationCreateForm::FIELD_NAME_DELIVERY_MODULE_ID] = $moduleId;
        }

        $form = $this->createForm(OrderCreationCreateForm::getName(), FormType::class, $data);

        try {
            $formValidate = $this->validateForm($form);

            $event = new OrderCreationEvent();

            if ($formValidate->get(OrderCreationCreateForm::FIELD_CHECK_REDIRECTS_PAYMENT)->getData()) {
                $event->setRedirect(1);
            } else {
                $event->setRedirect(0);
            }

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
                ->setLang($this->getCurrentEditionLang());

            $dispatcher->dispatch($event, OrderCreationListener::ADMIN_ORDER_CREATE);

            if (null != $event->getResponse()) {
                $con->commit();
                return $event->getResponse();
            }

            $con->commit();
        } catch (\Exception $e) {
            $con->rollBack();

            $error_message = $e->getMessage();

            $form->setErrorMessage($error_message);

            $parserContext
                ->addForm($form)
                ->setGeneralError($error_message);

            return $this->generateErrorRedirect($form);
        }

        return $this->generateSuccessRedirect($form);
    }

    /**
     * @param null $categoryId
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Propel\Runtime\Exception\PropelException
     * @Route("/{productId}/list-products/{categoryId}.{_format}", name="_list-products", methods="GET", requirements={"_format": "xml|json"})
     */
    public function getAvailableProductAction($categoryId = null)
    {
        $result = array();

        if ($categoryId !== null) {
            $pses = ProductSaleElementsQuery::create()
                ->useProductQuery()
                    ->useProductCategoryQuery()
                        ->filterByDefaultCategory(true)
                        ->filterByCategoryId($categoryId)
                    ->endUse()
                    ->useI18nQuery($this->getCurrentEditionLocale())
                    ->endUse()
                ->endUse()
                ->withColumn(ProductTableMap::COL_ID, 'product_id')
                ->withColumn(ProductTableMap::COL_REF, 'product_ref')
                ->withColumn(ProductI18nTableMap::COL_TITLE, 'product_title')
                ->orderBy('product_title')
                ->find()
            ;

            /** @var \Thelia\Model\ProductSaleElements $pse */
            foreach ($pses as $pse) {
                $productRef = $pse->getVirtualColumn('product_ref');

                if (! isset($result[$productRef])) {
                    $result[$productRef] = [
                        'title'      => $pse->getVirtualColumn('product_title'),
                        'product_id' => $pse->getVirtualColumn('product_id'),
                        'pse_list'   => []
                    ];
                }

                $result[$productRef]['pse_list'][] = [
                    'id'         => $pse->getId(),
                    'ref'        => $pse->getRef(),
                    'quantity'   => $pse->getQuantity()
                ];
            }
        }

        return $this->jsonResponse(json_encode($result));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response|static
     * @Route("/update/country/request", name="_country_request", methods="POST")
     */
    public function updateCountryInRequest(RequestStack $requestStack)
    {
        $response = JsonResponse::create([], 200);
        try {
            $addressId = $this->getRequest()->request->get('address_id');
            if (null === $addressId) {
                throw new InvalidArgumentException(
                    $this->getTranslator()->trans(
                        "You must pass address_id",
                        [],
                        OrderCreation::MESSAGE_DOMAIN
                    )
                );
            }
            $address = AddressQuery::create()->findPk($addressId);
            if (null === $address) {
                throw new \Exception(
                    Translator::getInstance()->trans(
                        "Cannot find address with id %addressId",
                        ["%addressId" => $addressId],
                        OrderCreation::MESSAGE_DOMAIN
                    )
                );
            }
            $order = new Order();
            $order
                ->setCustomer()
                ->setChoosenDeliveryAddress($addressId);

            $requestStack->getCurrentRequest()->getSession()->set(
                "thelia.order",
                $order
            );

            $requestStack->getCurrentRequest()->getSession()->set(
                "thelia.customer_user",
                $address->getCustomer()
            );
        } catch (\Exception $e) {
            $response = JsonResponse::create(["error" => $e->getMessage()], 500);
        }
        return $response;
    }

    /**
     * @Route("/configure-redirects-payment", name="_set_redirects", methods="POST")
     */
    public function setRedirectsPayment()
    {
        $authFail = $this->checkAuth(AdminResources::MODULE, OrderCreation::MESSAGE_DOMAIN, AccessManager::CREATE);
        if ($authFail !== null) {
            return $authFail;
        }

        $configurationRPForm = $this->createForm(ConfigurationRedirectsPayementForm::getName());

        try {
            $form = $this->validateForm($configurationRPForm, "POST");

            $data = $form->getData();

            $modules = $data['order_creation_redirects_payment'];

            OrderCreationConfiguration::setlistPaymentModule($modules);

            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/OrderCreation'));
        } catch (FormValidationException $exception) {
            $error_msg = $this->createStandardFormValidationErrorMessage($exception);
        }

        $this->setupFormErrorContext(
            Translator::getInstance()->trans("OrderCreation configuration", [], OrderCreation::MESSAGE_DOMAIN),
            $error_msg,
            $configurationRPForm,
            $exception
        );


        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/OrderCreation'));
    }

    /**
     * @Route("/redirectable-payment/{moduleID}", name="_redirectable_payment_request", methods="GET")
     */
    public function isRedirectable($moduleID)
    {
        $modules = json_decode(OrderCreationConfiguration::getlistPaymentModule());

        if (in_array($moduleID, $modules)) {
            return $this->jsonResponse(json_encode(['test' => 1]));
        }

        return $this->jsonResponse(json_encode(['test' => 0]));
    }
}
