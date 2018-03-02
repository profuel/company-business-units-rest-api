<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\CompanyUser\Business;

use Spryker\Zed\CompanyUser\Business\Model\CompanyUser;
use Spryker\Zed\CompanyUser\Business\Model\CompanyUserInterface;
use Spryker\Zed\CompanyUser\Business\Model\CompanyUserPluginExecutor;
use Spryker\Zed\CompanyUser\Business\Model\CompanyUserPluginExecutorInterface;
use Spryker\Zed\CompanyUser\CompanyUserDependencyProvider;
use Spryker\Zed\CompanyUser\Dependency\Facade\CompanyUserToCustomerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Spryker\Zed\CompanyUser\Persistence\CompanyUserRepositoryInterface getRepository()
 * @method \Spryker\Zed\CompanyUser\Persistence\CompanyUserEntityManagerInterface getEntityManager()
 */
class CompanyUserBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Spryker\Zed\CompanyUser\Business\Model\CompanyUserInterface
     */
    public function createCompanyUser(): CompanyUserInterface
    {
        return new CompanyUser(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getCustomerFacade(),
            $this->createCompanyUserPluginExecutor()
        );
    }

    /**
     * @return \Spryker\Zed\CompanyUser\Business\Model\CompanyUserPluginExecutorInterface
     */
    protected function createCompanyUserPluginExecutor(): CompanyUserPluginExecutorInterface
    {
        return new CompanyUserPluginExecutor(
            $this->getCompanyUserPostSavePlugins(),
            $this->getCompanyUserHydrationPlugins()
        );
    }

    /**
     * @return \Spryker\Zed\CompanyUser\Dependency\Facade\CompanyUserToCustomerFacadeInterface
     */
    protected function getCustomerFacade(): CompanyUserToCustomerFacadeInterface
    {
        return $this->getProvidedDependency(CompanyUserDependencyProvider::FACADE_CUSTOMER);
    }

    /**
     * @return \Spryker\Zed\CompanyUser\Dependency\Plugin\CompanyUserPostSavePluginInterface[]
     */
    protected function getCompanyUserPostSavePlugins(): array
    {
        return $this->getProvidedDependency(CompanyUserDependencyProvider::PLUGINS_COMPANY_USER_POST_SAVE);
    }

    /**
     * @return \Spryker\Zed\CompanyUser\Dependency\Plugin\CompanyUserHydrationPluginInterface[]
     */
    protected function getCompanyUserHydrationPlugins(): array
    {
        return $this->getProvidedDependency(CompanyUserDependencyProvider::PLUGINS_COMPANY_USER_HYDRATE);
    }
}
