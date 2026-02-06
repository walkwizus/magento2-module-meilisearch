<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Console\Command;

use Symfony\Component\Console\Command\Command;
use Walkwizus\MeilisearchBase\Service\KeysManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestionFactory;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Console\QuestionPerformer\YesNo;
use Magento\Framework\Encryption\Encryptor;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class ApiKey extends Command
{
    /**
     * @param KeysManager $keysManager
     * @param WriterInterface $writer
     * @param YesNo $yesNo
     * @param Encryptor $encryptor
     * @param string|null $name
     */
    public function __construct(
        private readonly KeysManager $keysManager,
        private readonly WriterInterface $writer,
        private readonly YesNo $yesNo,
        private readonly Encryptor $encryptor,
        private readonly ChoiceQuestionFactory $choiceQuestionFactory,
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
        $keys = array_values($keysResult->getResults());

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

        if (empty($keys)) {
            return self::SUCCESS;
        }

        $message = ['Do you want to save one of these keys in Magento configuration? (y/n)?'];
        if (!$this->yesNo->execute($message, $input, $output)) {
            return self::SUCCESS;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $choices = [];
        foreach ($keys as $index => $key) {
            $name = (string)$key->getName();
            $label = $name !== '' ? $name : 'unnamed';
            $choices[$index] = sprintf('%s (%s)', $label, $key->getUid());
        }

        $keyQuestion = $this->choiceQuestionFactory->create([
            'question' => 'Select a key to save',
            'choices' => $choices,
            'default' => 0
        ]);
        $keyQuestion->setErrorMessage('Key %s is invalid.');
        $selectedLabel = $questionHelper->ask($input, $output, $keyQuestion);
        $selectedIndex = array_search($selectedLabel, $choices, true);

        if ($selectedIndex === false || !isset($keys[$selectedIndex])) {
            $output->writeln('<error>Unable to resolve the selected key.</error>');
            return self::FAILURE;
        }

        $targetOptions = [
            'admin' => 'Admin API Key (server side)',
            'client' => 'Client API Key (public search)',
        ];
        $targetQuestion = $this->choiceQuestionFactory->create([
            'question' => 'Save key as',
            'choices' => array_values($targetOptions),
            'default' => 0
        ]);
        $targetQuestion->setErrorMessage('Choice %s is invalid.');
        $targetLabel = $questionHelper->ask($input, $output, $targetQuestion);
        $targetKey = array_search($targetLabel, $targetOptions, true);

        $selectedKey = $keys[$selectedIndex]->getKey();
        if ($targetKey === 'admin') {
            $encrypted = $this->encryptor->encrypt($selectedKey);
            $this->writer->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_API_KEY, $encrypted);
            $output->writeln('<info>Key saved in Magento config at "' . ServerSettings::MEILISEARCH_SERVER_SETTINGS_API_KEY . '".</info>');
        } else {
            $this->writer->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_CLIENT_API_KEY, $selectedKey);
            $output->writeln('<info>Key saved in Magento config at "' . ServerSettings::MEILISEARCH_SERVER_SETTINGS_CLIENT_API_KEY . '".</info>');
        }

        return self::SUCCESS;
    }
}
