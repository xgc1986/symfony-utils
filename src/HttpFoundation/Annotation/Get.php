<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Annotation Get
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Annotation
 *
 * @Annotation
 */
class Get extends Route
{

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return ['GET'];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return parent::getPath() ?: '';
    }
}
