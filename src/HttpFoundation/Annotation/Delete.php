<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Annotation Delete
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Annotation
 *
 * @Annotation
 */
class Delete extends Route
{

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return ['DELETE'];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return parent::getPath() ?: '';
    }
}
