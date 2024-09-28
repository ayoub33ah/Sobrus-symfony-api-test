<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class BlogArticleControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

    }

    public function testGetToken()
    {
       
        $this->client->request(
            'POST',
            '/api/login', 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'admin@email.com',  
                'password' => '1234' 
            ])
        );

        
    $this->assertResponseIsSuccessful();
    
    $responseData = json_decode($this->client->getResponse()->getContent(), true);
    $this->assertArrayHasKey('token', $responseData);
    
    return $responseData['token'];
    }

    public function testCreateBlogArticle()
    {
        
        $jwt = $this->testGetToken();
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $jwt));

        $filePath = __DIR__ . '/test_image.jpg'; 
        $uploadedFile = new UploadedFile(
            $filePath,
            'test_image.jpg',
            'image/jpeg',
            null,
            true 
        );

        $this->client->request(
            'POST',
            '/api/blog-articles', 
            [  
                'title' => 'Article de test',
                'publicationDate' => '2024-09-27',
                'content' => 'Ceci est un contenu de test',
                'keywords' => ['test'],
                'slug' => 'article-de-test',
                'status' => 'published',
            ],
            [   
                'file' => $uploadedFile
            ]
        );
        
        $this->assertResponseStatusCodeSame(201);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/api/blog-articles');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetBlogArticleNotFound(): void
    {
        $jwt = $this->testGetToken();
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $jwt));

        
        $this->client->request('GET', '/api/blog-articles/999'); 

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $responseContent);
        $this->assertEquals('blog Article not found', $responseContent['error']);
    }
}



