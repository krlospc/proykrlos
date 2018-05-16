<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert ;
use BackendBundle\Entity\Task;
// use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

class TaskController extends Controller{

    public function newAction(Request $request, $id=null){

      $helpers = $this->get(Helpers::class);
      $jwt_auth = $this->get(JwtAuth::class);

      $token = $request->get('authorization', null);
      $authCheck = $jwt_auth->checkToken($token);

      if($authCheck){

        $identity = $jwt_auth->checkToken($token, true);

        $json = $request->get('json', null);
          if($json != null ){
            $params = json_decode($json);

            $createdAt = new \Datetime('now');
            $updatedAt = new \Datetime('now');

            $user_id =($identity->sub != null) ? $identity->sub:null;
            $title =(isset($params->title)) ? $params->title:null;
            $description =(isset($params->description)) ? $params->description:null;
            $status =(isset($params->status)) ? $params->status:null;

            if($user_id != null && $title != null){
              // create new task
              $em = $this->getDoctrine()->getManager();
              $user =  $em->getRepository('BackendBundle:User')->findOneBy(array(
                'id' => $user_id
              ));
              if($id == null){
                $task = new Task();
                $task->setUser($user);
                $task->setTitle($title);
                $task->setDescription($description);
                $task->setStatus($status);
                $task->setUpdatedAt($updatedAt);
                $task->setCreatedAt($createdAt);
              }else {
                $task = $em->getRepository('BackendBundle:Task')->findOneBy(array(
                  'id'=> $id
                ));
                if(isset($identity->sub) && $identity->sub == $task->getUser()->getId()){


                  $task->setTitle($title);
                  $task->setDescription($description);
                  $task->setStatus($status);
                  $task->setUpdatedAt($updatedAt);
                  $data = array(
                    'status'=>'success',
                    'code'  => 200,
                    'msg' => 'task was updated!!',
                  );

                }else {
                    $data = array(
                      'status'=>'error',
                      'code'  => 200,
                      'msg' => 'task was not updated, you are not owner!!',
                    );
                }

              }

              $em->persist($task);
              $em->flush();

              $data = array(
                'status'=>'success',
                'code'  => 200,
                'msg' => 'task was created!!',
              );

            }else {
              $data = array(
                'status'=>'error',
                'code'  => 200,
                'msg' => 'task was not created, validation failed!!',
              );
            }



          }else {
              $data = array(
                'status'=>'success',
                'code'  => 200,
                'msg' => 'task was not created',
              );
          }

      }else {
        $data = array(
          'status'=>'error',
          'code'  => 400,
          'msg' => 'error authorization'
        );
      }

      return $helpers->json($data);


    }

    public function tasksAction(Request $request){

      $helpers = $this->get(Helpers::class);
      $jwt_auth = $this->get(JwtAuth::class);

      $token = $request->get('authorization', null);
      $authCheck = $jwt_auth->checkToken($token);

      if($authCheck){
        $identity = $jwt_auth->checkToken($token,true);

        $em = $this->getDoctrine()->getManager();
        $dql = 'select t from BackendBundle:Task t order by t.id DESC';
        $query = $em->createQuery($dql);

        $page = $request->query->getInt('page',1);
        $paginator = $this->get('knp_paginator');
        $items_per_page = 5;

        $pagination = $paginator->paginate($query,$page,$items_per_page);
        $total_items_count =  $pagination->getTotalItemCount();
        $data = array(
          'status'=>'success',
          'code'=>200,
          'msg'=>'good!!',
          'total_items_count' => $total_items_count,
          'page_actual'=>$page,
          'items_per_page'=> $items_per_page,
          'total_pages' => ceil($total_items_count/$items_per_page),
          'data'=>$pagination
        );
      }else {
        $data = array(
          'status'=>'error',
          'code'=>400,
          'msg'=>'authorization not valid!!'
        );

      }
      return $helpers->json($data);
    }

    public function taskAction(Request $request, $id = null){
      $helpers = $this->get(Helpers::class);
      $jwt_auth = $this->get(JwtAuth::class);

      $token = $request->get('authorization', null);

      $authCheck = $jwt_auth->checkToken($token);

      if($authCheck){
        $identity = $jwt_auth->checkToken($token, true);

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('BackendBundle:Task')->findOneBy(array(
          'id'=>$id
        ));

        if($task && is_object($task) && $identity->sub == $task->getUser()->getId()){
          $data = array(
            'status'=>'success',
            'code' => 200,
            'data' => $task
          );
        }else {
          $data = array(
            'status'=>'error',
            'code' => 404,
            'msg' => 'task not found'
          );
        }

      }else {

      }

      return $helpers->json($data);
    }

    public function searchAction(Request $request, $search = null){
      $helpers = $this->get(Helpers::class);
      $jwt_auth = $this->get(JwtAuth::class);

      $token = $request->get('authorization',null);
      $authCheck = $jwt_auth->checkToken($token);

      if($authCheck){
        $identity = $jwt_auth->checkToken($token,true);
        $em = $this->getDoctrine()->getManager();

        // filtro
        $filter =  $request->get('filter',null);
        if(empty($filter)){
          $filter = null;
        }elseif($filter == 1){
          $filter='new';
        }elseif($filter == 2){
          $filter='todo';
        }else{
          $filter='finished';
        }

        // order??
        $order = $request->get('request',null);
        if(empty($order) || $order  == 2){
          $order = 'DESC';
        }else {
          $order = 'ASC';
        }

        // busqueda
        if($search !=null ){
          $dql = "select t from BackendBundle:Task t "
                ."where t.user= $identity->sub and "
                ."(t.title like :search or t.description like :search) ";

        }else {
          $dql = "select t from BackendBundle:Task t "
                ."where t.user = $identity->sub";

        }
// dump($dql);die;
        // set filter
        if($filter != null ){
          $dql .= "and t.status = :filter ";
        }
        // set order?
        $dql .= "order by t.id $order ";

        $query = $em->createQuery($dql);

        if($filter != null ){
            $query->setParameter('filter', "$filter");
        }
        if(!empty($search)){
          $query->setParameter('search', "%$search%");
        }

        $task = $query->getResult();

        $data = array(
          'status'=> 'success',
          'code' => 200,
          'data' => $task
        );

      }else {

          $data = array(
            'status'=> 'error',
            'code' => 400,
            'msg' => 'error'
          );
      }

      return $helpers->json($data);
    }

    public function removeAction(Request $request, $id = null){

      $helpers = $this->get(Helpers::class);
      $jwt_auth = $this->get(JwtAuth::class);

      $token = $request->get('authorization', null);

      $authCheck = $jwt_auth->checkToken($token);

      if($authCheck){
        $identity = $jwt_auth->checkToken($token, true);

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('BackendBundle:Task')->findOneBy(array(
          'id'=>$id
        ));

        if($task && is_object($task) && $identity->sub == $task->getUser()->getId()){
          // delete the object
          $em->remove($task);
          $em->flush();
          $data = array(
            'status'=>'success',
            'code' => 200,
            'data' => $task
          );
        }else {
          $data = array(
            'status'=>'error',
            'code' => 404,
            'msg' => 'task not found'
          );
        }

      }else {

      }

      return $helpers->json($data);

    }

}
