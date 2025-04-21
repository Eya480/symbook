<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Faker\Factory;
use App\Entity\Livres;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class LivresFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $names = ['Roman', 'C++', 'Base de donnée', 'Histoire', 'Cuisine'];
        for ($j = 1; $j <= 5; $j++) {
            $cat = new Categories();

            $cat->setLibelle($names[$j - 1])
                ->setSlug(strtolower(str_replace(' ', '-', $names[$j - 1]))) // Fixed space
                ->setDescription($faker->text);
            $manager->persist($cat);

            for ($i = 1; $i < random_int(1, 15); $i++) {
                $livre = new Livres();
                $titre = $faker->name();

                $livre->setTitre($titre)
                    ->setEditeur($faker->company())
                    ->setDateEdition($faker->dateTimeBetween('-5 years', 'now'))
                    ->setResume($faker->text())
                    ->setIsbn($faker->isbn13())
                    ->setImage('https://picsum.photos/200/?id=' . $i)
                    ->setPrix($faker->randomFloat(2, 10, 700))
                    ->setSlag(strtolower(str_replace(' ', '-', $titre)))
                    ->setCategorie($cat);
                $manager->persist($livre);
            }
        }
        $manager->flush();
    }
}
