<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Sherlockode\AdvancedFormBundle\Form\Type\RemoveFileType;
use Sherlockode\AdvancedFormBundle\Form\Type\UploadFileType;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class FileUploadController extends AbstractController
{
    /**
     * @var UploadManager
     */
    private $uploadManager;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * @var string
     */
    private $tmpUploadClass;

    /**
     * @var StorageInterface
     */
    private $tmpStorage;

    /**
     * FileUploadController constructor.
     *
     * @param UploadManager    $uploadManager
     * @param MappingManager   $mappingManager
     * @param string           $tmpUploadClass
     * @param StorageInterface $tmpStorage
     */
    public function __construct($uploadManager, $mappingManager, $tmpUploadClass, $tmpStorage)
    {
        $this->uploadManager = $uploadManager;
        $this->mappingManager = $mappingManager;
        $this->tmpUploadClass = $tmpUploadClass;
        $this->tmpStorage = $tmpStorage;
    }

    /**
     * @param Request        $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function uploadFileAction(Request $request)
    {
        $form = $this->createForm(UploadFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $uploadedFile = $form->get('file')->getData();
                try {
                    $object = $this->uploadManager->upload(
                        $uploadedFile,
                        $form->get('mapping')->getData(),
                        $form->get('id')->getData()
                    );

                    $data = [
                        'id' => $object->getId(),
                        'filename' => $uploadedFile->getClientOriginalName(),
                    ];
                    $routeInfo = $this->mappingManager->getMapping($form->get('mapping')->getData())->route;
                    if (null !== $routeInfo) {
                        $params = [];
                        foreach ($routeInfo['parameters'] as $key => $parameter) {
                            $params[$key] = $parameter === '{id}' ? $object->getId() : $parameter;
                        }

                        $data['path'] = $this->generateUrl($routeInfo['name'], $params);
                    }

                    return new JsonResponse($data);
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }

            return new JsonResponse(['error' => $form->getErrors(true)->__toString()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadTmpAction(Request $request)
    {
        $form = $this->createForm(UploadFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $uploadedFile = $form->get('file')->getData();
                    $file = $this->uploadManager->uploadTemporary($uploadedFile);

                    return new JsonResponse([
                        'key'   => $file->getKey(),
                        'token' => $file->getToken(),
                    ]);
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }

            return new JsonResponse(['error' => $form->getErrors(true)->__toString()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeTmpFileAction(Request $request)
    {
        $token = $request->get('token');
        $fileInfo = $this->getDoctrine()->getRepository($this->tmpUploadClass)->findOneBy(['token' => $token]);
        if ($fileInfo instanceof TemporaryUploadedFileInterface) {
            $this->uploadManager->removeTemporary($fileInfo);
        }

        return new JsonResponse();
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    public function viewUploadedFileAction($token)
    {
        $fileInfo = $this->getDoctrine()->getRepository($this->tmpUploadClass)->findOneBy(['token' => $token]);

        $data = $this->tmpStorage->read($fileInfo->getKey());

        return $this->createDownloadResponse(
            $data,
            $fileInfo->getKey()
        );
    }

    /**
     * @param string $data
     * @param string $filename
     * @param string $mimeType
     *
     * @return Response
     */
    private function createDownloadResponse($data, $filename, $mimeType = 'application/octet-stream')
    {
        $response = new Response($data);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');

        return $response;
    }

    /**
     * @Route("/sherlockodeadvancedform/remove", name="sherlockode_afb_remove")
     *
     * @param Request       $request
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function removeFileAction(Request $request)
    {
        $form = $this->createForm(RemoveFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->uploadManager->remove(
                    $form->get('mapping')->getData(),
                    $form->get('id')->getData(),
                    $form->get('remove')->getData()
                );
            } catch (\Exception $e) {
                throw $e;
            }

            return new JsonResponse();
        }

        throw new \Exception('Invalid form');
    }
}
