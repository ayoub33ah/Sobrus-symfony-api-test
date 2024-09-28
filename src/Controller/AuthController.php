<?php


namespace App\Controller;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/api/register", name="register", methods={"POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder,SerializerInterface $serializer): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);
        
        $password = $data['password'];
        $email = $data['email'];

        if (empty($password) || empty($email)) {
            return new JsonResponse(["error" => "Invalid Password or Email"],400);
        }
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if($user){
            return new JsonResponse(["error" => "Invalid Email Already used"],400);
        }

        $user = new User();
        $user->setPassword($encoder->encodePassword($user, $password));
        $user->setEmail($email);
        $roles[] = "ROLE_USER";
        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager-> flush();

        $jsonContent = $serializer->serialize($user, 'json',['groups' => ['read_user']]);
        return new JsonResponse($jsonContent , 201, [], true);
    }


    /**
     * @Route("/api/login", name="login-check", methods={"POST"})
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function getTokenUser(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }

}