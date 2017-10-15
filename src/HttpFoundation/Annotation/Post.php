<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Annotation Post
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Annotation
 *
 * @Annotation
 */
class Post extends Route
{

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return ['POST'];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return parent::getPath() ?: '';
    }
}
