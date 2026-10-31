<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Console;

use Magento\Framework\Console\CommandListInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Setup\Application as SetupApplication;
use Magento\Setup\Console\Command\AdminUserCreateCommand;
use Magento\Setup\Console\Command\InstallCommand;
use Magento\Setup\Console\Command\InstallCommandFactory;
use Magento\Setup\Console\Command\InstallStoreConfigurationCommand;
use Magento\Setup\Model\ConfigModel;
use Magento\Setup\Model\InstallerFactory;
use Magento\Setup\Model\ObjectManagerProvider;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\LazyCommand;
use Walkwizus\MeilisearchBase\Setup\SearchConfigOptionsList;

class SetupCommandList implements CommandListInterface
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param SetupApplication $setupApplication
     * @param SearchConfigOptionsList $searchConfigOptionsList
     * @param InstallCommandFactory $installCommandFactory
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly SetupApplication $setupApplication,
        private readonly SearchConfigOptionsList $searchConfigOptionsList,
        private readonly InstallCommandFactory $installCommandFactory
    ) { }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        if (!class_exists(InstallCommand::class)) {
            return [];
        }

        $command = null;
        $command = new LazyCommand(
            InstallCommand::NAME,
            [],
            'Installs the Magento application',
            false,
            function () use (&$command): InstallCommand {
                return $this->createInstallCommand($command->getApplication());
            }
        );

        return [$command];
    }

    /**
     * @param Application $application
     * @return InstallCommand
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function createInstallCommand(Application $application): InstallCommand
    {
        // phpcs:ignore Magento2.Security.IncludeFile -- same bootstrap as Magento's own setup application
        $configuration = require BP . '/setup/config/application.config.php';
        $serviceManager = $this->setupApplication->bootstrap($configuration)->getServiceManager();
        $serviceManager->get(ObjectManagerProvider::class)->setObjectManager($this->objectManager);
        $serviceManager->setService(Application::class, $application);

        return $this->installCommandFactory->create([
            'installerFactory' => $serviceManager->get(InstallerFactory::class),
            'configModel' => $serviceManager->get(ConfigModel::class),
            'userConfig' => $serviceManager->get(InstallStoreConfigurationCommand::class),
            'adminUser' => $serviceManager->get(AdminUserCreateCommand::class),
            'searchConfigOptionsList' => $this->searchConfigOptionsList
        ]);
    }
}
