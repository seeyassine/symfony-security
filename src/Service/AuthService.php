<?php

namespace App\Service;

use App\DTO\RegisterRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    public function register(RegisterRequest $registerRequest): User
    {
        $errors = $this->validator->validate($registerRequest);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $registerRequest->email]);
        if ($existingUser) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User();
        $user->setEmail($registerRequest->email);
        $user->setFirstname($registerRequest->firstname);
        $user->setLastname($registerRequest->lastname);
        $user->setRoles($registerRequest->roles);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $registerRequest->password)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}