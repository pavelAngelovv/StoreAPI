<?php

namespace App\Tests\Controller;

use App\Entity\Alcohol;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function testGetList(): void
    {
        $this->client->request('GET', '/alcohols?page=1&perPage=55');
        $responseContent = $this->client->getResponse()->getContent();
    
        $this->assertResponseIsSuccessful();
    
        $this->assertJson($responseContent);
    
        $this->assertStringContainsString('"total":50', $responseContent);
        $this->assertStringContainsString('"alcohols":[', $responseContent);
         
        $this->client->request('GET', '/alcohols?perPage=55');
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Query parameters \'page\' and \'perPage\' are required.', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/alcohols?page=1');
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Query parameters \'page\' and \'perPage\' are required.', $this->client->getResponse()->getContent());
    }
    
    public function testGetItem(): void
    {
        $this->client->request('GET', '/alcohols/1');
        $responseContent = $this->client->getResponse()->getContent();
    
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

        $this->client->request('GET', '/alcohols/51');
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

    public function testCreateItem(): void
    {
        $this->authenticateClient();

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
    
        $this->client->request(
            'POST',
            '/admin/alcohols',
            $postData,
            ['image' => $uploadedFile],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('"name":"Test Beer"', $responseContent);
        $this->assertStringContainsString('"type":"beer"', $responseContent);
        $this->assertStringContainsString('"abv":7.5', $responseContent);
        $this->assertStringContainsString('"country":"Country 5"', $responseContent);
        $this->assertStringContainsString('"producer":{', $responseContent);
        $this->assertStringContainsString('"name":"Producer 5"', $responseContent);
        $this->assertStringContainsString('"country":"Country 5"', $responseContent);
    
        $this->client->request(
            'POST',
            '/admin/alcohols',
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Producer ID is required', $this->client->getResponse()->getContent());
        
        $this->client->request(
            'POST',
            '/admin/alcohols',
            ['producerId' => 5],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
        
        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('Image file is required', $this->client->getResponse()->getContent());
        
        $tempFilePath = tempnam(sys_get_temp_dir(), 'alcohol_image_test');
        copy('/Users/pavelangelov/Documents/GitHub/Store-api/Store-api/StoreAPI/tests/images/alcohol_image.jpeg', $tempFilePath);

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
        $this->client->request('PUT', '/admin/alcohols/1');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('JWT Token not found', $this->client->getResponse()->getContent());
    }

    public function testUpdateItem(): void
    {
        $this->authenticateClient();
        $createdEntity = $this->createItem();
        $createdItemId = $createdEntity['id'];

        $updatedData = [
            'name' => 'Updated Beer',
            'type' => 'beer',
            'description' => 'Updated beer description',
            'producerId' => 6, 
            'abv' => 8.0,
        ];

        $this->client->request(
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

        $this->client->request(
            'PUT',
            '/admin/alcohols/' . $createdItemId, 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseStatusCodeSame(400); 
        $this->assertStringContainsString('The \'producerId\' field is required.', $this->client->getResponse()->getContent());
    }

    public function testDeleteItemUnauthenticated(): void
    {
        $this->client->request('DELETE', '/admin/alcohols/1');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('JWT Token not found', $this->client->getResponse()->getContent());
    }

    public function testDeleteItem(): void
    {
        $this->authenticateClient();
        $createdEntity = $this->createItem();
        $createdItemId = $createdEntity['id'];
        $this->client->request('DELETE', '/admin/alcohols/' . $createdItemId);
        $this->assertResponseIsSuccessful();
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $deletedAlcohol = $entityManager->getRepository(Alcohol::class)->find($createdItemId);

        $this->assertNull($deletedAlcohol, 'The item should be deleted');

        $this->client->request('DELETE', '/admin/alcohols/51');
        $responseContent = $this->client->getResponse()->getContent();
    
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Alcohol not found.', $responseContent);
    }

    public function createItem(): array
    {
        $this->authenticateClient();

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
    
        $this->client->request(
            'POST',
            '/admin/alcohols',
            $postData,
            ['image' => $uploadedFile],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        $createdItemId = $responseData['id'];
    
        return ['id' => $createdItemId, 'data' => $postData];
    }
}
