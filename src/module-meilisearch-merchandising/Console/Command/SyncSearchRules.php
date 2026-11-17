<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Walkwizus\MeilisearchBase\Service\DynamicSearchRuleManager;
use Walkwizus\MeilisearchMerchandising\Service\CategoryPinRuleSync;

class SyncSearchRules extends Command
{
    /**
     * @param DynamicSearchRuleManager $ruleManager
     * @param CategoryPinRuleSync $categoryPinRuleSync
     * @param string|null $name
     */
    public function __construct(
        private readonly DynamicSearchRuleManager $ruleManager,
        private readonly CategoryPinRuleSync $categoryPinRuleSync,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('meilisearch:merchandising:sync-rules');
        $this->setDescription('Rebuilds the Meilisearch dynamic search rules of pinned category products');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $this->ruleManager->getStatus();

        if ($status !== DynamicSearchRuleManager::STATUS_ENABLED) {
            $output->writeln(sprintf(
                '<error>Dynamic search rules are %s. They require Meilisearch %s or later with the "%s" experimental feature enabled.</error>',
                $status,
                DynamicSearchRuleManager::MIN_VERSION,
                DynamicSearchRuleManager::EXPERIMENTAL_FEATURE
            ));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>%d category rule(s) synchronized.</info>', $this->categoryPinRuleSync->syncAll()));

        return Command::SUCCESS;
    }
}
