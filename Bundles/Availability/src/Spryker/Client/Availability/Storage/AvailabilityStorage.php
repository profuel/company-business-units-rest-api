<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Availability\Storage;

use Generated\Shared\Transfer\StorageAvailabilityTransfer;
use Spryker\Client\Availability\Dependency\Client\AvailabilityToStorageInterface;
use Spryker\Client\Availability\Exception\ProductAvailabilityNotFoundException;
use Spryker\Shared\KeyBuilder\KeyBuilderInterface;

class AvailabilityStorage implements AvailabilityStorageInterface
{

    /**
     * @var \Spryker\Client\Storage\StorageClientInterface
     */
    private $storageClient;

    /**
     * @var \Spryker\Shared\KeyBuilder\KeyBuilderInterface
     */
    private $keyBuilder;

    /**
     * @var string
     */
    private $locale;

    /**
     * @param \Spryker\Client\Availability\Dependency\Client\AvailabilityToStorageInterface $storage
     * @param \Spryker\Shared\KeyBuilder\KeyBuilderInterface $keyBuilder
     * @param string $localeName
     */
    public function __construct(AvailabilityToStorageInterface $storage, KeyBuilderInterface $keyBuilder, $localeName)
    {
        $this->storageClient = $storage;
        $this->keyBuilder = $keyBuilder;
        $this->locale = $localeName;
    }

    /**
     * @param int $idProductAbstract
     *
     * @throws \Spryker\Client\Availability\Exception\ProductAvailabilityNotFoundException
     *
     * @return \Generated\Shared\Transfer\StorageAvailabilityTransfer
     */
    public function getProductAvailability($idProductAbstract)
    {
        $availability = $this->getProductAvailabilityFromStorage($idProductAbstract);

        if ($availability === null) {
            throw new ProductAvailabilityNotFoundException('Product availability not found for "%d" product abstract id', $idProductAbstract);
        }

        return $this->getMappedStorageAvailabilityTransferFromStorage($availability);
    }

    /**
     * @param int $idProductAbstract
     *
     * @return bool
     */
    public function hasProductAvailability($idProductAbstract)
    {
        $availability = $this->getProductAvailabilityFromStorage($idProductAbstract);

        return $availability !== null;
    }

    /**
     * @param int $idProductAbstract
     *
     * @return array|null
     */
    protected function getProductAvailabilityFromStorage($idProductAbstract)
    {
        $key = $this->keyBuilder->generateKey($idProductAbstract, $this->locale);
        $availability = $this->storageClient->get($key);

        return $availability;
    }

    /**
     * @param array $availability
     *
     * @return \Generated\Shared\Transfer\StorageAvailabilityTransfer
     */
    protected function getMappedStorageAvailabilityTransferFromStorage(array $availability)
    {
        $storageAvailabilityTransfer = new StorageAvailabilityTransfer();
        $storageAvailabilityTransfer->fromArray($availability, true);

        return $storageAvailabilityTransfer;
    }

}
