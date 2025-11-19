<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Json;

#[Route('/location/dummy')]
final class LocationDummyController extends AbstractController
{
    #[Route('/create', name: 'app_location_dummy')]
    public function create(LocationRepository $locationRepository): JsonResponse
    {
        $location = new Location();
        $location
            ->setName('FES')
            ->setCountryCode('FS')
            ->setLatitude(53.4285)
            ->setLongitude(14.5528)
        ;

        $locationRepository->save($location, true);

        return new JsonResponse([
            'id' => $location->getId(),
        ]);
    }

    #[Route('/edit')]
    public function edit(LocationRepository $locationRepository): JsonResponse
    {
        $location = $locationRepository->find(6);

        $location->setName('Tanger');

        $locationRepository->save($location, true);

        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }

    #[Route('/remove/{id}')]
    public function remove(
        LocationRepository $locationRepository,
        EntityManagerInterface $entityManager,
        int $id
    ): JsonResponse{
        $location = $locationRepository->find($id);
        $entityManager->remove($location);
        $entityManager->flush();

        return new JsonResponse(null);
    }
}
