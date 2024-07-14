<?php

namespace App\Command;

use App\Service\CurrencyManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:currency-upload', description: 'Upload currencies')]
class Currency extends Command
{
    private CurrencyManager $currencyManager;

    public function __construct(CurrencyManager $currencyManager)
    {
        parent::__construct();
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->currencyManager->uploadCurrencies();
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

}
