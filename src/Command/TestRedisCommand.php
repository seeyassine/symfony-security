<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-redis',
    description: 'Test Redis connection',
)]
class TestRedisCommand extends Command
{
    public function __construct(
        private CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $output->writeln('Testing Redis configuration...');
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing Redis configuration...');
        
        try {
            // Test 1: Écriture et lecture simple
            $io->section('Writing test data to Redis...');
            $this->cache->get('test_key', function() {
                return 'Hello from Redis!';
            });
            
            // Test 2: Lecture
            $io->section('Reading test data from Redis...');
            $value = $this->cache->get('test_key', function() { 
                return null; 
            });
            
            if ($value === 'Hello from Redis!') {
                $io->success('SUCCESS: Redis is working perfectly!');
                $io->success('Value retrieved: ' . $value);
            } else {
                $output->writeln('FAILED: Could not read the correct value');
            }
            
        } catch (\Exception $e) {
            $output->writeln('ERROR: ' . $e->getMessage());
            $output->writeln('');
            $output->writeln('Troubleshooting tips:');
            $output->writeln('   1. Is Redis installed?');
            $output->writeln('   2. Is Redis running? (Check services)');
            $output->writeln('   3. Check REDIS_URL in .env file');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
