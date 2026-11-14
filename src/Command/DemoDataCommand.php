<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo-data', description: 'Charge les données de démonstration si elles ne sont pas déjà présentes.')]
class DemoDataCommand extends Command
{
    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Les comptes de démonstration sont créés avec les données : leur présence indique que la base est déjà initialisée.
        if ($this->users->count([]) > 0) {
            $io->success('Les données de démonstration sont déjà présentes : rien à charger.');

            return Command::SUCCESS;
        }

        $fixtures = $this->getApplication()->find('doctrine:fixtures:load');
        $arguments = new ArrayInput([]);
        $arguments->setInteractive(false);

        return $fixtures->run($arguments, $output);
    }
}
