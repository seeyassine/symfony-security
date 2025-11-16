<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/clients')]
class ClientApiController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'api_client_create', methods: ['POST'])]
    public function createClient(
        Request $request,
        EntityManagerInterface $entityManager,
        CacheInterface $cache
    ): JsonResponse {
        $startTime = microtime(true);
        
        $data = json_decode($request->getContent(), true);
        
        $client = new Client();
        $client->setNom($data['nom'] ?? '');
        $client->setEmail($data['email'] ?? '');
        $client->setTelephone($data['telephone'] ?? '');
        $client->setAdresse($data['adresse'] ?? '');
        
        $entityManager->persist($client);
        $entityManager->flush();
        
        // Invalider le cache liste après création
        $cache->delete('clients_list_1_10_');
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        $this->logger->info('Client créé - Cache invalidé', [
            'client_id' => $client->getId(),
            'execution_time_ms' => $executionTime
        ]);
        
        return $this->json([
            'message' => 'Client créé avec succès',
            'client_id' => $client->getId(),
            'performance' => [
                'execution_time_ms' => $executionTime
            ]
        ], 201);
    }


    #[Route('', name: 'api_clients_list', methods: ['GET'])]
    public function listClients(
        Request $request,
        ClientRepository $clientRepository,
        CacheInterface $cache
    ): JsonResponse {
        $startTime = microtime(true);
        
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $search = $request->query->get('search', '');
        
        $cacheKey = sprintf('clients_list_%s_%s_%s', $page, $limit, md5($search));
        
        // Stocker les données brutes
        $clientsData = $cache->get($cacheKey, function (ItemInterface $item) use ($clientRepository, $page, $limit, $search) {
            $item->expiresAfter(300); // 5 minutes
            
            $this->logger->info('CACHE MISS - Chargement depuis la BDD', [
                'cache_key' => $item->getKey(),
                'search' => $search
            ]);
            
            $clients = $clientRepository->findBySearch($search, $page, $limit);
            
            // Retourner seulement les données, pas les métadonnées
            return [
                'clients' => array_map(function(Client $client) {
                    return [
                        'id' => $client->getId(),
                        'nom' => $client->getNom(),
                        'email' => $client->getEmail(),
                        'telephone' => $client->getTelephone(),
                        'adresse' => $client->getAdresse(),
                    ];
                }, $clients),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($clients)
                ]
            ];
        });
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        // Déterminer si le cache a été utilisé
        $cacheUsed = $executionTime < 50; // Si < 50ms, très probablement du cache
        
        // Log de performance
        $this->logPerformance($cacheKey, $executionTime, $cacheUsed ? 'redis_cache' : 'database');
        
        // Construction de la réponse
        $response = [
            'source' => $cacheUsed ? 'redis_cache' : 'database',
            'clients' => $clientsData['clients'],
            'pagination' => $clientsData['pagination'],
            'performance' => [
                'execution_time_ms' => $executionTime,
                'cache_used' => $cacheUsed,
                'cache_key' => $cacheKey
            ]
        ];
        
        return $this->json($response);
    }


    #[Route('/{id}', name: 'api_client_show', methods: ['GET'])]
    public function showClient(
        int $id,
        ClientRepository $clientRepository,
        CacheInterface $cache
    ): JsonResponse {
        $startTime = microtime(true);
        
        $cacheKey = "client_details_{$id}";
        
        $data = $cache->get($cacheKey, function (ItemInterface $item) use ($clientRepository, $id, $startTime) {
            $item->expiresAfter(600); // 10 minutes
            // $item->tag(["client_{$id}"]); // Tag pour invalidation
            
            $this->logger->info('CACHE MISS - Chargement client depuis BDD', [
                'client_id' => $id,
                'cache_key' => $item->getKey()
            ]);
            
            $client = $clientRepository->find($id);
            
            if (!$client) {
                return [
                    'source' => 'database',
                    'error' => 'Client non trouvé'
                ];
            }
            
            return [
                'source' => 'database',
                'client' => [
                    'id' => $client->getId(),
                    'nom' => $client->getNom(),
                    'email' => $client->getEmail(),
                    'telephone' => $client->getTelephone(),
                    'adresse' => $client->getAdresse(),
                ]
            ];
        });
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        $this->logPerformance($cacheKey, $executionTime, $data['source']);
        
        if (isset($data['error'])) {
            return $this->json($data, 404);
        }
        
        $responseData = array_merge($data, [
            'performance' => [
                'execution_time_ms' => $executionTime,
                'cache_used' => $data['source'] === 'redis_cache',
                'cache_key' => $cacheKey
            ]
        ]);
        
        return $this->json($responseData);
    }

    
    private function logPerformance(string $cacheKey, float $executionTime, string $source): void
    {
        $this->logger->info('Performance API', [
            'cache_key' => $cacheKey,
            'execution_time_ms' => $executionTime,
            'source' => $source,
            'cache_hit' => $source === 'redis_cache'
        ]);
    }
    
}