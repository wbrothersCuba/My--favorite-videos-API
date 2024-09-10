<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Entity\User;

class JwtAuth
{
    public $manager;
    public $key;

    public function __construct($manager)
    {
        $this->manager = $manager;
        $this->key = '**mysecretkeyñoqdificil89548962145';
    }

    public function login($email, $passw, $getToken = null)
    {
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $passw,
        ]);
        $login = false;
        if (is_object($user)) // check if user exist
            $login = true;
        if ($login) {
            $token = [
                'sub' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time() + 7 * 24 * 60 * 60 //a week
            ];
            $jwt = JWT::encode($token, $this->key, 'HS256'); //generate jwt token
            if (!empty($getToken)) { //check flag
                $data = $jwt;
            } else {
                $decoded = JWT::decode($jwt, new Key($this->key, 'HS256')); //get user data
                $data = $decoded;
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Login failed'
            ];
        }

        return $data;
    }

    public function checkToken($jwt, $identity = false)
    {
        $auth = false;
        try {
            $decoded = JWT::decode($jwt, new Key($this->key, 'HS256')); //get user data
        } catch (\UnexpectedValueException $e) {
            $auth = false;
        } catch (\DomainException $e) {
            $auth = false;
        }
        if (isset($decoded) && !empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        } else {
            $auth = false;
        }
        if ($identity) {
            return $decoded;
        } else {
            return $auth;
        }
    }

}

