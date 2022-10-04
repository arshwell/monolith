<?php

namespace Arsavinel\Arshwell\Table;

interface TableSegment {
    function class (): string;

    function id (): ?int;

    function key (): string;

    function isTranslated (): bool;

    /**
     * Returns columns value (TableColumn) or file content (TableFiles)
     *
     * @return array|string|null
     */
    function value ();
}
