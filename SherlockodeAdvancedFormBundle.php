<?php

namespace Sherlockode\AdvancedFormBundle;

use Sherlockode\AdvancedFormBundle\DependencyInjection\Compiler\DependentEntityMapperPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SherlockodeAdvancedFormBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DependentEntityMapperPass());
    }
}
