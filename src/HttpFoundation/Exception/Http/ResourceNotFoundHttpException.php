<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class ResourceNotFoundHttpException
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http
 */
class ResourceNotFoundHttpException extends HttpException
{
    /**
     * ResourceNotFoundHttpException constructor.
     *
     * @param Throwable|null $exception
     */
    public function __construct(Throwable $exception = null)
    {
        parent::__construct(Response::HTTP_NOT_FOUND, 'Resource Not Found.', [], $exception);
    }
}
