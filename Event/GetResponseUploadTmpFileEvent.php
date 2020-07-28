<?php

namespace Sherlockode\AdvancedFormBundle\Event;

use Symfony\Component\HttpFoundation\Response;

class GetResponseUploadTmpFileEvent
{
    /**
     * @var Response
     */
    private $response;

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
}
