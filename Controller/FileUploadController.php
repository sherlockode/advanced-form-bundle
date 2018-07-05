<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Sherlockode\AdvancedFormBundle\Form\Type\RemoveFileType;
use Sherlockode\AdvancedFormBundle\Form\Type\UploadTempFileType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

    public function __construct($uploadManager, $mappingManager)
    {
        $this->uploadManager = $uploadManager;
        $this->mappingManager = $mappingManager;
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
                $this->uploadManager->upload(
                    $uploadedFile,
                    $form->get('mapping')->getData(),
                    $form->get('id')->getData()
                );
            } catch (\Exception $e) {
                throw $e;
            }

            $routeInfo = $this->mappingManager->getRouteProperty($form->get('mapping')->getData());
            $params = [];
            foreach ($routeInfo['parameters'] as $key => $parameter) {
                $params[$key] = $parameter === '{id}' ? $form->get('id')->getData() : $parameter;
            }

            return new JsonResponse([
                'path' => $this->generateUrl($routeInfo['name'], $params),
            ]);
        }

        throw new \Exception('Invalid form');
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
