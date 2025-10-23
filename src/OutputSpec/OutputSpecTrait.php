<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

use InvalidArgumentException;

/**
 * This trait implements the transform method to use supports() and throw an
 * exception if it's not supported, and delegates the transform activities to
 * a new abstract private doTransform() method. Thus preventing the same 
 * if-statement in every OutputSpec
 */
trait OutputSpecTrait
{
    abstract public function supports(object $data): bool;

    public function transform(object $data): array
    {
        if (!$this->supports($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s does not support objects of type %s',
                    static::class,
                    get_class($data)
                )
            );
        }
        
        return $this->doTransform($data);
    }
    
    abstract private function doTransform(object $data): array;
}