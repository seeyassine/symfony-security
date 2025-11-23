<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

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
        int $id
    ): JsonResponse{
        $location = $locationRepository->find($id);
        $locationRepository->remove($location, true);

        return new JsonResponse(null);
    }

    #[Route('/show/{id}')]
    public function show(
        LocationRepository $locationRepository,
        int $id,
    ): JsonResponse
    {

        $location = $locationRepository->find($id);

        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }

    #[Route('/showby/{name}')]
    public function showby(
        LocationRepository $locationRepository,
        string $name,
    ): JsonResponse
    {

        $location = $locationRepository->findOneBy([
            'name' => $name,
        ]);

        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }


    #[Route('/show_country/{country}')]
    public function show_country(
        LocationRepository $locationRepository,
        string $country,
    ): JsonResponse
    {

        $locations = $locationRepository->findBy([
            'countryCode' => $country,
        ],  [
            'name' => 'DESC',  // ASC
        ]);

        $json = [];

        foreach($locations as $location){
            $json[] = [
                'id'=> $location->getId(),
                'name'=> $location->getName()
            ];
        }

        return new JsonResponse($json);
    }



    #[Route('/show_countryC/{country}')]
    public function show_countryC(
        LocationRepository $locationRepository,
        string $country,
    ): JsonResponse
    {

        $locations = $locationRepository->findByCountryCode($country);

        $json = [];

        foreach($locations as $location){
            $json[] = [
                'id'=> $location->getId(),
                'name'=> $location->getName()
            ];
        }

        return new JsonResponse($json);
    }


    #[Route('/show_name/{name}')]
    public function show_name(
        LocationRepository $locationRepository,
        string $name,
    ): JsonResponse
    {

        $location = $locationRepository->findOneByName($name);

        if(!$location){
            throw $this->createNotFoundException();
        }

        $json = [
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ];     

        return new JsonResponse($json);
    }


    #[Route('/list')]
    public function list(
        LocationRepository $locationRepository,
    ): JsonResponse
    {

        $locations = $locationRepository->findAll();

        $json = [];

        foreach($locations as $location){
            $json[] = [
                'id'=> $location->getId(),
                'name'=> $location->getName()
            ];
        }

        return new JsonResponse($json);
    }

    
    #[Route('/show_by_id_Attribute/{id}')]
    public function show_id_Att(
        Location $location,
    ): JsonResponse
    {
        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }

    #[Route('/show_name_Attribute/{name}')]   //EntityValueResolver
    public function show_name_At(
        Location $location,
    ): JsonResponse
    {
        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }

    // fetch entity by name used MapEntity Attribute
    #[Route('/show_name_AttributeT/{location_name}')]   //EntityValueResolver
    public function show_name_(
        #[MapEntity(mapping:['location_name' => 'name'])]
        Location $location,
    ): JsonResponse
    {
        return new JsonResponse([
            'id'=> $location->getId(),
            'name'=> $location->getName()
        ]);
    }
}
