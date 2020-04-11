<?php

/**
 * This file is part of the "NFQ Bundles" package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\AdminBundle\Twig;

/**
 * Class IsLoadedExtension
 * @package Nfq\AdminBundle\Twig
 */
class IsLoadedExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getTests()
    {
        return [
            new \Twig\TwigTest('loaded', [$this, 'hasExtension'],[
                'is_safe' => ['html'],
                'needs_environment' => true // Tell twig we need the environment
            ]),
        ];
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    function hasExtension(\Twig\Environment $env,$name)
    {
        return $env->hasExtension($name);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'nfq_admin_extension_exists';
    }
}
