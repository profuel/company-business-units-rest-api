<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductApi\Business\Model;

use Generated\Shared\Transfer\ApiDataTransfer;
use Generated\Shared\Transfer\ApiFilterTransfer;
use Generated\Shared\Transfer\ApiRequestTransfer;
use Generated\Shared\Transfer\ProductApiTransfer;
use Generated\Shared\Transfer\PropelQueryBuilderColumnTransfer;
use Generated\Shared\Transfer\PropelQueryBuilderTableTransfer;
use Orm\Zed\Product\Persistence\Map\SpyProductAbstractTableMap;
use Orm\Zed\Product\Persistence\SpyProductAbstract;
use Propel\Runtime\Map\TableMap;
use Spryker\Zed\Api\Business\Exception\EntityNotFoundException;
use Spryker\Zed\ProductApi\Business\Mapper\EntityMapperInterface;
use Spryker\Zed\ProductApi\Business\Mapper\TransferMapperInterface;
use Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToApiInterface;
use Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToApiQueryBuilderInterface;
use Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToPropelQueryBuilderInterface;
use Spryker\Zed\ProductApi\Persistence\ProductApiQueryContainerInterface;
use Spryker\Zed\PropelOrm\Business\Model\Formatter\AssociativeArrayFormatter;

class ProductApi implements ProductApiInterface
{

    /**
     * @var \Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToApiInterface
     */
    protected $apiQueryContainer;

    /**
     * @var \Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToApiQueryBuilderInterface
     */
    protected $apiQueryBuilderQueryContainer;

    /**
     * @var \Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToPropelQueryBuilderInterface
     */
    protected $propelQueryBuilderQueryContainer;

    /**
     * @var \Spryker\Zed\ProductApi\Persistence\ProductApiQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\ProductApi\Business\Mapper\EntityMapperInterface
     */
    protected $entityMapper;

    /**
     * @var \Spryker\Zed\ProductApi\Business\Mapper\TransferMapperInterface
     */
    protected $transferMapper;

    /**
     * @param \Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToApiInterface $apiQueryContainer
     * @param \Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToApiQueryBuilderInterface $apiQueryBuilderQueryContainer
     * @param \Spryker\Zed\ProductApi\Dependency\QueryContainer\ProductApiToPropelQueryBuilderInterface $propelQueryBuilderQueryContainer
     * @param \Spryker\Zed\ProductApi\Persistence\ProductApiQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\ProductApi\Business\Mapper\EntityMapperInterface $entityMapper
     * @param \Spryker\Zed\ProductApi\Business\Mapper\TransferMapperInterface $transferMapper
     */
    public function __construct(
        ProductApiToApiInterface $apiQueryContainer,
        ProductApiToApiQueryBuilderInterface $apiQueryBuilderQueryContainer,
        ProductApiToPropelQueryBuilderInterface $propelQueryBuilderQueryContainer,
        ProductApiQueryContainerInterface $queryContainer,
        EntityMapperInterface $entityMapper,
        TransferMapperInterface $transferMapper
    ) {
        $this->apiQueryContainer = $apiQueryContainer;
        $this->apiQueryBuilderQueryContainer = $apiQueryBuilderQueryContainer;
        $this->propelQueryBuilderQueryContainer = $propelQueryBuilderQueryContainer;
        $this->queryContainer = $queryContainer;
        $this->entityMapper = $entityMapper;
        $this->transferMapper = $transferMapper;
    }

    /**
     * @param \Generated\Shared\Transfer\ApiDataTransfer $apiDataTransfer
     *
     * @return \Generated\Shared\Transfer\ApiItemTransfer
     */
    public function add(ApiDataTransfer $apiDataTransfer)
    {
        $productEntity = $this->entityMapper->toEntity($apiDataTransfer->getData());
        $productApiTransfer = $this->persist($productEntity);

        return $this->apiQueryContainer->createApiItem($productApiTransfer);
    }

    /**
     * @param int $idProductAbstract
     * @param \Generated\Shared\Transfer\ApiFilterTransfer $apiFilterTransfer
     *
     * @return \Generated\Shared\Transfer\ApiItemTransfer
     */
    public function get($idProductAbstract, ApiFilterTransfer $apiFilterTransfer)
    {
        $productData = $this->getProductData($idProductAbstract, $apiFilterTransfer);
        $productTransfer = $this->transferMapper->toTransfer($productData);

        return $this->apiQueryContainer->createApiItem($productTransfer);
    }

    /**
     * @param int $idProductAbstract
     * @param \Generated\Shared\Transfer\ApiDataTransfer $apiDataTransfer
     *
     * @throws \Spryker\Zed\Api\Business\Exception\EntityNotFoundException
     *
     * @return \Generated\Shared\Transfer\ApiItemTransfer
     */
    public function update($idProductAbstract, ApiDataTransfer $apiDataTransfer)
    {
        $entityToUpdate = $this->queryContainer
            ->queryFind()
            ->filterByIdProductAbstract($idProductAbstract)
            ->findOne();

        if (!$entityToUpdate) {
            throw new EntityNotFoundException(sprintf('Product not found: %s', $idProductAbstract));
        }

        $data = (array)$apiDataTransfer->getData();
        $entityToUpdate->fromArray($data);

        $productApiTransfer = $this->persist($entityToUpdate);

        return $this->apiQueryContainer->createApiItem($productApiTransfer);
    }

    /**
     * @param int $idProductAbstract
     *
     * @return \Generated\Shared\Transfer\ApiItemTransfer
     */
    public function remove($idProductAbstract)
    {
        $deletedRows = $this->queryContainer
            ->queryRemove($idProductAbstract)
            ->delete();

        $productApiTransfer = new ProductApiTransfer();

        if ($deletedRows > 0) {
            $productApiTransfer->setIdProductAbstract($idProductAbstract);
        }

        return $this->apiQueryContainer->createApiItem($productApiTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ApiRequestTransfer $apiRequestTransfer
     *
     * @return \Generated\Shared\Transfer\ApiCollectionTransfer
     */
    public function find(ApiRequestTransfer $apiRequestTransfer)
    {
        $query = $this->buildQuery($apiRequestTransfer);

        $collection = $this->transferMapper->toTransferCollection(
            $query->find()->toArray()
        );

        return $this->apiQueryContainer->createApiCollection($collection);
    }

    /**
     * @param \Orm\Zed\Product\Persistence\SpyProductAbstract $entity
     *
     * @return \Generated\Shared\Transfer\ProductApiTransfer
     */
    protected function persist(SpyProductAbstract $entity)
    {
        $entity->save();

        return $this->transferMapper->toTransfer($entity->toArray());
    }

    /**
     * @param int $idProduct
     * @param \Generated\Shared\Transfer\ApiFilterTransfer $apiFilterTransfer
     *
     * @throws \Spryker\Zed\Api\Business\Exception\EntityNotFoundException
     *
     * @return array
     */
    protected function getProductData($idProduct, ApiFilterTransfer $apiFilterTransfer)
    {
        //TODO column filtering
        $productArray = (array)$this->queryContainer
            ->queryGet($idProduct)
            ->findOne()
            ->toArray();

        if (!$productArray) {
            throw new EntityNotFoundException(sprintf('Product not found: %s', $idProduct));
        }

        return $productArray;
    }

    /**
     * @param \Generated\Shared\Transfer\ApiRequestTransfer $apiRequestTransfer
     *
     * @return \Orm\Zed\Product\Persistence\SpyProductQuery|\Propel\Runtime\ActiveQuery\ModelCriteria
     */
    protected function buildQuery(ApiRequestTransfer $apiRequestTransfer)
    {
        $tableTransfer = $this->buildTable();
        $criteriaTransfer = $this->apiQueryBuilderQueryContainer->toPropelQueryBuilderCriteria($apiRequestTransfer);
        $criteriaTransfer->setTable($tableTransfer);

        $query = $this->queryContainer->queryFind();
        $query = $this->propelQueryBuilderQueryContainer->createQuery($query, $criteriaTransfer);
        $query->setFormatter(new AssociativeArrayFormatter());

        return $query;
    }

    protected function buildTable()
    {
        $tableTransfer = new PropelQueryBuilderTableTransfer();
        $tableTransfer->setName(SpyProductAbstractTableMap::TABLE_NAME);
        $tableColumns = SpyProductAbstractTableMap::getFieldNames(TableMap::TYPE_COLNAME);

        foreach ($tableColumns as $columnAlias) {
            $columnTransfer = new PropelQueryBuilderColumnTransfer();
            $columnTransfer->setName(SpyProductAbstractTableMap::TABLE_NAME . '.' . $columnAlias);
            $columnTransfer->setAlias($columnAlias);

            $tableTransfer->addColumn($columnTransfer);
        }

        return $tableTransfer;
    }

}
