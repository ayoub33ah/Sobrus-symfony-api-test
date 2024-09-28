<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\BlogArticle;
use App\Entity\User;
use App\Service\BlogArticleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BlogArticleController extends AbstractController
{
    private $entityManager;
    private $blogArticleService;
    private $parameter;

    public function __construct(EntityManagerInterface $entityManager,BlogArticleService $blogArticleService,ParameterBagInterface $parameter)
    {
        $this->entityManager = $entityManager;
        $this->blogArticleService = $blogArticleService;
        $this->parameter = $parameter;
    }

    /**
     * @Route("/api/blog-articles", methods={"POST"})
     */
    public function createBlogArticle(Request $request, ValidatorInterface $validator)
    {
        $banned = $this->parameter->get('banned_words');
        $token = $request->headers->get('Authorization');
        $tokenParts = explode(".", $token);  
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);
        $users = $this->entityManager->getRepository(User::class)->findOneBy(["email"=>$jwtPayload->username]);
        $authorId = $users->getId();

        $file = $request->files->get('file');
        $title = $request->request->get('title');
        $publicationDate = $request->request->get('publicationDate');
        $content = $request->request->get('content');
        $slug = $request->request->get('slug');
        $status = $request->request->get('status');

        $keywords = $this->blogArticleService->mostFrequentWords($content,$banned);

        $blogArticle = new BlogArticle();
        $blogArticle->setAuthorId($authorId);
        $blogArticle->setTitle($title);
        $blogArticle->setPublicationDate(new \DateTime($publicationDate));
        $blogArticle->setContent($content);
        $blogArticle->setKeywords($keywords);
        $blogArticle->setSlug($slug);
        $blogArticle->setStatus($status);
        
    

        $errors = $validator->validate($blogArticle);
        if (count($errors) > 0) {
            $errorMessages = [];

            foreach ($errors as $error) {
                $errorMessages[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }
            
            return new JsonResponse([
                'status' => 'error',
                'errors' => $errorMessages
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $bannedWordsFound = $this->blogArticleService->validateContent($content,$banned);
        if (!empty($bannedWordsFound)) {
            $bannedWordsList = implode(', ', array_unique($bannedWordsFound)); 
            return new JsonResponse(["error" => "Your content contains banned words: " . $bannedWordsList], 400);
        }
        
        if ($file) {
            $docsize = $file->getSize();
            if ($docsize > 4194304) {
                return new JsonResponse(["error" => "File size must be less than 4 MB"], 400);
            }
            
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();
            
            $uploadDir = $this->getParameter('uploaded_pictures');
            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            if ($file->move($uploadDir, $fileName)) {
                $blogArticle->setCoverPictureRef($fileName);
            }
        }

        $this->entityManager->persist($blogArticle);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Blog article created'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/api/blog-articles", methods={"GET"})
     */
    public function getAllBlogArticles(SerializerInterface $serializer)
    {
      
        $blogArticles = $this->entityManager->getRepository(BlogArticle::class)->findAll();

        $serializedJobTitles = $serializer->serialize($blogArticles, 'json', ['groups' => ['read_blog_article']]);

        return new JsonResponse($serializedJobTitles, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/blog-articles/{id}", methods={"GET"})
     */
    public function getBlogArticle(SerializerInterface $serializer, $id)
    {
        
        $blogArticle = $this->entityManager->getRepository(BlogArticle::class)->find($id);

        if (!$blogArticle) {
            return new JsonResponse(['error' => 'blog Article not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $serialized = $serializer->serialize($blogArticle, 'json', ['groups' => ['read_blog_article']]);

        return new JsonResponse($serialized, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/blog-articles/{id}", methods={"POST"})
     */
    public function updateBlogArticle($id, Request $request, ValidatorInterface $validator)
    {
        $banned = $this->parameter->get('banned_words');
        $file = $request->files->get('file');
        $title = $request->request->get('title');
        $publicationDate = $request->request->get('publicationDate');
        $content = $request->request->get('content');
        $slug = $request->request->get('slug');
        $status = $request->request->get('status');

        $keywords = $this->blogArticleService->mostFrequentWords($content, $banned);
        
        $blogArticle = $this->entityManager->getRepository(BlogArticle::class)->find($id);
        if (!$blogArticle) {
            return new JsonResponse(['error' => 'blog Article not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $blogArticle->setTitle($title);
        $blogArticle->setPublicationDate(new \DateTime($publicationDate));
        $blogArticle->setContent($content);
        $blogArticle->setKeywords($keywords);
        $blogArticle->setSlug($slug);
        $blogArticle->setStatus($status);
        
        $errors = $validator->validate($blogArticle);
        if (count($errors) > 0) {
            $errorMessages = [];

            foreach ($errors as $error) {
                $errorMessages[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }

            return new JsonResponse([
                'status' => 'error',
                'errors' => $errorMessages
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $bannedWordsFound = $this->blogArticleService->validateContent($content, $banned);

        if (!empty($bannedWordsFound)) {
            $bannedWordsList = implode(', ', array_unique($bannedWordsFound)); 
            return new JsonResponse(["error" => "Your content contains banned words: " . $bannedWordsList], 400);
        }
        
        if ($file) {
            $uploadDir = $this->getParameter('uploaded_pictures');
            $filePath = $uploadDir . '/' . $blogArticle->getCoverPictureRef();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $docsize = $file->getSize();
            if ($docsize > 4194304) {
                return new JsonResponse(["error" => "File size must be less than 4 MB"], 400);
            }
             
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();
            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            if ($file->move($uploadDir, $fileName)) {
                $blogArticle->setCoverPictureRef($fileName);
            }
        }

        $this->entityManager->persist($blogArticle);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Blog article updated'], JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/api/blog-articles/{id}", methods={"DELETE"})
     */
    public function deleteBlogArticle($id, EntityManagerInterface $em)
    {
        $article = $em->getRepository(BlogArticle::class)->find($id);

        if (!$article) {
            return new JsonResponse(['error' => 'Article not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $article->setStatus('deleted');
        $em->flush();

        return new JsonResponse(['status' => 'Blog article soft deleted'], JsonResponse::HTTP_OK);
    }




}