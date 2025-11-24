<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use InvalidArgumentException;

/**
 * This trait implements the transform method to use supports() and throw an
 * exception if it's not supported, and delegates the transform activities to
 * a new abstract private doTransform() method. Thus preventing the same
 * if-statement in every OutputSpec
 *
 * @template T of object
 */
trait OutputSpecTrait
{
    abstract public function supports(object $data): bool;

    /**
     * Transforms the given domain object into an array representation.
     *
     * @param T $data Domain object to transform into array representation
     * @return array<string, mixed> Serializable array representation
     * @throws InvalidArgumentException When data is of unsupported type
     */
    public function transform(object $data): array
    {
        if (!$this->supports($data)) {
            throw new InvalidArgumentException(
                static::class .
                    " does not support objects of type " .
                    \get_class($data),
            );
        }

        return $this->doTransform($data);
    }

    /**
     * @param T $data Domain object to transform into array representation
     * @return array<string, mixed> Serializable array representation
     */
    abstract private function doTransform(object $data): array;
}
