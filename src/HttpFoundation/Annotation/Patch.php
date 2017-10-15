<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Annotation Patch
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Annotation
 *
 * @Annotation
 */
class Patch extends Route
{

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return ['PATCH'];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return parent::getPath() ?: '';
    }
}
