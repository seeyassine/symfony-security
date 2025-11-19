<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location/dummy')]
final class LocationDummyController extends AbstractController
{
    #[Route('/create', name: 'app_location_dummy')]
    public function create(EntityManagerInterface $entityManager): JsonResponse
    {
        $location = new Location();
        $location
            ->setName('FES')
            ->setCountryCode('FS')
            ->setLatitude(53.4285)
            ->setLongitude(14.5528)
        ;

        $entityManager->persist($location);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $location->getId(),
        ]);
    }

    #[Route('/edit')]
    public function edit(LocationRepository $locationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $location = $locationRepository->find(5);

        $location->setName('Tanger');

        $entityManager->flush();

        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }
}
