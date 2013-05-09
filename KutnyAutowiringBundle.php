<?php

namespace Kutny\AutowiringBundle;

use Kutny\AutowiringBundle\Compiler\ClassConstructorFiller;
use Kutny\AutowiringBundle\Compiler\ClassListBuilder;
use Kutny\AutowiringBundle\Compiler\ParameterProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KutnyAutowiringBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AutowiringCompilerPass(new ClassConstructorFiller(new ParameterProcessor()), new ClassListBuilder())
        );
    }

}
