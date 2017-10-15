<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\HttpFoundation;

use XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http\InvalidParamHttpException;
use XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http\MissingParamHttpException;
use XgcSymfonyUtilsBundle\HttpFoundation\Exception\Http\ResourceNotFoundHttpException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Throwable;
use UnexpectedValueException;

/**
 * Class Request
 *
 * @package XgcSymfonyUtilsBundle\HttpFoundation
 */
class Request extends BaseRequest implements ContainerAwareInterface
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @return Request|array|mixed
     * @throws \LogicException
     */
    public static function createFromGlobals()
    {
        // With the php's bug #66606, the php's built-in web server
        // stores the Content-Type and Content-Length header values in
        // HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
        $server = $_SERVER;
        if ('cli-server' === \PHP_SAPI) {
            if (\array_key_exists('HTTP_CONTENT_LENGTH', $_SERVER)) {
                $server['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH'];
            }
            if (\array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
                $server['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE'];
            }
        }

        $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $server);

        if ($request->headers->get('CONTENT_TYPE') &&
            0 === \strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded') &&
            \in_array(\strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'], true)
        ) {
            \parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    /**
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null  $content
     *
     * @return array|mixed|static
     * @throws LogicException
     */
    private static function createRequestFromFactory(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    )
    {
        if (self::$requestFactory) {
            $request = \call_user_func(
                self::$requestFactory,
                $query,
                $request,
                $attributes,
                $cookies,
                $files,
                $server,
                $content
            );

            if (!$request instanceof self) {
                throw new LogicException(
                    'The Request factory must return an instance of Symfony\Component\HttpFoundation\Request.'
                );
            }

            return $request;
        }

        return new static($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * Creates a Request based on a given URI and configuration.
     *
     * The information contained in the URI always take precedence
     * over the other information (server and parameters).
     *
     * @param string $uri        The URI
     * @param string $method     The HTTP method
     * @param array  $parameters The query (GET) or request (POST) parameters
     * @param array  $cookies    The request cookies ($_COOKIE)
     * @param array  $files      The request files ($_FILES)
     * @param array  $server     The server parameters ($_SERVER)
     * @param string $content    The raw body data
     *
     * @return static
     * @throws \LogicException
     */
    public static function create(
        $uri,
        $method = 'GET',
        $parameters = [],
        $cookies = [],
        $files = [],
        $server = [],
        $content = null
    ) {
        $server = \array_replace([
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_USER_AGENT'      => 'Symfony/3.X',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR'          => '127.0.0.1',
            'SCRIPT_NAME'          => '',
            'SCRIPT_FILENAME'      => '',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_TIME'         => \time()
        ], $server);

        $server['PATH_INFO']      = '';
        $server['REQUEST_METHOD'] = \strtoupper($method);

        $components = \parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST']   = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS']       = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST']   = $server['HTTP_HOST'] . ':' . $components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        switch (\strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
            // no break
            case 'PATCH':
                $request = $parameters;
                $query   = [];
                break;
            default:
                $request = [];
                $query   = $parameters;
                break;
        }

        $queryString = '';
        if (isset($components['query'])) {
            \parse_str(\html_entity_decode($components['query']), $qs);

            if ($query) {
                $query       = \array_replace($qs, $query);
                $queryString = \http_build_query($query, '', '&');
            } else {
                $query       = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = \http_build_query($query, '', '&');
        }

        $server['REQUEST_URI']  = $components['path'] . ('' !== $queryString ? '?' . $queryString : '');
        $server['QUERY_STRING'] = $queryString;

        return self::createRequestFromFactory($query, $request, [], $cookies, $files, $server, $content);
    }

    /**
     * @param ContainerInterface $container
     *
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->doctrine = $container ? $container->get('doctrine') : null;
    }

    /**
     * @param string $param
     *
     * @return bool
     */
    public function has(string $param): bool
    {
        return $this->query->has($param) || $this->request->has($param);
    }

    /**
     * @param string   $param
     * @param int|null $default
     *
     * @return int|null
     *
     * @throws InvalidParamHttpException
     */
    public function getInt(string $param, ?int $default = null): ?int
    {
        if ($this->has($param)) {
            try {
                return (int)$this->get($param);
            } catch (Throwable $e) {
                throw new InvalidParamHttpException($param, $e);
            }
        }

        return $default;
    }

    /**
     * @param string     $param
     * @param float|null $default
     *
     * @return float|null
     * @throws InvalidParamHttpException
     */
    public function getFloat(string $param, ?float $default = null): ?float
    {
        if ($this->has($param)) {
            try {
                return (float)$this->get($param);
            } catch (Throwable $e) {
                throw new InvalidParamHttpException($param, $e);
            }
        }

        return $default;
    }

    /**
     * @param string    $param
     * @param bool|null $default
     *
     * @return bool|null
     * @throws InvalidParamHttpException
     */
    public function getBool(string $param, ?bool $default = null): ?bool
    {
        if ($this->has($param)) {
            $res = \strtolower($this->get($param));

            if ($res === 'true' || $res === '1') {
                return true;
            }

            if ($res === 'false' || $res === '0') {
                return false;
            }

            throw new InvalidParamHttpException($param);
        }

        return $default;
    }

    /**
     * @param string        $param
     * @param null|DateTime $default
     *
     * @return null|DateTime
     * @throws InvalidParamHttpException
     */
    public function getDateTime(string $param, ?DateTime $default = null): ?DateTime
    {
        $time = $this->get($param);
        if ($time !== null) {
            try {
                return new DateTime($this->get($param));
            } catch (Throwable $e) {
                throw new InvalidParamHttpException($param, $e);
            }
        }

        return $default;
    }

    /**
     * @param string     $param
     * @param array|null $default
     *
     * @return array|null
     * @throws InvalidParamHttpException
     */
    public function getArray(string $param, array $default = []): ?array
    {
        if ($this->has($param)) {
            $ret = \json_decode($this->get($param), true);

            if (\json_last_error() === \JSON_ERROR_NONE) {
                return $ret;
            }

            throw new InvalidParamHttpException($param);
        }

        return $default;
    }

    /**
     * @param string     $param
     * @param array|null $default
     *
     * @return array|null
     */
    public function getList(string $param, array $default = []): ?array
    {
        if ($this->has($param)) {
            $res = $this->get($param);
            if (is_iterable($res)) {
                return $res;
            }

            return [$res];
        }

        return $default;
    }

    /**
     * @param string $file
     *
     * @return UploadedFile
     */
    public function getFile(string $file): ?UploadedFile
    {
        return $this->files->get($file);
    }

    /**
     * @param string $fqn
     * @param string $name
     * @param string $arg
     *
     * @return Entity|object|null
     */
    public function getEntity(string $fqn, string $name, string $arg = ''): ?Entity
    {
        $id = $this->get($name);
        if ($id) {
            if ($arg) {
                return $this->doctrine->getRepository($fqn)->findOneBy([$arg, $id]);
            }

            return $this->doctrine->getRepository($fqn)->find($id);
        }

        return null;
    }

    /**
     * @param string $fqn
     * @param string $name
     * @param string $arg
     *
     * @return array
     * @throws InvalidParamHttpException
     */
    public function getEntities(string $fqn, string $name, string $arg): array
    {
        $value = $this->get($name);

        try {
            return $this->doctrine->getRepository($fqn)->findBy([$arg, $value]);
        } catch (UnexpectedValueException $e) {
            throw new InvalidParamHttpException($value, $e);
        }
    }

    /**
     * @param string $param
     *
     * @return string
     * @throws MissingParamHttpException
     */
    public function check(string $param): string
    {
        $val = $this->get($param);
        if (!$val) {
            throw new MissingParamHttpException($param);
        }

        return $val;
    }

    /**
     * @param string $param
     *
     * @return int
     * @throws MissingParamHttpException
     * @throws InvalidParamHttpException
     */
    public function checkInt(string $param): int
    {
        $this->check($param);

        return $this->getInt($param);
    }

    /**
     * @param string $param
     *
     * @return float
     * @throws InvalidParamHttpException
     * @throws MissingParamHttpException
     */
    public function checkFloat(string $param): float
    {
        $this->check($param);

        return $this->getFloat($param);
    }

    /**
     * @param string $param
     *
     * @return bool
     * @throws MissingParamHttpException
     * @throws InvalidParamHttpException
     */
    public function checkBool(string $param): bool
    {
        $this->check($param);

        return $this->getBool($param);
    }

    /**
     * @param string $param
     *
     * @return DateTime
     * @throws InvalidParamHttpException
     * @throws MissingParamHttpException
     */
    public function checkDateTime(string $param): DateTime
    {
        $this->check($param);

        return $this->getDateTime($param);
    }

    /**
     * @param string $param
     *
     * @return array
     * @throws MissingParamHttpException
     * @throws InvalidParamHttpException
     */
    public function checkArray(string $param): array
    {
        $this->check($param);

        return $this->getArray($param);
    }

    /**
     * @param string $param
     *
     * @return array
     * @throws MissingParamHttpException
     */
    public function checkList(string $param): array
    {
        $this->check($param);

        return $this->getList($param);
    }

    /**
     * @param string $file
     *
     * @return UploadedFile
     * @throws MissingParamHttpException
     */
    public function checkFile(string $file): UploadedFile
    {
        $res = $this->getFile($file);
        if (!$res) {
            throw new MissingParamHttpException($file);
        }

        return $res;
    }

    /**
     * @param string $fqn
     * @param string $name
     * @param string $arg
     *
     * @return Entity|null
     *
     * @throws ResourceNotFoundHttpException
     * @throws MissingParamHttpException
     */
    public function checkEntity(string $fqn, string $name, string $arg = ''): Entity
    {
        $this->check($name);
        $entity = $this->getEntity($fqn, $name, $arg);
        if (!$entity) {
            throw new ResourceNotFoundHttpException();
        }

        return $entity;
    }

    /**
     * @param string $fqn
     * @param string $name
     * @param string $arg
     *
     * @return Entity[]
     * @throws InvalidParamHttpException
     * @throws MissingParamHttpException
     */
    public function checkEntities(string $fqn, string $name, string $arg = ''): array
    {
        $this->check($name);

        return $this->getEntities($fqn, $name, $arg);
    }
}
