<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sherlockode\AdvancedFormBundle\Form\Type\RemoveFileType;
use Sherlockode\AdvancedFormBundle\Form\Type\UploadTempFileType;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileUploadController extends Controller
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

    public function __construct($uploadManager, $mappingManager, $tmpUploadClass)
    {
        $this->uploadManager = $uploadManager;
        $this->mappingManager = $mappingManager;
        $this->tmpUploadClass = $tmpUploadClass;
    }

    /**
     * @param Request        $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function uploadFileAction(Request $request)
    {
        $form = $this->createForm(UploadTempFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            try {
                $object = $this->uploadManager->upload(
                    $uploadedFile,
                    $form->get('mapping')->getData(),
                    $form->get('id')->getData()
                );
            } catch (\Exception $e) {
                throw $e;
            }

            $data = ['id' => $object->getId()];
            $routeInfo = $this->mappingManager->getRouteProperty($form->get('mapping')->getData());
            if ($routeInfo) {
                $params = [];
                foreach ($routeInfo['parameters'] as $key => $parameter) {
                    $params[$key] = $parameter === '{id}' ? $form->get('id')->getData() : $parameter;
                }

                $data['path'] = $this->generateUrl($routeInfo['name'], $params);
            }
            return new JsonResponse($data);
        }

        throw new \Exception('Invalid form');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadTmpAction(Request $request)
    {
        $form = $this->createForm(UploadTempFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            $file = $this->uploadManager->uploadTemporary($uploadedFile);

            return new JsonResponse([
                'key' => $file->getKey(),
                'token' => $file->getToken(),
            ]);
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

        $file = null;

        $file = $this->get('sherlockode_afb.storage.tmp_storage')->read($fileInfo->getKey());
        $stream = fopen($file, 'rb');

        return $this->createDownloadResponse(
            $stream,
            $fileInfo->getKey(),
            null === $file ? null : $file->getMimeType()
        );
    }

    /**
     * @param resource $stream
     * @param string   $filename
     * @param string   $mimeType
     *
     * @return StreamedResponse
     */
    private function createDownloadResponse($stream, $filename, $mimeType = 'application/octet-stream')
    {
        $response = new StreamedResponse(function () use ($stream) {
            stream_copy_to_stream($stream, fopen('php://output', 'wb'));
        });

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
