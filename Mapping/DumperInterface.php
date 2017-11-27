<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Mapping;

/**
 * DumperInterface is the interface implemented by elasticsearch document annotations.
 */
interface DumperInterface
{
    /**
     * Dumps properties into array.
     *
     * @param array $exclude Properties array to exclude from dump.
     *
     * @return array
     */
    public function dump(array $exclude = []);
}
