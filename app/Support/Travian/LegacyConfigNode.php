<?php

namespace App\Support\Travian;

use ArrayAccess;
use IteratorAggregate;
use Traversable;

final class LegacyConfigNode implements ArrayAccess, IteratorAggregate
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private array $items)
    {
    }

    public function __get(string $name): mixed
    {
        if (!array_key_exists($name, $this->items)) {
            return null;
        }

        return $this->transform($this->items[$name]);
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items[(string) $offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[(string) $offset]);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $value) {
            yield $key => $this->transform($value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    private function transform(mixed $value): mixed
    {
        if (is_array($value)) {
            if ($this->isAssociative($value)) {
                return new self($value);
            }

            return array_map(function (mixed $item): mixed {
                if (is_array($item)) {
                    return $this->transform($item);
                }

                return $item;
            }, $value);
        }

        return $value;
    }

    private function isAssociative(array $value): bool
    {
        $keys = array_keys($value);

        return $keys !== array_keys($keys);
    }
}
