<?php

namespace Sherlockode\AdvancedFormBundle\Controller;

use Sherlockode\AdvancedFormBundle\Form\Type\RemoveFileType;
use Sherlockode\AdvancedFormBundle\Form\Type\UploadTempFileType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileUploadController extends Controller
{
    /**
     * @Route("/sherlockodeadvancedform/upload", name="sherlockode_afb_upload")
     *
     * @param Request       $request
     * @param UploadManager $uploadManager
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function uploadFileAction(Request $request, UploadManager $uploadManager)
    {
        $form = $this->createForm(UploadTempFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            try {
                $file = $uploadManager->upload(
                    $uploadedFile,
                    $form->get('mapping')->getData(),
                    $form->get('id')->getData(),
                    $form->get('field')->getData()
                );
            } catch (\Exception $e) {
                throw $e;
            }

            return new JsonResponse(
                [
                    'pathname' => $file->getPathname(),
                    'size' => $uploadedFile->getClientSize(),
                    'mime-type' => $uploadedFile->getClientMimeType(),
                ]
            );
        }

        throw new \Exception('Invalid form');
    }

    /**
     * @Route("/sherlockodeadvancedform/remove", name="sherlockode_afb_remove")
     *
     * @param Request       $request
     * @param UploadManager $uploadManager
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function removeFileAction(Request $request, UploadManager $uploadManager)
    {
        $form = $this->createForm(RemoveFileType::class, [], ['csrf_protection' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $uploadManager->remove(
                    $form->get('mapping')->getData(),
                    $form->get('id')->getData(),
                    $form->get('field')->getData(),
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
