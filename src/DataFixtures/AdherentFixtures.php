<?php

namespace App\DataFixtures;

use App\Entity\Adherent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdherentFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $adherent = new Adherent();

        $adherent->setNumeroAdherent('ADH2026001');
        $adherent->setNom('GHEZ');
        $adherent->setPrenom('Cam');
        $adherent->setEmail('camghez77@gmail.com');
        $adherent->setLigue('Football Nice');
        $adherent->setPoste('Coach');
        $adherent->setMotDePasse(
            $this->hasher->hashPassword($adherent, 'KingInTheNorth2')
        );

        $manager->persist($adherent);
        $manager->flush();
    }
}
