<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Annotation Put
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Annotation
 *
 * @Annotation
 */
class Put extends Route
{

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return ['PUT'];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return parent::getPath() ?: '';
    }
}
