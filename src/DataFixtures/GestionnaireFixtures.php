<?php

namespace App\DataFixtures;

use App\Entity\Gestionnaires; 
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GestionnaireFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. On crée le compte Super Admin
        $superAdmin = new Gestionnaires();
        
        $superAdmin->setIdentifiant('CGH'); 

        $superAdmin->setNom('GHEZ');

        $superAdmin->setPrenom('Cam');

        $superAdmin->setEmail('camghez77@gmail.com');
        
        $superAdmin->setRoles(['ROLE_SUPER_ADMIN']);

        $motDePasseCrypte = $this->hasher->hashPassword($superAdmin, 'KingInTheNorth1');
        $superAdmin->setPassword($motDePasseCrypte);

        // 3. On prépare l'enregistrement
        $manager->persist($superAdmin);

        // 4. On envoie dans la base de données MySQL
        $manager->flush();
    }
}