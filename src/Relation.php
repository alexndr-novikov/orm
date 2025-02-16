<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM;

/**
 * Enum of possible relation options.
 */
final class Relation
{
    // General options
    public const TYPE   = 0;
    public const TARGET = 1;
    public const SCHEMA = 2;
    public const LOAD   = 3;

    // Composite relation type without usage of external table.
    public const EMBEDDED = 1;

    // Relation types (default)
    public const HAS_ONE      = 10;
    public const HAS_MANY     = 11;
    public const BELONGS_TO   = 12;
    public const REFERS_TO    = 13;
    public const MANY_TO_MANY = 14;

    // Morphed relations
    public const BELONGS_TO_MORPHED = 20;
    public const MORPHED_HAS_ONE    = 21;
    public const MORPHED_HAS_MANY   = 23;

    // Custom morph key
    public const MORPH_KEY = 29;

    // Common relation options
    public const CASCADE   = 30;
    public const NULLABLE  = 31;
    public const OUTER_KEY = 32;
    public const INNER_KEY = 33;

    // Selections
    public const WHERE = 41;

    // Many-To-Many relation(s) options
    public const THOUGH_INNER_KEY = 50;
    public const THOUGH_OUTER_KEY = 51;
    public const THOUGH_ENTITY    = 52;
    public const THOUGH_WHERE     = 54;

    // Relation pre-fetch mode
    public const LOAD_PROMISE = 10;
    public const LOAD_EAGER   = 11;
}
