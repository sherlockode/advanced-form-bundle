<?php

namespace Sherlockode\AdvancedFormBundle\Twig\Extension;

use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
use Sherlockode\AdvancedFormBundle\Manager\UploadManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class UploaderExtension
 */
class UploaderExtension extends AbstractExtension
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
            new TwigFunction('sherlockode_afb_asset', [$this, 'getAsset']),
            new TwigFunction('sherlockode_afb_filename', [$this, 'getFilename']),
        ];
    }

    public function getAsset($type, $id)
    {
        $routeInfo = $this->mappingManager->getMapping($type)->route;
        if (null === $routeInfo || null === $id) {
            return null;
        }
        $params = [];
        foreach ($routeInfo['parameters'] as $key => $parameter) {
            $params[$key] = $parameter === '{id}' ? $id : $parameter;
        }
        return $this->urlGenerator->generate($routeInfo['name'], $params);
    }

    public function getFilename($type, $object)
    {
        $mapping = $this->mappingManager->getMapping($type);

        return $this->uploadManager->getFilename($mapping, $object);
    }
}
