<?php

namespace App\DataFixtures;

use App\Entity\Alcohol;
use App\Entity\Image;
use App\Entity\Producer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AlcoholFixtures extends Fixture
{
    private static $alcoholTypes = ['beer', 'whiskey', 'wine', 'rum', 'vodka'];

    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 10; $i++) {
            $producer = new Producer();
            $producer->setName("Producer {$i}");
            $producer->setCountry("Country {$i}");
            $manager->persist($producer);

            for ($j = 1; $j <= 5; $j++) {
                $alcoholType = self::$alcoholTypes[array_rand(self::$alcoholTypes)];

                $image = new Image();
                $fileName = md5(uniqid()) . '.jpg';
                $image->setFilename($fileName);
                $image->setUrl("https://images.unsplash.com/photo-1569529465841-dfecdab7503b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Nnx8YWxjb2hvbHxlbnwwfHwwfHx8MA%3D%3D&auto=format&fit=crop&w=800&q=60");
                $manager->persist($image);

                $alcohol = new Alcohol();
                $alcohol->setName("{$alcoholType} {$j}");
                $alcohol->setType($alcoholType);
                $alcohol->setDescription("This {$alcoholType} {$j} is the best!");
                $alcohol->setProducer($producer);
                $alcohol->setAbv(rand(4, 10) + (rand(0, 9) / 10));
                $alcohol->setImage($image);
                $manager->persist($alcohol);
            }
        }

        $manager->flush();
    }
}
