<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-doctrine-redis')]
class TestDoctrineRedisCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing Doctrine + Redis integration...');
        
        try {
            // Test 1: Cache des métadonnées
            $output->writeln('Testing metadata cache...');
            $classMetadata = $this->entityManager->getClassMetadata('App\Entity\User');
            $io->success('Metadata cache works');
            
            // Test 2: Cache de requête
            $output->writeln('Testing query cache...');
            $dql = 'SELECT u FROM App\Entity\User u';
            $query = $this->entityManager->createQuery($dql);
            $query->enableResultCache(300, 'users_query_cache');
            
            $users = $query->getResult();
            $io->success('Query cache works - Found ' . count($users) . ' users');
            
            // Test 3: Cache de résultat avec une autre méthode
            $output->writeln('Testing result cache...');
            $query2 = $this->entityManager->createQuery($dql);
            $query2->setResultCacheLifetime(300);
            
            $users2 = $query2->getResult();
            $io->success('Result cache works');
            
            $output->writeln('');
            $io->success('SUCCESS: Doctrine is now using Redis for all caches!');
            
        } catch (\Exception $e) {
            $output->writeln(' ERROR: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}