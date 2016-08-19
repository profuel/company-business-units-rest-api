<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Functional\Spryker\Zed\Checkout\Business;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use Orm\Zed\Country\Persistence\SpyCountry;
use Orm\Zed\Customer\Persistence\SpyCustomer;
use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Orm\Zed\Product\Persistence\SpyProduct;
use Orm\Zed\Product\Persistence\SpyProductAbstract;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Orm\Zed\Stock\Persistence\SpyStock;
use Orm\Zed\Stock\Persistence\SpyStockProduct;
use Spryker\Shared\Checkout\CheckoutConstants;
use Spryker\Shared\Oms\OmsConstants;
use Spryker\Zed\Availability\Communication\Plugin\ProductsAvailableCheckoutPreConditionPlugin;
use Spryker\Zed\Checkout\Business\CheckoutBusinessFactory;
use Spryker\Zed\Checkout\Business\CheckoutFacade;
use Spryker\Zed\Checkout\CheckoutDependencyProvider;
use Spryker\Zed\Checkout\Dependency\Facade\CheckoutToOmsBridge;
use Spryker\Zed\Customer\Communication\Plugin\CustomerPreConditionCheckerPlugin;
use Spryker\Zed\Customer\Communication\Plugin\OrderCustomerSavePlugin;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Sales\Business\SalesBusinessFactory;
use Spryker\Zed\Sales\Business\SalesFacade;
use Spryker\Zed\Sales\Communication\Plugin\SalesOrderSaverPlugin;
use Spryker\Zed\Sales\Dependency\Facade\SalesToCountryBridge;
use Spryker\Zed\Sales\Dependency\Facade\SalesToOmsBridge;
use Spryker\Zed\Sales\Dependency\Facade\SalesToSequenceNumberBridge;
use Spryker\Zed\Sales\SalesConfig;
use Spryker\Zed\Sales\SalesDependencyProvider;

/**
 * @group Functional
 * @group Spryker
 * @group Zed
 * @group Checkout
 * @group Business
 * @group CheckoutFacadeTest
 */
class CheckoutFacadeTest extends Test
{

    /**
     * @var \Spryker\Zed\Checkout\Business\CheckoutFacade
     */
    protected $checkoutFacade;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->checkoutFacade = new CheckoutFacade();

        $factoryMock = $this->getFactory();
        $this->checkoutFacade->setFactory($factoryMock);
    }

    /**
     * @todo move this code to customer checkout connector, registration can only happen if we have
     * already installed customer bundle
     *
     * @return void
     */
    public function testRegistrationIsTriggeredOnNewNonGuestCustomer()
    {
        $quoteTransfer = $this->getBaseQuoteTransfer();

        $result = $this->checkoutFacade->placeOrder($quoteTransfer);

        $this->assertTrue($result->getIsSuccess());
        $this->assertEquals(0, count($result->getErrors()));

        $customerQuery = SpyCustomerQuery::create()->filterByEmail($quoteTransfer->getCustomer()->getEmail());
        $this->assertEquals(1, $customerQuery->count());
    }

    /**
     * @todo move this code to customer checkout connector, registration can only happen if we have
     * already installed customer bundle
     *
     * @return void
     */
    public function testRegistrationDoesNotCreateACustomerIfGuest()
    {
        $quoteTransfer = $this->getBaseQuoteTransfer();
        $quoteTransfer->getCustomer()->setIsGuest(true);

        $result = $this->checkoutFacade->placeOrder($quoteTransfer);

        $this->assertTrue($result->getIsSuccess());
        $this->assertEquals(0, count($result->getErrors()));

        $customerQuery = SpyCustomerQuery::create()->filterByEmail($quoteTransfer->getCustomer()->getEmail());
        $this->assertEquals(0, $customerQuery->count());
    }

    /**
     * @return void
     */
    public function testCheckoutResponseContainsErrorIfCustomerAlreadyRegistered()
    {
        $customer = new SpyCustomer();
        $customer
            ->setCustomerReference('TestCustomer1')
            ->setEmail('max@mustermann.de')
            ->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setPassword('MyPass')
            ->save();

        $quoteTransfer = $this->getBaseQuoteTransfer();

        $result = $this->checkoutFacade->placeOrder($quoteTransfer);

        $this->assertFalse($result->getIsSuccess());
        $this->assertEquals(1, count($result->getErrors()));
        $this->assertEquals(CheckoutConstants::ERROR_CODE_CUSTOMER_ALREADY_REGISTERED, $result->getErrors()[0]->getErrorCode());
    }

    /**
     * @return void
     */
    public function testCheckoutCreatesOrderItems()
    {
        $quoteTransfer = $this->getBaseQuoteTransfer();

        $result = $this->checkoutFacade->placeOrder($quoteTransfer);

        $this->assertTrue($result->getIsSuccess());
        $this->assertEquals(0, count($result->getErrors()));

        $orderItem1Query = SpySalesOrderItemQuery::create()
            ->filterBySku('OSB1337');
        $orderItem2Query = SpySalesOrderItemQuery::create()
            ->filterBySku('OSB1338');

        $this->assertEquals(1, $orderItem1Query->count());
        $this->assertEquals(1, $orderItem2Query->count());
    }

    /**
     * @return void
     */
    public function testCheckoutResponseContainsErrorIfStockNotSufficient()
    {
        $quoteTransfer = $this->getBaseQuoteTransfer();
        $productAbstract1 = new SpyProductAbstract();
        $productAbstract1
            ->setSku('AOSB1339')
            ->setAttributes('{}');
        $productConcrete1 = new SpyProduct();
        $productConcrete1
            ->setSku('OSB1339')
            ->setAttributes('{}')
            ->setSpyProductAbstract($productAbstract1)
            ->save();

        $stock = new SpyStock();
        $stock
            ->setName('Stock2');

        $stock1 = new SpyStockProduct();
        $stock1
            ->setQuantity(1)
            ->setStock($stock)
            ->setSpyProduct($productConcrete1)
            ->save();

        $item = new ItemTransfer();
        $item
            ->setSku('OSB1339')
            ->setQuantity(2)
            ->setUnitGrossPrice(3000)
            ->setSumGrossPrice(6000);

        $quoteTransfer->addItem($item);

        $result = $this->checkoutFacade->placeOrder($quoteTransfer);

        $this->assertFalse($result->getIsSuccess());
        $this->assertEquals(1, count($result->getErrors()));
        $this->assertEquals(CheckoutConstants::ERROR_CODE_PRODUCT_UNAVAILABLE, $result->getErrors()[0]->getErrorCode());
    }

    /**
     * @return void
     */
    public function testCheckoutTriggersStateMachine()
    {
        $quoteTransfer = $this->getBaseQuoteTransfer();

        $this->checkoutFacade->placeOrder($quoteTransfer);

        $orderItem1Query = SpySalesOrderItemQuery::create()
            ->filterBySku('OSB1337');

        $orderItem2Query = SpySalesOrderItemQuery::create()
            ->filterBySku('OSB1338');

        $orderItem1 = $orderItem1Query->findOne();
        $orderItem2 = $orderItem2Query->findOne();

        $this->assertNotNull($orderItem1);
        $this->assertNotNull($orderItem2);

        $this->assertNotEquals(OmsConstants::INITIAL_STATUS, $orderItem1->getState()->getName());
        $this->assertEquals('waiting for payment', $orderItem2->getState()->getName());
    }

    /**
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function getBaseQuoteTransfer()
    {
        $quoteTransfer = new QuoteTransfer();

        $country = new SpyCountry();
        $country
            ->setIso2Code('xi')
            ->save();

        $productAbstract1 = new SpyProductAbstract();
        $productAbstract1
            ->setSku('AOSB1337')
            ->setAttributes('{}');
        $productConcrete1 = new SpyProduct();
        $productConcrete1
            ->setSku('OSB1337')
            ->setAttributes('{}')
            ->setSpyProductAbstract($productAbstract1)
            ->save();

        $productAbstract2 = new SpyProductAbstract();
        $productAbstract2
            ->setSku('AOSB1338')
            ->setAttributes('{}');
        $productConcrete2 = new SpyProduct();
        $productConcrete2
            ->setSku('OSB1338')
            ->setSpyProductAbstract($productAbstract2)
            ->setAttributes('{}')
            ->save();

        $stock = new SpyStock();
        $stock
            ->setName('testStock');

        $stock1 = new SpyStockProduct();
        $stock1
            ->setQuantity(1)
            ->setStock($stock)
            ->setSpyProduct($productConcrete1)
            ->save();

        $stock2 = new SpyStockProduct();
        $stock2
            ->setQuantity(1)
            ->setStock($stock)
            ->setSpyProduct($productConcrete2)
            ->save();

        $item1 = new ItemTransfer();
        $item1
            ->setSku('OSB1337')
            ->setQuantity(1)
            ->setUnitGrossPrice(3000)
            ->setName('Product1');

        $item2 = new ItemTransfer();
        $item2
            ->setSku('OSB1338')
            ->setQuantity(1)
            ->setUnitGrossPrice(4000)
            ->setName('Product2');

        $quoteTransfer->addItem($item1);
        $quoteTransfer->addItem($item2);

        $totals = new TotalsTransfer();
        $totals
            ->setGrandTotal(1000)
            ->setSubtotal(500);

        $quoteTransfer->setTotals($totals);

        $billingAddress = new AddressTransfer();
        $shippingAddress = new AddressTransfer();

        $billingAddress
            ->setIso2Code('xi')
            ->setEmail('max@mustermann.de')
            ->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setAddress1('Straße')
            ->setAddress2('82')
            ->setZipCode('12345')
            ->setCity('Entenhausen');
        $shippingAddress
            ->setIso2Code('xi')
            ->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setEmail('max@mustermann.de')
            ->setAddress1('Straße')
            ->setAddress2('84')
            ->setZipCode('12346')
            ->setCity('Entenhausen2');

        $quoteTransfer->setBillingAddress($billingAddress);
        $quoteTransfer->setShippingAddress($shippingAddress);

        $customerTransfer = new CustomerTransfer();

        $customerTransfer
            ->setIsGuest(false)
            ->setEmail('max@mustermann.de');

        $quoteTransfer->setCustomer($customerTransfer);

        $shipment = new ShipmentTransfer();
        $shipment->setMethod(new ShipmentMethodTransfer());

        $quoteTransfer->setShipment($shipment);

        $paymentTransfer = new PaymentTransfer();
        $paymentTransfer->setPaymentSelection('no_payment');
        $quoteTransfer->setPayment($paymentTransfer);

        return $quoteTransfer;
    }

    /**
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function getContainer()
    {
        $container = new Container();

        $container[CheckoutDependencyProvider::CHECKOUT_PRE_CONDITIONS] = function (Container $container) {
            return [
                new CustomerPreConditionCheckerPlugin(),
                new ProductsAvailableCheckoutPreConditionPlugin(),
            ];
        };

        $container[CheckoutDependencyProvider::CHECKOUT_ORDER_SAVERS] = function (Container $container) {

            $salesOrderSaverPlugin = $this->createOrderSaverPlugin();

            return [
                $salesOrderSaverPlugin,
                new OrderCustomerSavePlugin(),
            ];
        };

        $container[CheckoutDependencyProvider::CHECKOUT_POST_HOOKS] = function (Container $container) {
            return [];
        };

        $container[CheckoutDependencyProvider::FACADE_OMS] = function (Container $container) {
            return new CheckoutToOmsBridge($container->getLocator()->oms()->facade());
        };

        return $container;
    }

    /**
     * @return \Spryker\Zed\Checkout\Business\CheckoutBusinessFactory
     */
    protected function getFactory()
    {
        $container = $this->getContainer();

        $factory = new CheckoutBusinessFactory();
        $factory->setContainer($container);

        return $factory;
    }

    /**
     * @return \Spryker\Zed\Sales\Communication\Plugin\SalesOrderSaverPlugin
     */
    protected function createOrderSaverPlugin()
    {
        $salesOrderSaverPlugin = new SalesOrderSaverPlugin();

        $salesConfigMock = $this->getMock(SalesConfig::class, ['determineProcessForOrderItem']);
        $salesConfigMock->method('determineProcessForOrderItem')->willReturn('Nopayment01');

        $salesBusinessFactoryMock = $this->getMock(SalesBusinessFactory::class, ['getConfig']);
        $salesBusinessFactoryMock->method('getConfig')->willReturn($salesConfigMock);

        $container = new Container();
        $container[SalesDependencyProvider::FACADE_COUNTRY] = function (Container $container) {
              return new SalesToCountryBridge($container->getLocator()->country()->facade());
        };
        $container[SalesDependencyProvider::FACADE_OMS] = function (Container $container) {
            return new SalesToOmsBridge($container->getLocator()->oms()->facade());
        };
        $container[SalesDependencyProvider::FACADE_SEQUENCE_NUMBER] = function (Container $container) {
            return new SalesToSequenceNumberBridge($container->getLocator()->sequenceNumber()->facade());
        };

        $salesBusinessFactoryMock->setContainer($container);

        $salesFacade = new SalesFacade();
        $salesFacade->setFactory($salesBusinessFactoryMock);

        $salesOrderSaverPlugin->setFacade($salesFacade);

        return $salesOrderSaverPlugin;
    }

}
