<?php

namespace Arsh\Core\Table;

interface TableSegment {
    function class (): string;

    function id (): ?int;

    function key (): string;

    function isTranslated (): bool;
}
