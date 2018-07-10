<?php

namespace Sherlockode\AdvancedFormBundle\Twig\Extension;

use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class UploaderExtension
 */
class UploaderExtension extends \Twig_Extension
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var MappingManager
     */
    private $mappingManager;

    /**
     * @var UploadManager
     */
    private $uploadManager;

    public function __construct(UrlGeneratorInterface $urlGenerator, MappingManager $mappingManager, UploadManager $uploadManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->mappingManager = $mappingManager;
        $this->uploadManager = $uploadManager;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sherlockode_afb_asset', [$this, 'getAsset']),
            new \Twig_SimpleFunction('sherlockode_afb_filename', [$this, 'getFilename']),
        ];
    }

    public function getAsset($type, $id)
    {
        $routeInfo = $this->mappingManager->getRouteProperty($type);
        $params = [];
        foreach ($routeInfo['parameters'] as $key => $parameter) {
            $params[$key] = $parameter === '{id}' ? $id : $parameter;
        }
        return $this->urlGenerator->generate($routeInfo['name'], $params);
    }

    public function getFilename($type, $object)
    {
        return $this->uploadManager->getFilename($type, $object);
    }
}
