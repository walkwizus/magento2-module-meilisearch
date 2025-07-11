<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Console\Command;

use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Console\QuestionPerformer\YesNo;
use Magento\Framework\Encryption\Encryptor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class MasterKey extends Command
{
    /**
     * @param WriterInterface $writer
     * @param YesNo $yesNo
     * @param Encryptor $encryptor
     * @param string|null $name
     */
    public function __construct(
        private readonly WriterInterface $writer,
        private readonly YesNo $yesNo,
        private readonly Encryptor $encryptor,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('meilisearch:generate:master-key');
        $this->setDescription('Generate Meilisearch Master Key');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $key = bin2hex(random_bytes(16));
            $output->writeln('<info>Generated Master Key:</info> ' . $key);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return self::FAILURE;
        }

        $message = ['Do you want to save the key in Magento configuration? (y/n)?'];
        if ($this->yesNo->execute($message, $input, $output)) {
            $encrypt = $this->encryptor->encrypt($key);
            $this->writer->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_MASTER_KEY, $encrypt);
            $output->writeln('<info>Key saved in Magento config at "' . ServerSettings::MEILISEARCH_SERVER_SETTINGS_MASTER_KEY . '".</info>');
        }

        $output->writeln('<comment>You must restart the Meilisearch server with --master-key command-line option for the new master key to take effect.</comment>');
        $output->writeln('<info><href=https://www.meilisearch.com/docs/learn/security/basic_security>https://www.meilisearch.com/docs/learn/security/basic_security</></info>');

        return self::SUCCESS;
    }
}
