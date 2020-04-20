<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// importar
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use App\Entity\Video;

// use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
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
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $video_repo = $this->getDoctrine()->getRepository(Video::class);
        
        $users = $user_repo->findAll();
        $user = $user_repo->find(1);
        
        $videos = $video_repo->findAll();

        $data = [
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ];
        // foreach ($users as $user) {
        //     echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";

        //     foreach ($user->getVideos() as $video) {
        //         // también se pueden conseguir datos del usuario
        //         echo "<p>Título del vídeo: {$video->getTitle()} - Nick del usuario: {$video->getUser()->getNick()}</p>";
        //     }
        // }

        // die();

        // return $this->json($user);
        return $this->resjson($videos);
    }

    public function create(Request $request)
    {
        // recoger los datos por post
        $json = $request->get('json', null);

        // decodificar el json
        $params = json_decode($json);

        // respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha creado',
            'json' => $params,
        ];

        // comprobar y validar datos


        // si la validación es correcta crear el objeto usuario


        // cifrar la contraseña


        // comprobar si el usuario existe (duplicados)


        // si no existe, guardarlo en la db


        // hacer respuesta en json
        // return $this->resjson($data);
        return new JsonResponse($data);
    }
}