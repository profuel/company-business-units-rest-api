<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\OmsCheckoutConnector\Business;

use Generated\Zed\Ide\FactoryAutoCompletion\OmsCheckoutConnectorBusiness;
use SprykerEngine\Zed\Kernel\Business\AbstractBusinessDependencyContainer;
use SprykerFeature\Zed\OmsCheckoutConnector\OmsCheckoutConnectorConfig;

/**
 * @method OmsCheckoutConnectorBusiness getFactory()
 * @method OmsCheckoutConnectorConfig getConfig()
 */
class OmsCheckoutConnectorDependencyContainer extends AbstractBusinessDependencyContainer
{

    /**
     * @return OmsOrderHydratorInterface
     */
    public function createOmsOrderHydrator()
    {
        return $this->getFactory()->createOmsOrderHydrator();
    }
}
