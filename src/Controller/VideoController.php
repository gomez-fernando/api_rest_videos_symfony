<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// importar
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;

use Symfony\Component\Routing\Annotation\Route;

class VideoController extends AbstractController
{
    private function resjson($data)
    {
        // serializar datos con servicio serializer con el metodo get() que permite acceder a un servicio que haya en symfony
        $json = $this->get('serializer')->serialize($data, 'json');

        // response con hhtpfoundation
        $response = new Response();

        // asignar contenido a la respuesta
        $response->setContent($json);

        // indicar formato de la respuesta
        $response->headers->set('Content-Type', 'application/json');

        // devolver la respuesta
        return $response;
    }


    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }

    public function create(Request $request, JwtAuth $jwtAuth, $id = null)
    {
        if ($id == null) {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'El video no ha podido crearse',
            ];
        } else {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'El video no ha podido editarse',
            ];
        }

        // recoger el token
        $token = $request->headers->get('Authorization', null);

        // comprobar si es correcto
        $authCheck = $jwtAuth->checkToken($token);

        if ($authCheck) {
            // recoger datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);

            // recoger el objeto del usuario identificado esta vez con el parametro true para que nos devuelva un objeto
            $identity = $jwtAuth->checkToken($token, true);

            // comprobar y validar datos
            if (!empty($json)) {
                $userId = ($identity->sub != null) ? $identity->sub : null;
                $title = (!empty($params->title)) ? $params->title : null;
                $description = (!empty($params->description)) ? $params->description : null;
                $url = (!empty($params->url)) ? $params->url : null;
                $status = (!empty($params->status)) ? $params->status : null;

                if (!empty($userId) && !empty($title)) {
                    // guardar el video en la db
                    $em = $this->getDoctrine()->getManager();
                    $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                        'id' => $userId
                    ]);

                    // editar video
                    if ($id == null) {
                        // crear y guardar objeto
                        $video = new Video();
                        $video->setUser($user);
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setUrl($url);

                        $createdAt = new \Datetime('now');
                        $updatedAt = new \Datetime('now');

                        $video->setCreatedAt($createdAt);
                        $video->setUpdatedAt($updatedAt);

                        // guardar en la db
                        $em->persist($video);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'El video se ha guardado correctamente',
                            'video' => $video
                        ];
                    } else {
                        $video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
                            'id' => $id,
                            'user' => $identity->sub
                        ]);

                        if ($video && is_object($video)) {
                            $video->setTitle($title);
                            $video->setDescription($description);
                            $video->setUrl($url);
    
                            $updatedAt = new \Datetime('now');
                            $video->setUpdatedAt($updatedAt);

                            $em->persist($video);
                            $em->flush();

                            $data = [
                                'status' => 'success',
                                'code' => 200,
                                'message' => 'El video se ha actualizado correctamente',
                                'video' => $video
                            ];
                        }
                    }
                }
            }
        }
        //devolver la respuesta
        return $this->resjson($data);
    }

    public function videos(Request $request, JwtAuth $jwtAuth, PaginatorInterface $paginator)
    {
        // recoger la cabecera de autenticación
        $token = $request->headers->get('Authorization');

        // comprobar el token
        $authCheck = $jwtAuth->checkToken($token);

        // si es valido
        if ($authCheck) {
            // conseguir la identidad del usuario
            $identity = $jwtAuth->checkToken($token, true);

            // hacer una consulta para paginar
            $em = $this->getDoctrine()->getManager();

            $dql = "SELECT v from App\Entity\Video v where v.user = {$identity->sub} order by v.id desc";
            $query = $em->createQuery($dql);

            // recoger el parametro page de la url
            $page = $request->query->getInt('page', 1);
            $itemsPerPage = 6;

            // invocar paginacion
            $pagination = $paginator->paginate($query, $page, $itemsPerPage);
            $total = $pagination->getTotalItemCount();

            // preparar array de datos para devolver
            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $itemsPerPage,
                'total_pages' => ceil($total / $itemsPerPage),
                'videos' => $pagination,
                'user_id' => $identity->sub,
            ];
        } else {

            // si falla devolver esto:
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'No se pueden listar los videos en este momento'
            ];
        }

        return $this->resjson($data);
    }

    public function video(Request $request, JwtAuth $jwtAuth, $id = null)
    {
        // recoger la cabecera de autenticación
        $token = $request->headers->get('Authorization');

        // comprobar el token
        $authCheck = $jwtAuth->checkToken($token);

        // respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Vídeo no encontrado',
        ];

        if ($authCheck) {
            // sacar la identidad del usuario
            $identity = $jwtAuth->checkToken($token, true);

            // sacar la identidad del video en base al id
            $video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
                'id' => $id,
                'user' => $identity->sub,
            ]);

            // comprobar si el video existe y es propiedad del usuario identificado

            // if($video && is_object($video) && $identity->sub == $video->getUser()->getId()){
            if ($video && is_object($video)) {
                // devolver una respuesta
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'video' => $video,
                ];
            }
        }

        return $this->resjson($data);
    }

    public function remove(Request $request, JwtAuth $jwtAuth, $id = null)
    {
        // recoger la cabecera de autenticación
        $token = $request->headers->get('Authorization');

        // comprobar el token
        $authCheck = $jwtAuth->checkToken($token);

        // devolver una respuesta
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Vídeo no encontrado',
        ];

        if ($authCheck) {
            $identity = $jwtAuth->checkToken($token, true);

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $video = $doctrine->getRepository(Video::class)->findOneBy([
                'id' => $id
            ]);

            if ($video && is_object($video) && $identity->sub == $video->getUser()->getId()) {
                $em->remove($video);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'video' => $video,
                ];
            }
        }

        return $this->resjson($data);
    }
}