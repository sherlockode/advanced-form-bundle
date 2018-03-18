<?php

namespace Sherlockode\AdvancedFormBundle\Twig\Extension;

use Sherlockode\AdvancedFormBundle\Manager\MappingManager;
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

    public function __construct(UrlGeneratorInterface $urlGenerator, MappingManager $mappingManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->mappingManager = $mappingManager;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sherlockode_afb_asset', [$this, 'getAsset']),
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
}
