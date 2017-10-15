<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class InternalServerErrorHttpException
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http
 */
class InternalServerErrorHttpException extends HttpException
{
    /**
     * InternalServerErrorHttpException constructor.
     *
     * @param string          $message
     * @param Throwable|null $exception
     */
    public function __construct(string $message, Throwable $exception = null)
    {
        if (\getenv('APP_ENV') === 'prod') {
            $message = 'Internal Server Error.';
        }
        parent::__construct(Response::HTTP_INTERNAL_SERVER_ERROR, $message, [], $exception);
    }
}
