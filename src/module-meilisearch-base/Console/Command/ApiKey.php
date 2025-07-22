<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Console\Command;

use Symfony\Component\Console\Command\Command;
use Walkwizus\MeilisearchBase\Service\KeysManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiKey extends Command
{
    /**
     * @param KeysManager $keysManager
     * @param string|null $name
     */
    public function __construct(
        private readonly KeysManager $keysManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('meilisearch:keys');
        $this->setDescription('Shows API keys');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $keysResult = $this->keysManager->getKeys();
        $keys = $keysResult->getResults();

        $rows = [];

        foreach ($keys as $key) {
            $rows[] = [
                $key->getName(),
                $key->getUid(),
                $key->getKey(),
                implode(', ', $key->getActions()),
                implode(', ', $key->getIndexes()),
                $key->getExpiresAt() ?? 'never',
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'UID', 'Key', 'Actions', 'Indexes', 'Expires At'])
            ->setRows($rows);
        $table->render();

        return self::SUCCESS;
    }
}
