<?php declare(strict_types=1);

namespace jschreuder\BookmarkBureau\OutputSpec;

/**
 * Specification for transforming domain objects into serializable arrays
 * 
 * OutputSpecs mirror InputSpecs, providing symmetry in the data flow:
 * - InputSpec:  array parsed from HTTP Request → validated array → domain
 * - OutputSpec: domain → array ready for HTTP Response
 * 
 * Functionally, OutputSpecs are serializers that transform domain objects
 * (entities, collections, value objects) into arrays suitable for JSON/XML
 * serialization. The naming reflects their architectural role as the output
 * boundary specification rather than just their technical function.
 * 
 * They may also be used to enrich given data if necessary.
 */
interface OutputSpecInterface
{
    /**
     * Takes an object and determines if it supports it
     */
    public function supports(object $data): bool;

    /**
     * Transform domain object(s) into array representation
     * 
     * This method takes domain objects (entities, collections, value objects)
     * and transforms them into plain arrays that can be serialized to JSON, XML,
     * or other formats.
     * 
     * @return array Serializable array representation
     * @throws \InvalidArgumentException When data is of unsupported type
     */
    public function transform(object $data): array;
}
