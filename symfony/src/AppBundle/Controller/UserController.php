<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert ;
use BackendBundle\Entity\User;
// use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

class UserController extends Controller{

        public function newAction(Request $request){
          $helpers = $this->get(Helpers::class);
          //gget the values to send by post
          $json = $request->get('json',null);
          $params = json_decode($json);

          $data = array(
            'status'=>'error',
            'code'=> 400,
            'msg'=> 'user not created'
          );

          if($json){
            $createdAt = new \Datetime("now");
            $role = 'user';

            $email = (isset($params->email))?$params->email:null;
            $name = (isset($params->name))?$params->name:null;
            $surname = (isset($params->surname))?$params->surname:null;
            $password = (isset($params->password))?$params->password:null;

            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "this email is not valid";
            $validate_email = $this->get('validator')->validate($email, $emailConstraint);

          }
          if($email != null && count($validate_email)==0 && $password !=null && $name != null && $surname != null && $password != null){
            $user = new User();
            $user->setCreatedAt($createdAt);
            $user->setRole($role);
            $user->setEmail($email);
            $user->setName($name);
            $user->setSurname($surname);
            $user->setPassword($password);

            $em = $this->getDoctrine()->getManager();
            $isset_user = $em->getRepository('BackendBundle:User')->findOneBy(array(
              'email'=>$email
            ));

            if(count($isset_user)==0){
              $em->persist($user);
              $em->flush();
              $data = array(
                'status'=>'success',
                'code'=> 200,
                'msg'=> 'user created',
                'user'=> $user
              );
            }else{

              $data = array(
                'status'=>'error',
                'code'=> 400,
                'msg'=> 'user exists'
              );

            }
          }
          return $helpers->json($data);
        }

        public function editAction(Request $request){
            $helpers = $this->get(Helpers::class);
            $jwt_auth = $this->get(JwtAuth::class);


            //gget the values to send by post

            $token = $request->get('authorization', null);
            $authCheck = $jwt_auth->checkToken($token);
            if($authCheck){
                $em = $this->getDoctrine()->getManager();
                // get user data
                $identity = $jwt_auth->checkToken($token,true);
                // dump($identity);die;
                // get data to update
                $user = $em->getRepository('BackendBundle:User')->findOneBy(array(
                  'id'=>$identity->sub
                ));

                $json = $request->get('json',null);
                $params = json_decode($json);

                $data = array(
                  'status'=>'error',
                  'code'=> 400,
                  'msg'=> 'user not created'
                );

                if($json){
                  $createdAt = new \Datetime("now");
                  $role = 'user';

                  $email = (isset($params->email))?$params->email:null;
                  $name = (isset($params->name))?$params->name:null;
                  $surname = (isset($params->surname))?$params->surname:null;
                  $password = (isset($params->password))?$params->password:null;

                  $emailConstraint = new Assert\Email();
                  $emailConstraint->message = "this email is not valid";
                  $validate_email = $this->get('validator')->validate($email, $emailConstraint);

                }
                if($email != null && count($validate_email)==0 && $password !=null && $name != null && $surname != null && $password != null){

                  $user->setCreatedAt($createdAt);
                  $user->setRole($role);
                  $user->setEmail($email);
                  $user->setName($name);
                  $user->setSurname($surname);
                  // encrypt password
                  $pwd = hash('sha256',$password);
                  $user->setPassword($pwd);


                  $isset_user = $em->getRepository('BackendBundle:User')->findOneBy(array(
                    'email'=>$email
                  ));

                  if(count($isset_user)==0 || $identity->email == $email){
                    $em->persist($user);
                    $em->flush();
                    $data = array(
                      'status'=>'success',
                      'code'=> 200,
                      'msg'=> 'user  update',
                      'user'=> $user
                    );
                  }else{

                    $data = array(
                      'status'=>'error',
                      'code'=> 400,
                      'msg'=> 'user was not update'
                    );

                  }
                }


            }else {
              $data = array(
                'status'=>'error',
                'code'=> 400,
                'msg'=> 'authorization no valid!!'
              );
            }
          return $helpers->json($data);

        }

}
