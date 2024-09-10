<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Video;
use App\Repository\VideoRepository;
use App\Services\JwtAuth;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/video')]
class VideoController extends AbstractController
{

    private function resjson($data, SerializerInterface $serializer)
    {
        $json = $serializer->serialize($data, 'json');
        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    #[Route('/new', name: 'app_video_new', methods: ['POST'])]
    #[Route('/edit/{id}', name: 'app_video_edit', methods: ['PUT'])]
    public function create(SerializerInterface $serializer, EntityManagerInterface $em, Request $request, JwtAuth $jwt_auth, $id = null)
    {

        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Failed to create a video'
        ];
        if ($authCheck) {
            $json = $request->get('json', null);
            $params = json_decode($json, true);
            $identity = $jwt_auth->checkToken($token, true);
            if (!empty($json)) {
                $user_id = $identity->sub != null ? $identity->sub : null;
                $title = !empty($params['title']) ? $params['title'] : null;
                $description = !empty($params['description']) ? $params['description'] : null;
                $url = !empty($params['url']) ? $params['url'] : null;
                if (!empty($user_id) && !empty($title)) {
                    $user_repo = $em->getRepository(User::class);
                    $user = $user_repo->findOneBy(['id' => $identity->sub]);
                    if ($id == null) {
                        $video = new Video();
                        $video->setUser($user);
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setUrl($url);
                        $video->setStatus('normal');

                        $updatedAt = new \DateTime('now');
                        $video->setUpdateAt($updatedAt);
                        $em->persist($video);
                        $em->flush();
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Video created successfully',
                            'video' => $video
                        ];
                    } else {
                        $video = $em->getRepository(Video::class)->findOneBy(['id' => $id, 'user' => $identity->sub]);
                        if ($video && is_object($video)) {
                            $video->setTitle($title);
                            $video->setDescription($description);
                            $video->setUrl($url);
                            $updatedAt = new \DateTime('now');
                            $video->setUpdateAt($updatedAt);
                            $em->persist($video);
                            $em->flush();
                            $data = [
                                'status' => 'success',
                                'code' => 200,
                                'message' => 'Video updated successfully',
                                'video' => $video
                            ];
                        }
                    }
                }
            }
        }
        return $this->resjson($data, $serializer);
    }

    #[Route('/list', name: 'app_video_show', methods: ['GET'])]
    public function videos(SerializerInterface $serializer, Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator, EntityManagerInterface $em)
    {
        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'There are not videos to show'
        ];
        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);
            $dql = "SELECT v FROM App\Entity\Video v WHERE v.user = ($identity->sub) ORDER BY v.id DESC";
            $query = $em->createQuery($dql);
            $page = $request->query->getInt('page', 1);
            $items_per_page = 4;
            $pagination = $paginator->paginate($query, $page, $items_per_page);
            $total = $pagination->getTotalItemCount();
            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'videos' => $pagination,
                'user_id' => $identity->sub

            ];
        }
        return $this->resjson($data, $serializer);
    }

    #[Route('/detail/{id}', name: 'app_video_detail', methods: ['GET'])]
    public function video(SerializerInterface $serializer, Request $request, JwtAuth $jwt_auth, VideoRepository $videoRepository, $id = null)
    {
        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);
        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);
            $video = $videoRepository->findOneBy([
                'id' => $id,
                'user' => $identity->sub
            ]);
            if ($video && is_object($video)) {
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'video' => $video
                ];

            } else {
                $data = [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Video not found'
                ];
            }
        }
        return $this->resjson($data, $serializer);
    }

    #[Route('/remove/{id}', name: 'app_video_remove', methods: ['DELETE'])]
    public function remove(SerializerInterface $serializer, Request $request, JwtAuth $jwt_auth, EntityManagerInterface $em, $id = null)
    {
        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);
        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);
            $video = $em->getRepository(Video::class)->findOneBy([
                'id' => $id,
                'user' => $identity->sub
            ]);
        }
        if ($video && is_object($video)) {
            $em->remove($video);
            $em->flush();
            $data = [
                'status' => 'success',
                'code' => 200,
                'video' => $video
            ];

        } else {
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'Video not found'
            ];
        }
        return $this->resjson($data, $serializer);
    }
}