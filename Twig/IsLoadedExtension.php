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
                'needs_environment' => true // Tell twig we need the environment
            ]),
        ];
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    function hasExtension($name)
    {
        return isset($this->extensions[$name]);

        /**
         * This way of checking whether a particular extension exists 
         * or not has been taken from following link:
         * https://api.drupal.org/api/drupal/vendor%21twig%21twig%21lib%21Twig%21Environment.php/function/Twig_Environment%3A%3AhasExtension/8.2.x
         */
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
