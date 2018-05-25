<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Merchant\Persistence;

use Generated\Shared\Transfer\MerchantTransfer;

interface MerchantRepositoryInterface
{
    /**
     * Specification:
     * - Returns a MerchantTransfer by merchant id.
     * - Throws an exception in case a record is not found.
     *
     * @api
     *
     * @param int $idMerchant
     *
     * @return \Generated\Shared\Transfer\MerchantTransfer
     */
    public function getMerchantById(int $idMerchant): MerchantTransfer;

    /**
     * Specification:
     * - Checks whether merchant key already exists.
     *
     * @api
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasKey(string $key): bool;
}
