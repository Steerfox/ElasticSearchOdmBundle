<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle;

use Steerfox\ElasticsearchBundle\DependencyInjection\Compiler\ManagerFactoryPass;
use Steerfox\ElasticsearchBundle\DependencyInjection\Compiler\MappingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Steerfox Elasticsearch bundle system file required by kernel.
 */
class SteerfoxElasticsearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MappingPass());
    }
}
