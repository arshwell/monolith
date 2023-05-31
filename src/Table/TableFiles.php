<?php

namespace Arshwell\Monolith\Table;

use Arshwell\Monolith\Table\Files\Image;
use Arshwell\Monolith\Table\Files\ImageGroup;
use Arshwell\Monolith\Table\Files\Doc;
use Arshwell\Monolith\Table\Files\DocGroup;
use Arshwell\Monolith\Table;

final class TableFiles {
    private $files = array(); // has files - images & docs

    function __construct (string $class, int $id_table = NULL, string $fileStorageKey = null) {
        foreach (($class)::FILES as $filekey => $info) {
            switch (($info['type'] ?? Table::FILE_IMAGE)) {
                case Table::FILE_IMAGE: {
                    $this->files[$filekey] = new Image($class, $id_table, $filekey, $fileStorageKey);
                    break;
                }
                case Table::FILE_IMAGE_GROUP: {
                    $this->files[$filekey] = new ImageGroup($class, $id_table, $filekey, $fileStorageKey);
                    break;
                }
                case Table::FILE_DOC: {
                    $this->files[$filekey] = new Doc($class, $id_table, $filekey, $fileStorageKey);
                    break;
                }
                case Table::FILE_DOC_GROUP: {
                    $this->files[$filekey] = new DocGroup($class, $id_table, $filekey, $fileStorageKey);
                    break;
                }
            }
        }
    }

    function toArray (): array {
        return $this->files;
    }

    function get (string $file): ?object {
        return ($this->files[$file] ?? NULL);
    }
}
