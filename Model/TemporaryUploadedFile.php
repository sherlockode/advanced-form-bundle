<?php

namespace Sherlockode\AdvancedFormBundle\Model;

/**
 * Class UploadedFile
 */
class TemporaryUploadedFile implements TemporaryUploadedFileInterface
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }
}
