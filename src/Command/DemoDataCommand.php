<?php

namespace App\Command;

use App\Repository\SiteRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo-data', description: 'Charge les données de démonstration si la base est vide.')]
class DemoDataCommand extends Command
{
    public function __construct(private readonly SiteRepository $sites)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->sites->count([]) > 0) {
            $io->success('La base contient déjà des données : rien à charger.');

            return Command::SUCCESS;
        }

        $fixtures = $this->getApplication()->find('doctrine:fixtures:load');
        $arguments = new ArrayInput([]);
        $arguments->setInteractive(false);

        return $fixtures->run($arguments, $output);
    }
}
