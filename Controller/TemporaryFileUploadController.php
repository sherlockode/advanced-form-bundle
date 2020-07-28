<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Sherlockode\AdvancedFormBundle\Event\GetResponseRemoveTmpFileEvent;
use Sherlockode\AdvancedFormBundle\Event\GetResponseUploadTmpFileEvent;
use Sherlockode\AdvancedFormBundle\Event\GetResponseViewTmpFileEvent;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Sherlockode\AdvancedFormBundle\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Kernel;

class TemporaryFileUploadController extends AbstractController
{
    /**
     * @var UploadManager
     */
    private $uploadManager;

    /**
     * @var string
     */
    private $tmpUploadClass;

    /**
     * @var StorageInterface
     */
    private $tmpStorage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * FileUploadController constructor.
     *
     * @param UploadManager            $uploadManager
     * @param string                   $tmpUploadClass
     * @param StorageInterface         $tmpStorage
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        UploadManager $uploadManager,
        string $tmpUploadClass,
        StorageInterface $tmpStorage,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->uploadManager = $uploadManager;
        $this->tmpUploadClass = $tmpUploadClass;
        $this->tmpStorage = $tmpStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadTmpAction(Request $request)
    {
        $event = new GetResponseUploadTmpFileEvent();
        if (Kernel::VERSION_ID < 40300) {
            $this->eventDispatcher->dispatch(get_class($event), $event);
        } else {
            $this->eventDispatcher->dispatch($event);
        }
        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        $form = $this->createForm(FileType::class, null, ['csrf_protection' => false]);
        $form->submit($request->files->get('afb_upload_file')['file']);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $uploadedFile = $form->getData();
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
            $event = new GetResponseRemoveTmpFileEvent($fileInfo);
            if (Kernel::VERSION_ID < 40300) {
                $this->eventDispatcher->dispatch(get_class($event), $event);
            } else {
                $this->eventDispatcher->dispatch($event);
            }
            if ($event->getResponse() !== null) {
                return $event->getResponse();
            }

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

        if (!$fileInfo instanceof TemporaryUploadedFileInterface) {
            throw $this->createNotFoundException();
        }

        $event = new GetResponseViewTmpFileEvent($fileInfo);
        if (Kernel::VERSION_ID < 40300) {
            $this->eventDispatcher->dispatch(get_class($event), $event);
        } else {
            $this->eventDispatcher->dispatch($event);
        }
        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

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
}
