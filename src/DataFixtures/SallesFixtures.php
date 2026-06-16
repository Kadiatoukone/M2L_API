<?php

namespace App\DataFixtures;

use App\Entity\TypeSalle;
use App\Entity\Salles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SallesFixtures extends Fixture
{
       public function load(ObjectManager $manager): void
       {
           // ── Types de salle ─────────────────────────────────────────────────────
       
              $typeFoot = new TypeSalle();
              $typeFoot->setLibelle('Football')->setCategorie(TypeSalle::CATEGORIE_SPORT);
              $manager->persist($typeFoot);
       
              $typeBasket = new TypeSalle();
              $typeBasket->setLibelle('Basketball')->setCategorie(TypeSalle::CATEGORIE_SPORT);
              $manager->persist($typeBasket);
       
              $typeConference = new TypeSalle();
              $typeConference->setLibelle('Conférence')->setCategorie(TypeSalle::CATEGORIE_EVENEMENT);
              $manager->persist($typeConference);

              $typeReunion = new TypeSalle();
              $typeReunion->setLibelle('Réunion')->setCategorie(TypeSalle::CATEGORIE_EVENEMENT);
              $manager->persist($typeReunion);

           // ── Salles ─────────────────────────────────────────────────────────────
              $salle1 = new Salles();
              $salle1->setNom('Gymnase Jean Moulin')
                     ->setAdresse('12 Rue Jean Moulin')
                     ->setVille('Nancy')
                     ->setCapacite(200)
                     ->setDescription('Grand gymnase polyvalent équipé pour le football en salle.')
                     ->setPhoto(null)
                     ->setTypeSalle($typeFoot);
              $manager->persist($salle1);
       
              $salle2 = new Salles();
              $salle2->setNom('Salle Omnisports Sud')
                     ->setAdresse('45 Avenue du Stade')
                     ->setVille('Nancy')
                     ->setCapacite(150)
                     ->setDescription('Salle équipée de parquet sportif pour le basketball.')
                     ->setPhoto(null)
                     ->setTypeSalle($typeBasket);
              $manager->persist($salle2);
       
              $salle3 = new Salles();
              $salle3->setNom('Salle Pasteur')
                     ->setAdresse('3 Rue Pasteur')
                     ->setVille('Nancy')
                     ->setCapacite(80)
                     ->setDescription('Salle de conférence moderne avec vidéoprojecteur et climatisation.')
                     ->setPhoto(null)
                     ->setTypeSalle($typeConference);
              $manager->persist($salle3);
       
              $salle4 = new Salles();
              $salle4->setNom('Espace Réunion Ligues')
                     ->setAdresse('1 Place des Ligues')
                     ->setVille('Nancy')
                     ->setCapacite(30)
                     ->setDescription('Petite salle de réunion idéale pour les comités et assemblées.')
                     ->setPhoto(null)
                     ->setTypeSalle($typeReunion);
              $manager->persist($salle4);
       
              $manager->flush();
       }   
}
