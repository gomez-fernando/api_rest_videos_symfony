<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// importar
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;

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
            // 'json' => $params,
        ];

        // comprobar y validar datos
        if ($json != null) {
            $name = (!empty($params->name)) ? $name = $params->name : null;
            $surname = (!empty($params->surname)) ? $surname = $params->surname : null;
            $nick = (!empty($params->nick)) ? $nick = $params->nick : null;
            $email = (!empty($params->email)) ? $email = $params->email : null;
            $password = (!empty($params->password)) ? $password = $params->password : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if (!empty($name) && !empty($surname) && !empty($nick) && !empty($email) && count($validate_email) == 0 && !empty($password)) {
                // si la validación es correcta crear el objeto usuario
                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setNick($nick);
                $user->setEmail($email);
                $user->setRole('ROLE_USER');
                $user->setCreatedAt(new \DateTime('now'));

                // cifrar la contraseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);
                // $data = $user;

                // comprobar si el usuario existe (duplicados)
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();

                $user_repo = $doctrine->getRepository((User::class));
                $isset_user = $user_repo->findBy(array(
                    'email' => $email,
                ));

                // si no existe, guardarlo en la db
                if (count($isset_user) == 0) {
                    $em->persist($user);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El usuario se ha registrado correctamente',
                        'user' => $user,
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'El email ya está registrado',
                        // 'json' => $params,
                    ];
                }
            }
        }
        // hacer respuesta en json
        return $this->resjson($data);
        // return new JsonResponse($data);
    }

    public function login(Request $request, JwtAuth $jwtAuth)
    {
        // recibir los datos por post
        $json = $request->get('json', null);
        // convertir el objeto json a un objeto de php
        $params = json_decode($json);

        // array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El email o la contraseña son incorrrectos',
        ];

        // comprobar y validar datos
        if ($json != null) {
            // var_dump($json);
            // die();
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken : null;

            $validator = Validation::createValidator();
            $validateEmail = $validator->validate($email, [
                new Email
            ]);

            if (!empty($email) && !empty($password) && count($validateEmail) == 0) {
                // cifrar la contraseña
                $pwd = hash('sha256', $password);

                // si es válido llamar al servicio jwt para que nos devuelva un token o un objeto
                if ($gettoken) {
                    $signIn = $jwtAuth->sigIn($email, $pwd, $gettoken);
                } else {
                    $signIn = $jwtAuth->sigIn($email, $pwd);
                }
                return new JsonResponse($signIn);
            }
        }
        // si es correcto damos una respuesta

        return $this->resjson($data);
    }

    public function edit(Request $request, JwtAuth $jwtAuth)
    {
        // recoger la cabecera de autentificación
        $token = $request->headers->get('Authorization');

        // crear un método para comprobar si el token es correcto
        $checkToken = $jwtAuth->checkToken($token);

        // respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario no actualizado'
        ];

        // si es correcto, hacer la actualizacion del usuario
        if ($checkToken) {
            // conseguir el entity manager
            $em = $this->getDoctrine()->getManager();

            // conseguir los datos del usuario autenticado, con el flag true nos devuelve el objeto completo
            $identity = $jwtAuth->checkToken($token, true);
            // var_dump($identity);
            // die();

            // conseguir el usuario a actualizar completo
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'id' => $identity->sub
            ]);

            // recoger los datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);

            // comprobar y validar los datos
            if (!empty($json)) {
                $name = (!empty($params->name)) ? $name = $params->name : null;
                $surname = (!empty($params->surname)) ? $surname = $params->surname : null;
                $nick = (!empty($params->nick)) ? $nick = $params->nick : null;
                $email = (!empty($params->email)) ? $email = $params->email : null;

                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);

                if (!empty($name) && !empty($surname) && !empty($nick) && !empty($email) && count($validate_email) == 0) {
                    // asignar nuevos datos al objeto usuario
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setNick($nick);
                    $user->setEmail($email);

                    // comprobar duplicados
                    $issetUser = $user_repo->findBy([
                        'email' => $email
                    ]);

                    if (count($issetUser) == 0 || $identity->email == $email) {
                        // guardar cambios en la base de datos
                        $em->persist($user);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Usuario actualizado correctamente',
                            'user' => $user,
                        ];
                    } else {
                        $data = [
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'Este email ya está registrado'
                        ];
                    }
                }
            }
        }

        return $this->resjson($data);
    }
}