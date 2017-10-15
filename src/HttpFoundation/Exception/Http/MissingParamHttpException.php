<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class MissingParamHttpException
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http
 */
class MissingParamHttpException extends HttpException
{
    /**
     * ResourceNotFoundHttpException constructor.
     *
     * @param string         $name
     * @param Throwable|null $exception
     */
    public function __construct(string $name, Throwable $exception = null)
    {
        parent::__construct(Response::HTTP_NOT_FOUND, 'Missing Param "%param%".', ['param' => $name], $exception);
    }
}
