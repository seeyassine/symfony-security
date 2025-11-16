<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demonstrate-pools')]
class DemonstratePoolsCommand extends Command
{
    public function __construct(
        private CacheItemPoolInterface $appCacheRedis,
        private CacheItemPoolInterface $doctrineResultCachePool,
        private CacheItemPoolInterface $doctrineSystemCachePool,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Démonstration des 3 Pools Redis');

        // 1. Pool Applicatif
        $io->section('1.Pool Applicatif (Base 0)');
        $this->appCacheRedis->save($this->appCacheRedis->getItem('app_data')->set('Donnée métier'));
        $io->writeln('Stocke: Données métier, réponses API, sessions');

        // 2. Pool Résultats Doctrine
        $io->section('2. Pool Résultats Doctrine (Base 1)');
        $this->doctrineResultCachePool->save(
            $this->doctrineResultCachePool->getItem('doctrine_result')->set('Résultat requête SQL')
        );
        $io->writeln('Stocke: Résultats de requêtes SELECT, jointures complexes');

        // 3. Pool Système Doctrine
        $io->section('3. Pool Système Doctrine (Base 2)');
        $this->doctrineSystemCachePool->save(
            $this->doctrineSystemCachePool->getItem('doctrine_metadata')->set('Métadonnées entités')
        );
        $io->writeln('Stocke: Structure des entités, requêtes DQL parsées');

        // Affichage dans Redis
        $io->section('Vérification dans Redis');
        $io->writeln('Ouvrez un nouveau terminal et exécutez:');
        $io->writeln('```bash');
        $io->writeln('redis-cli');
        $io->writeln('SELECT 0  # Pool applicatif');
        $io->writeln('KEYS "*"');
        $io->writeln('SELECT 1  # Pool résultats Doctrine');
        $io->writeln('KEYS "*"');
        $io->writeln('SELECT 2  # Pool système Doctrine');
        $io->writeln('KEYS "*"');
        $io->writeln('```');

        $io->success('Les 3 pools sont configurés et séparés!');

        return Command::SUCCESS;
    }


    // Quand un user est modifié
    public function updateUser(User $user): void
    {
        // 1. On invalide le cache applicatif
        $this->appCacheRedis->deleteItem('api_users_list');
        
        // 2. Mais le cache Doctrine des requêtes reste intact
        // Les autres APIs utilisant Doctrine restent rapides
    }
}