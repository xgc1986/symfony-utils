<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http;

/**
 * Class HttpException
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http
 */
abstract class HttpException extends \Symfony\Component\HttpKernel\Exception\HttpException
{
    /**
     * @var int
     */
    protected $status;

    /**
     * @var array
     */
    protected $extras;

    /**
     * HttpException constructor.
     *
     * @param int             $status
     * @param null|string     $message
     * @param array           $extras
     * @param null|\Throwable $exception
     */
    public function __construct(int $status, ?string $message, array $extras = [], ?\Throwable $exception = null)
    {
        $this->extras = $extras;
        $this->status = $status;
        $message      = $message ?? 'Exception has been thrown.';

        $diff = [];
        foreach ($extras as $key => $value) {
            $diff["%$key%"] = $value;
        }
        $message = strtr($message, $diff);

        parent::__construct($status, $message, $exception);

        if (\getenv('APP_ENV') !== 'prod' && \getenv('APP_DEBUG') === '1') {
            $this->extras['exception'] = \exception_to_array($this);
        }
    }

    /**
     * @return array
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getMessage();
    }
}
