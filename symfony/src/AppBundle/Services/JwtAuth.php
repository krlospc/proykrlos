<?php
namespace AppBundle\Services;

use Firebase\JWT\JWT;

class JwtAuth{
public $manager;
public $key;

  public function __construct($manager){

    $this->manager = $manager;
    $this->key = 'iamthesecretkeykrlos';
  }

  public function signup($email, $password, $getHash=null){

    $user = $this->manager->getRepository('BackendBundle:User')->findOneBy(array(
      'email' => $email,
      'password' => $password
    ));
    $signup = false;
    if(is_object($user)){
      $signup = true;
    }

    if($signup){
      //generate token jwt
      $token = array(
        "sub" => $user->getId(),
        "email" => $user->getEmail(),
        "name" => $user->getName(),
        "suername" => $user->getSurname(),
        "iat" => time(),
        "exp" => time() +(7*24*60*60)
      );

      $jwt = JWT::encode($token,$this->key,'HS256');
      $decode = JWT::decode($jwt,$this->key,array('HS256'));
      if(!$getHash){
        $data = $jwt;
      }else {
        $data = $decode;
      }
    }else{
      $data = array(
        'status'=>'error',
        'user' => 'login failed!!'
      );
    }
	   return $data;
	}

  public function checkToken($jwt, $getIdentity=false){
    $auth = false;
    try {
      $decode = JWT::decode($jwt,$this->key,array('HS256'));
    } catch (\UnexpectedValueException $e) {
      $auth=false;
    } catch (\DomainException $e) {
      $auth=false;
    }

    if(isset($decode) && is_object($decode) && isset($decode->sub)){
      $auth = true;
    }else{
      $auth = false;
    }

    if($getIdentity == false){
      return $auth;
    }else{
      return $decode;
    }

  }
}
