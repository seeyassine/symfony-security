<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;
use Redis;

#[AsCommand(name: 'app:monitor-performance')]
class MonitorPerformanceCommand extends Command
{
    public function __construct(
        private CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Tableau de Bord Performance Redis');

        // 1. Statistiques Redis
        $io->section('Statistiques Redis Server');
        
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        $info = $redis->info();
        
        $io->table([
            'Métrique', 'Valeur'
        ], [
            ['Mémoire utilisée', $info['used_memory_human'] ?? 'N/A'],
            ['Hit Rate', round(($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses'])) * 100, 2) . '%'],
            ['Connexions', $info['connected_clients'] ?? 'N/A'],
            ['Commandes/s', $info['instantaneous_ops_per_sec'] ?? 'N/A'],
        ]);

        // 2. Clés Redis par pool
        $io->section('Clés Redis par Pool');
        
        $poolStats = [];
        for ($i = 0; $i <= 2; $i++) {
            $redis->select($i);
            $keys = $redis->keys('*');
            $poolStats[] = [
                'Pool ' . $i,
                count($keys),
                implode(', ', array_slice($keys, 0, 5)) . (count($keys) > 5 ? '...' : '')
            ];
        }
        
        $io->table([
            'Pool', 'Nb Clés', 'Exemples'
        ], $poolStats);

        // 3. Test de performance
        $io->section('Test de Performance Cache');
        
        $iterations = 10;
        $timesWithoutCache = [];
        $timesWithCache = [];
        
        // Test sans cache
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cache->delete('performance_test');
            $this->cache->get('performance_test', function() {
                // Simulation traitement coûteux
                usleep(10000); // 10ms
                return 'data';
            });
            $timesWithoutCache[] = microtime(true);
        }
        
        // Test avec cache
        for ($i = 0; $i < $iterations; $i++) {
            $startReq = microtime(true);
            $this->cache->get('performance_test', function() {
                usleep(10000);
                return 'data';
            });
            $timesWithCache[] = microtime(true) - $startReq;
        }
        
        $avgWithoutCache = (array_sum($timesWithoutCache) / $iterations) * 1000;
        $avgWithCache = (array_sum($timesWithCache) / $iterations) * 1000;
        
        $io->table([
            'Scénario', 'Temps moyen', 'Amélioration'
        ], [
            [
                'Sans cache (1ère requête)',
                round($avgWithoutCache, 2) . 'ms',
                'Base'
            ],
            [
                'Avec cache (requêtes suivantes)',
                round($avgWithCache, 2) . 'ms',
                round($avgWithoutCache / $avgWithCache, 1) . 'x plus rapide'
            ]
        ]);

        $io->success('Monitoring terminé!');

        return Command::SUCCESS;
    }
}