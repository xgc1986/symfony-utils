<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\Test;

use XgcSymfonyUtilsBundle\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Symfony\Component\BrowserKit\Request as DomRequest;

/**
 * Class Client
 *
 * @package App\Test
 */
class Client extends BaseClient
{

    /**
     * @param DomRequest $request
     *
     * @return Request
     */
    protected function filterRequest(DomRequest $request): Request
    {
        $httpRequest = Request::create(
            $request->getUri(),
            $request->getMethod(),
            $request->getParameters(),
            $request->getCookies(),
            $request->getFiles(),
            $request->getServer(),
            $request->getContent()
        );

        foreach ($this->filterFiles($httpRequest->files->all()) as $key => $value) {
            $httpRequest->files->set($key, $value);
        }

        return $httpRequest;
    }
}
