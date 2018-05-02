<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductBarcode\Business;

use Spryker\Service\Barcode\BarcodeServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\ProductBarcode\Business\ProductBarcodeFinder\ProductBarcodeFinder;
use Spryker\Zed\ProductBarcode\Business\ProductBarcodeFinder\ProductBarcodeFinderInterface;
use Spryker\Zed\ProductBarcode\Business\ProductBarcodeGenerator\ProductBarcodeGenerator;
use Spryker\Zed\ProductBarcode\Business\ProductBarcodeGenerator\ProductBarcodeGeneratorInterface;
use Spryker\Zed\ProductBarcode\Business\ProductSkuProvider\ProductSkuProvider;
use Spryker\Zed\ProductBarcode\Business\ProductSkuProvider\ProductSkuProviderInterface;
use Spryker\Zed\ProductBarcode\Dependency\Facade\ProductBarcodeToProductBridgeInterface;
use Spryker\Zed\ProductBarcode\ProductBarcodeDependencyProvider;

/**
 * @method \Spryker\Zed\ProductBarcode\ProductBarcodeConfig getConfig()
 * @method \Spryker\Zed\ProductBarcode\Persistence\ProductBarcodeQueryContainerInterface getQueryContainer()
 */
class ProductBarcodeBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Spryker\Zed\ProductBarcode\Business\ProductBarcodeGenerator\ProductBarcodeGeneratorInterface
     */
    public function createProductBarcodeGenerator(): ProductBarcodeGeneratorInterface
    {
        return new ProductBarcodeGenerator(
            $this->getBarcodeService(),
            $this->createProductSkuProvider()
        );
    }

    /**
     * @return \Spryker\Zed\ProductBarcode\Business\ProductSkuProvider\ProductSkuProviderInterface
     */
    public function createProductSkuProvider(): ProductSkuProviderInterface
    {
        return new ProductSkuProvider(
            $this->getProductFacade()
        );
    }

    /**
     * @return \Spryker\Zed\ProductBarcode\Business\ProductBarcodeFinder\ProductBarcodeFinderInterface
     */
    public function createProductBarcodeFinder(): ProductBarcodeFinderInterface
    {
        return new ProductBarcodeFinder();
    }

    /**
     * @return \Spryker\Zed\ProductBarcode\Dependency\Facade\ProductBarcodeToProductBridgeInterface
     */
    public function getProductFacade(): ProductBarcodeToProductBridgeInterface
    {
        return $this->getProvidedDependency(ProductBarcodeDependencyProvider::PRODUCT_FACADE);
    }

    /**
     * @return \Spryker\Service\Barcode\BarcodeServiceInterface
     */
    public function getBarcodeService(): BarcodeServiceInterface
    {
        return $this->getProvidedDependency(ProductBarcodeDependencyProvider::BARCODE_SERVICE);
    }
}
