<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Currency\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Spryker\Zed\Currency\Business\CurrencyBusinessFactory getFactory()
 */
class CurrencyFacade extends AbstractFacade implements CurrencyFacadeInterface
{

    /**
     * Specification:
     * - Return CurrencyTransfer Object for given iso code
     *
     * @api
     *
     * @param string $isoCode
     *
     * @return \Generated\Shared\Transfer\CurrencyTransfer
     */
    public function fromIsoCode($isoCode)
    {
        return $this->getFactory()->createCurrencyBuilder()->fromIsoCode($isoCode);
    }

    /**
     * Specification:
     * - Return CurrencyTransfer Object for current iso code
     *
     * @api
     *
     * @return \Generated\Shared\Transfer\CurrencyTransfer
     */
    public function getCurrent()
    {
        return $this->getFactory()->createCurrencyBuilder()->getCurrent();
    }

}