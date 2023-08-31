<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Alcohol;
use App\Entity\Producer;
use App\Entity\Image;

class AlcoholFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Create producers
        for ($i = 1; $i <= 10; $i++) {
            $producer = new Producer();
            $producer->setName("Producer {$i}");
            $producer->setCountry("Country {$i}");
            $manager->persist($producer);

            // Create alcohol entries
            for ($j = 1; $j <= 5; $j++) {
                $image = new Image(); // Create a new Image for each alcohol entry
                $image->setName("Image {$j} for Alcohol {$i}");
                $image->setUrl("http://example.com/image{$j}_{$i}.jpg");
                $manager->persist($image);

                $alcohol = new Alcohol();
                $alcohol->setName("Alcohol {$j} from Producer {$i}");
                $alcohol->setType("Type {$j}");
                $alcohol->setDescription("Description for Alcohol {$j}");
                $alcohol->setProducer($producer);
                $alcohol->setAbv(rand(4, 10) + (rand(0, 9) / 10));
                $alcohol->setImage($image); // Associate the alcohol entry with the specific image
                $manager->persist($alcohol);
            }
        }

        $manager->flush();
    }
}