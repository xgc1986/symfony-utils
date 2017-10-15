<?php
declare(strict_types=1);

namespace XgcSymfonyUtilsBundle\Exception;

use Exception;
use Throwable;

/**
 * Class EntityNotFoundException
 *
 * @package App\Exception
 */
class EntityNotFoundException extends Exception
{
    /**
     * EntityNotFoundException constructor.
     *
     * @param string         $entity
     * @param string         $id
     * @param Throwable|null $previous
     */
    public function __construct(string $entity, string $id, Throwable $previous = null)
    {
        parent::__construct("Entity '$entity' with id '$id' not found", 0, $previous);
    }
}
