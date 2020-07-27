<?php

namespace Sherlockode\AdvancedFormBundle\Event;

use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFileInterface;
use Symfony\Component\HttpFoundation\Response;

class GetResponseViewTmpFileEvent
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var TemporaryUploadedFileInterface
     */
    private $fileInfo;

    /**
     * @param TemporaryUploadedFileInterface $fileInfo
     */
    public function __construct(TemporaryUploadedFileInterface $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return TemporaryUploadedFileInterface
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }
}
