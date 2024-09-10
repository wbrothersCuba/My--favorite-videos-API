<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\JwtAuth;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

#[Route('/user')]
class UserController extends AbstractController
{

    private function resjson($data, SerializerInterface $serializer)
    {
        $json = $serializer->serialize($data, 'json');
        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    #[Route('/', name: 'app_user', methods: ['GET'])]
    public function index(UserRepository $userRepository, SerializerInterface $serializer,): JsonResponse
    {
        $data = $userRepository->findAll();
        return $this->resjson($data, $serializer);
    }

    #[Route('/signup', name: 'app_user_signup', methods: ['POST'])]
    public function signUp(SerializerInterface $serializer, Request $request, EntityManagerInterface $entityManager)
    {
        $json = $request->get('json', null); //get post data
        $params = json_decode($json, true);  //decode json
        $data = [                            //default response           
            'status' => 'error',
            'code' => 400,
            'message' => 'Failed to create a user'
        ];
        if ($json != null) {  // check and validate data
            $name = !empty($params['name']) ? $params['name'] : null;
            $surname = !empty($params['surname']) ? $params['surname'] : null;
            $email = !empty($params['email']) ? $params['email'] : null;
            $password = !empty($params['password']) ? $params['password'] : null;
            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [new Email()]);
            if (count($validate_email) == 0 && !empty($name) && !empty($surname) && !empty($email) && !empty($password)) {
                $user = new User();  //if valid create user object
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole('ROLE_USER');
                $user->setPassword(hash('sha256', $password));//encrypt pass
                $data = $user;
                $isset_user = $entityManager->getRepository(User::class)->findBy(['email' => $email]);// check duplicity
                if (count($isset_user) == 0) { // if dont exist save
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'User has been created successfully',
                        'user' => $user
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'User already exists'
                    ];
                }
            }
        }
        return $this->resjson($data, $serializer);
    }

    #[Route('/signin', name: 'app_user_signin', methods: ['POST'])]
    public function signIn(SerializerInterface $serializer, Request $request, JwtAuth $jwt_auth)
    {
        $json = $request->get('json', null);
        $params = json_decode($json, true);
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Incorrect login'
        ];
        if ($json != null) {
            $email = !empty($params['email']) ? $params['email'] : null;
            $password = !empty($params['password']) ? $params['password'] : null;
            $getToken = !empty($params['gettoken']) ? $params['gettoken'] : null;
            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);
            if (!empty($validate_email) && !empty($password) && count($validate_email) == 0) {
                $pwd = hash('sha256', $password);  // encrypt pass 
                if ($getToken) {
                    $singup = $jwt_auth->login($email, $pwd, $getToken); // jwt -> get token
                } else {
                    $singup = $jwt_auth->login($email, $pwd);// // jwt -> get user object
                }
                return new JsonResponse($singup);
            }
        }
        return $this->resjson($data, $serializer);
    }

    #[Route('/edit', name: 'app_user_edit', methods: ['PUT'])]
    public function edit(SerializerInterface $serializer, Request $request, EntityManagerInterface $em, JwtAuth $jwt_auth)
    {
        $token = $request->headers->get('Authorization'); // get auth header
        $authCheck = $jwt_auth->checkToken($token); // check if the token is right
        $data = [
            'status' => 'error',
            'code' => '400',
            'message' => 'Failed to update user'
        ];
        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);// get the user login data
            $user_repo = $em->getRepository(User::class);
            $user = $user_repo->findOneBy(['id' => $identity->sub]);//get the complete user object 
            $json = $request->get('json', null); // get the PUT data
            $params = json_decode($json, true);
            if (!empty($json) && !empty($user)) {  //check and validate data
                $name = !empty($params['name']) ? $params['name'] : null;
                $surname = !empty($params['surname']) ? $params['surname'] : null;
                $email = !empty($params['email']) ? $params['email'] : null;
                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [new Email()]);
                if (count($validate_email) == 0 && !empty($name) && !empty($surname) && !empty($email)) {
                    //set new user data
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setEmail($email);
                    //check duplicity
                    $isset_user = $user_repo->findBy(['email' => $email]); //new email is not in use
                    if (count($isset_user) == 0 || $identity->email == $email) { //email in PUT don't change
                        $em->persist($user);
                        $em->flush();
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'User updated successfully',
                            'user' => $user
                        ];
                    } else {
                        $data = [
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'Email is already in use'
                        ];
                    }
                }
            }
        }

        return $this->resjson($data, $serializer);
    }

}
