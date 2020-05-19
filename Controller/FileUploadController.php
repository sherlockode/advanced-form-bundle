<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sherlockode\AdvancedFormBundle\Form\Type\EntityMappingType;
use Sherlockode\AdvancedFormBundle\Form\Type\UploadFileType;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param UploadManager          $uploadManager
     * @param MappingManager         $mappingManager
     * @param EntityManagerInterface $em
     */
    public function __construct(UploadManager $uploadManager, MappingManager $mappingManager, EntityManagerInterface $em)
    {
        $this->uploadManager = $uploadManager;
        $this->mappingManager = $mappingManager;
        $this->em = $em;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadFileAction(Request $request)
    {
        $data = $request->get('afb_upload_file');
        $form = $this->createForm(EntityMappingType::class, [], ['csrf_protection' => false]);
        $form->submit(['mapping' => $data['mapping'], 'entity' => $data['id']]);

        $mapping = $form->getData()['mapping'];
        $object = $form->getData()['entity'];

        $form = $this->createForm(UploadFileType::class, $object, ['csrf_protection' => false, 'mapping' => $mapping]);
        $form->submit([$mapping->fileProperty => $request->files->get('afb_upload_file')['file']]);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $uploadedFile = $form->get('file')->getData();
                $fileContainer = $form->get('fileContainer')->getData();
                try {
                    $this->em->flush();

                    $data = [
                        'id' => $fileContainer->getId(),
                        'filename' => $uploadedFile->getClientOriginalName(),
                    ];
                    $routeInfo = $mapping->route;
                    if (null !== $routeInfo) {
                        $params = [];
                        foreach ($routeInfo['parameters'] as $key => $parameter) {
                            $params[$key] = $parameter === '{id}' ? $fileContainer->getId() : $parameter;
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
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function removeFileAction(Request $request)
    {
        $data = $request->get('afb_remove_file');
        $form = $this->createForm(EntityMappingType::class, [], ['csrf_protection' => false]);
        $form->submit(['mapping' => $data['mapping'], 'fileEntity' => $data['id']]);

        $mapping = $form->getData()['mapping'];
        $object = $form->getData()['fileEntity'];

        if ($form->isSubmitted() && $form->isValid()) {
            $this->uploadManager->remove(
                $mapping,
                $object
            );

            return new JsonResponse();
        }

        return new JsonResponse([], 401);
    }
}
