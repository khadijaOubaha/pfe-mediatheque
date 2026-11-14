<?php

namespace App\Command;

use App\Enum\StatutImport;
use App\Service\ImportIsbnService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-isbn', description: "Crée ou complète des fiches du catalogue à partir d'ISBN (Open Library).")]
class ImportIsbnCommand extends Command
{
    public function __construct(private readonly ImportIsbnService $import)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('isbn', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Un ou plusieurs ISBN')
            ->addOption('fichier', 'f', InputOption::VALUE_REQUIRED, 'Fichier texte ou CSV : un ISBN par ligne (première colonne)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isbns = $input->getArgument('isbn');

        if (null !== $input->getOption('fichier')) {
            $chemin = (string) $input->getOption('fichier');
            if (!is_readable($chemin)) {
                $io->error(sprintf('Fichier illisible : %s', $chemin));

                return Command::FAILURE;
            }
            foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $ligne) {
                $colonne = trim((string) (preg_split('/[;,\t]/', $ligne)[0] ?? ''));
                if ('' !== $colonne) {
                    $isbns[] = $colonne;
                }
            }
        }

        if ([] === $isbns) {
            $io->error('Indiquez au moins un ISBN, ou un fichier avec --fichier.');

            return Command::FAILURE;
        }

        $lignes = [];
        $compteurs = [];
        foreach ($this->import->importerLot($isbns) as $resultat) {
            $lignes[] = [$resultat->isbn, $resultat->statut->value, $resultat->message];
            foreach ($resultat->differences as $difference) {
                $lignes[] = ['', '', '  ⚠ '.$difference];
            }
            $compteurs[$resultat->statut->value] = ($compteurs[$resultat->statut->value] ?? 0) + 1;
        }

        $io->table(['ISBN', 'Statut', 'Détail'], $lignes);
        $io->writeln(implode(' · ', array_map(static fn (string $s, int $n): string => sprintf('%s : %d', $s, $n), array_keys($compteurs), $compteurs)));

        return ($compteurs[StatutImport::ECHEC->value] ?? 0) > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
