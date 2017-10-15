<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class InvalidParamHttpException
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http
 */
class InvalidParamHttpException extends HttpException
{
    /**
     * InvalidParamHttpException constructor.
     *
     * @param string          $name
     * @param Throwable|null $exception
     */
    public function __construct(string $name, Throwable $exception = null)
    {
        parent::__construct(Response::HTTP_CONFLICT, 'Invalid Param "%name%".', ['param' => $name], $exception);
    }
}
