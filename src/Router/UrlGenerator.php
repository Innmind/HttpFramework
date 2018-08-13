<?php
declare(strict_types = 1);

namespace Innmind\HttpFramework\Router;

use Innmind\Router\{
    UrlGenerator as UrlGeneratorInterface,
    Route\Name,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\MapInterface;

/**
 * Used as an indirection in the container to reference the real router url generator
 */
final class UrlGenerator implements UrlGeneratorInterface
{
    private $generate;

    public function __construct(UrlGeneratorInterface $generate)
    {
        $this->generate = $generate;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Name $route, MapInterface $variables = null): UrlInterface
    {
        return ($this->generate)($route, $variables);
    }
}
