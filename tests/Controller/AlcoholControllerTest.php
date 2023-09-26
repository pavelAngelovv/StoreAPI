<?php

namespace App\Tests\Controller;

use App\Entity\Alcohol;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AlcoholControllerTest extends WebTestCase
{
    private $authenticatedClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticatedClient = $this->createAuthenticatedClient();
    }

    protected function createAuthenticatedClient($username = 'admin', $password = 'password')
    {
        $client = static::createClient();
        $client->request(
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
    
        $data = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));
    
        return $client;
    }
    public function testGetList(): void
    {
        $client = $this->authenticatedClient;
        $client->request('GET', '/alcohols?page=1&perPage=55');
        $responseContent = $client->getResponse()->getContent();
    
        $this->assertResponseIsSuccessful();
    
        $this->assertJson($responseContent);
    
        $this->assertStringContainsString('"total":50', $responseContent);
        $this->assertStringContainsString('"alcohols":[', $responseContent);
        $this->assertStringContainsString('"id":1', $responseContent);
        $this->assertStringContainsString('"name":"vodka 1"', $responseContent);
        $this->assertStringContainsString('"type":"vodka"', $responseContent);
        $this->assertStringContainsString('"description":"This wine 5 is the best!"', $responseContent);
        $this->assertStringContainsString('"producer":{', $responseContent);
        $this->assertStringContainsString('"id":1', $responseContent);
        $this->assertStringContainsString('"name":"Producer 1"', $responseContent);
        $this->assertStringContainsString('"country":"Country 1"', $responseContent);
        $this->assertStringContainsString('"abv":8.3', $responseContent);
        $this->assertStringContainsString('"image":{', $responseContent);
        $this->assertStringContainsString('"id":1', $responseContent);
        $this->assertStringContainsString('"filename":"c174fc3b59fecea41c5e9e8e9809e97d.jpg"', $responseContent);

        $this->assertStringContainsString('"id":50', $responseContent);
        $this->assertStringContainsString('"name":"wine 5"', $responseContent);
        $this->assertStringContainsString('"type":"wine"', $responseContent);
        $this->assertStringContainsString('"description":"This wine 5 is the best!"', $responseContent);
        $this->assertStringContainsString('"producer":{', $responseContent);
        $this->assertStringContainsString('"id":10', $responseContent);
        $this->assertStringContainsString('"name":"Producer 10"', $responseContent);
        $this->assertStringContainsString('"country":"Country 10"', $responseContent);
        $this->assertStringContainsString('"abv":9.5', $responseContent);
        $this->assertStringContainsString('"image":{', $responseContent);
        $this->assertStringContainsString('"id":50', $responseContent);
        $this->assertStringContainsString('"filename":"c174fc3b59fecea41c5e9e8e9809e97d.jpg"', $responseContent);
    }
    
    public function testGetItem(): void
    {
        $client = $this->authenticatedClient;
        $client->request('GET', '/alcohols/1');
        $responseContent = $client->getResponse()->getContent();
    
        $this->assertResponseIsSuccessful();
    
        $this->assertJson($responseContent);
    
        $this->assertStringContainsString('"id":1', $responseContent);
        $this->assertStringContainsString('"name":"vodka 1"', $responseContent);
        $this->assertStringContainsString('"type":"vodka"', $responseContent);
        $this->assertStringContainsString('"abv":5', $responseContent);
        $this->assertStringContainsString('"country":"Country 1"', $responseContent);
        $this->assertStringContainsString('"producer":{', $responseContent);
        $this->assertStringContainsString('"name":"Producer 1"', $responseContent);
        $this->assertStringContainsString('"country":"Country 1"', $responseContent);
        $this->assertStringContainsString('"image":{', $responseContent);
        $this->assertStringContainsString('"id":1', $responseContent);
        $this->assertStringContainsString('"filename":"c174fc3b59fecea41c5e9e8e9809e97d.jpg"', $responseContent);
    }

    public function testCreateItem(): void
    {
        $client = $this->authenticatedClient;

        $tempFilePath = tempnam(sys_get_temp_dir(), 'alcohol_image_test');
        copy('/Users/pavelangelov/Documents/GitHub/Store-api/Store-api/StoreAPI/tests/images/alcohol_image.jpeg', $tempFilePath);

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
    
        $client->request(
            'POST',
            '/admin/alcohols',
            $postData,
            ['image' => $uploadedFile],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('"name":"Test Beer"', $responseContent);
        $this->assertStringContainsString('"type":"beer"', $responseContent);
        $this->assertStringContainsString('"abv":7.5', $responseContent);
        $this->assertStringContainsString('"country":"Country 5"', $responseContent);
        $this->assertStringContainsString('"producer":{', $responseContent);
        $this->assertStringContainsString('"name":"Producer 5"', $responseContent);
        $this->assertStringContainsString('"country":"Country 5"', $responseContent);
    }

    public function testUpdateItem(): void
    {
        $client = $this->authenticatedClient;
        $createdEntity = $this->createItem();
        $createdItemId = $createdEntity['id'];

        $updatedData = [
            'name' => 'Updated Beer',
            'type' => 'beer',
            'description' => 'Updated beer description',
            'producerId' => 6, 
            'abv' => 8.0,
        ];

        $client->request(
            'PUT',
            '/admin/alcohols/' . $createdItemId, 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $this->assertResponseIsSuccessful();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $updatedAlcohol = $entityManager->getRepository(Alcohol::class)->find($createdItemId);

        $this->assertEquals('Updated Beer', $updatedAlcohol->getName());
        $this->assertEquals('beer', $updatedAlcohol->getType());
        $this->assertEquals('Updated beer description', $updatedAlcohol->getDescription());
        $this->assertEquals(6, $updatedAlcohol->getProducer()->getId());
        $this->assertEquals(8.0, $updatedAlcohol->getAbv());
    }

    public function testDeleteItem(): void
    {
        $client = $this->authenticatedClient;
        $createdEntity = $this->createItem();
        $createdItemId = $createdEntity['id'];
        $client->request('DELETE', '/admin/alcohols/' . $createdItemId);
        $this->assertResponseIsSuccessful();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $deletedAlcohol = $entityManager->getRepository(Alcohol::class)->find($createdItemId);

        $this->assertNull($deletedAlcohol, 'The item should be deleted');
    }

    public function createItem(): array
    {
        $client = $this->authenticatedClient;

        $tempFilePath = tempnam(sys_get_temp_dir(), 'alcohol_image_test');
        copy('/Users/pavelangelov/Documents/GitHub/Store-api/Store-api/StoreAPI/tests/images/alcohol_image.jpeg', $tempFilePath);

        $uploadedFile = new UploadedFile(
            $tempFilePath,
            'alcohol_image.jpeg', 
            'image/jpeg', 
            null,
            true
        );

        $postData = [
            'name' => 'Newly Created Beer',
            'type' => 'beer',
            'description' => 'This beer was just created!',
            'producerId' => 7, 
            'abv' => 6.5,
        ];
    
        $client->request(
            'POST',
            '/admin/alcohols',
            $postData,
            ['image' => $uploadedFile],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $responseContent = $client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        $createdItemId = $responseData['id'];
    
        return ['id' => $createdItemId, 'data' => $postData];
    }
}
