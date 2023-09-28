<?php

namespace App\Tests\Controller;

use App\Entity\Alcohol;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class AlcoholControllerTest extends WebTestCase
{    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    private function authenticateClient($username = 'admin', $password = 'password')
    {
        $this->client->request(
            'POST',
            '/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $username,
                'password' => $password,
            ])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));
    }

    public function testGetListSuccess(): void
    {
        $this->client->request('GET', '/alcohols?page=1&perPage=55');
        $responseContent = $this->client->getResponse()->getContent();
    
        $this->assertResponseIsSuccessful();
        $this->assertJson($responseContent);
    
        $responseData = json_decode($responseContent, true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('alcohols', $responseData);
        $this->assertIsArray($responseData['alcohols']);
        $this->assertEquals(50, $responseData['total']);
    }

    public function testGetListFailure(): void
    {    
        $this->client->request('GET', '/alcohols?perPage=55');
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Query parameters \'page\' and \'perPage\' are required.', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/alcohols?page=1');
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Query parameters \'page\' and \'perPage\' are required.', $this->client->getResponse()->getContent());
    }
    
    public function testGetItemSuccess(): void
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->findOneBy(['name' => 'wine 5']);

        $this->client->request('GET', '/alcohols/' . $alcohol->getId());
        $responseContent = $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
    
        $this->assertJson($responseContent);
    
        $responseData = json_decode($responseContent, true);
    
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('producer', $responseData);
        $this->assertArrayHasKey('image', $responseData);
        $this->assertEquals(15, $responseData['id']);
        $this->assertEquals('wine 5', $responseData['name']);
    }

    public function testGetItemFailure(): void
    {
        $this->client->request('GET', '/alcohols/999');
        $responseContent = $this->client->getResponse()->getContent();
    
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not found.', $responseContent);
    }

    public function testCreateItemUnauthenticated(): void
    {
        $this->client->request('POST', '/admin/alcohols');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('JWT Token not found', $this->client->getResponse()->getContent());
    }

    public function testCreateItemSuccess(): void
    {
        $this->authenticateClient();
        
        $kernel = self::getContainer()->get(KernelInterface::class);
        $tempFilePath = tempnam(sys_get_temp_dir(), 'alcohol_image_test');
        copy($kernel->getProjectDir() . '/tests/images/alcohol_image.jpeg', $tempFilePath);

        $uploadedFile = new UploadedFile(
            $tempFilePath,
            'alcohol_image.jpeg', 
            'image/jpeg', 
            null,
            true
        );

        $postData = [
            'name' => 'Test Beer',
            'type' => 'beer',
            'description' => 'Test for creating a delicious beer',
            'producerId' => 5, 
            'abv' => 7.5,
        ];
    
        $this->client->request(
            'POST',
            '/admin/alcohols',
            $postData,
            ['image' => $uploadedFile],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseIsSuccessful();
        
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('type', $responseData);
        $this->assertArrayHasKey('abv', $responseData);
        
        $this->assertEquals('Test Beer', $responseData['name']);
        $this->assertEquals('beer', $responseData['type']);
        $this->assertEquals(7.5, $responseData['abv']);
        
        $this->assertArrayHasKey('producer', $responseData);
        $this->assertEquals('5', $responseData['producer']['id']);
        $this->assertEquals('Producer 5', $responseData['producer']['name']);
        $this->assertEquals('Country 5', $responseData['producer']['country']);

        $this->assertArrayHasKey('image', $responseData);
    }

    public function testCreateItemMissingProducerId(): void
    {
        $this->authenticateClient();
        $this->client->request(
            'POST',
            '/admin/alcohols',
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Producer ID is required', $this->client->getResponse()->getContent());
    }

    public function testCreateItemMissingImage(): void
    {
        $this->authenticateClient();
        $this->client->request(
            'POST',
            '/admin/alcohols',
            ['producerId' => 5],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Image file is required', $this->client->getResponse()->getContent());
    }

    public function testCreateItemMissingProperties(): void
    {
        $this->authenticateClient();
        $kernel = self::getContainer()->get(KernelInterface::class);
        $tempFilePath = tempnam(sys_get_temp_dir(), 'alcohol_image_test');
        copy($kernel->getProjectDir() . '/tests/images/alcohol_image.jpeg', $tempFilePath);

        $uploadedFile = new UploadedFile(
            $tempFilePath,
            'alcohol_image.jpeg', 
            'image/jpeg', 
            null,
            true
        );

        $this->client->request(
            'POST',
            '/admin/alcohols',
            ['producerId' => 5],
            ['image' => $uploadedFile],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Alcohol name is required', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Alcohol type is required', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Alcohol description is required', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Alcohol ABV is required', $this->client->getResponse()->getContent());
    }

    public function testUpdateItemUnauthenticated(): void
    {   
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->findOneBy(['name' => 'wine 5']);

        $this->client->request('PUT', '/admin/alcohols/' . $alcohol->getId());

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('JWT Token not found', $this->client->getResponse()->getContent());
    }

    public function testUpdateItemSuccess(): void
    {
        $this->authenticateClient();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->findOneBy(['name' => 'wine 5']);
        $itemId = $alcohol->getId();

        $updatedData = [
            'name' => 'Updated Beer',
            'type' => 'beer',
            'description' => 'Updated beer description',
            'producerId' => 6, 
            'abv' => 8.0,
        ];

        $this->client->request(
            'PUT',
            '/admin/alcohols/' . $itemId, 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $this->assertResponseIsSuccessful();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $updatedAlcohol = $entityManager->getRepository(Alcohol::class)->find($itemId);

        $this->assertNotNull($updatedAlcohol);
        $this->assertEquals('Updated Beer', $updatedAlcohol->getName());
        $this->assertEquals('beer', $updatedAlcohol->getType());
        $this->assertEquals('Updated beer description', $updatedAlcohol->getDescription());
        $this->assertEquals(6, $updatedAlcohol->getProducer()->getId());
        $this->assertEquals(8.0, $updatedAlcohol->getAbv());
    }
    
    public function testUpdateItemFailure(): void
    {
        $this->authenticateClient();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->findOneBy(['name' => 'wine 5']);
        $itemId = $alcohol->getId();

        $this->client->request(
            'PUT',
            '/admin/alcohols/' . $itemId, 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('The \'producerId\' field is required.', $this->client->getResponse()->getContent());

        $this->client->request(
            'PUT',
            '/admin/alcohols/999', 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['producerId' => 6]),
        );
        $responseContent = $this->client->getResponse()->getContent();
    
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Alcohol not found.', $responseContent);
    }

    public function testDeleteItemUnauthenticated(): void
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->findOneBy(['name' => 'wine 5']);

        $this->client->request('DELETE', '/admin/alcohols/' . $alcohol->getId());

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('JWT Token not found', $this->client->getResponse()->getContent());
    }

    public function testDeleteItemSuccess(): void
    {
        $this->authenticateClient();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $alcohol = $entityManager->getRepository(Alcohol::class)->findOneBy(['name' => 'wine 5']);
        $itemId = $alcohol->getId();
        $this->client->request('DELETE', '/admin/alcohols/' . $itemId);
        $this->assertResponseIsSuccessful();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $deletedAlcohol = $entityManager->getRepository(Alcohol::class)->find($itemId);
        
        $this->assertNull($deletedAlcohol, 'The item should be deleted');
    }

    public function testDeleteItemFailure(): void
    {
        $this->authenticateClient();
        $this->client->request('DELETE', '/admin/alcohols/999');
        $responseContent = $this->client->getResponse()->getContent();
    
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Alcohol not found.', $responseContent);
    }
}
