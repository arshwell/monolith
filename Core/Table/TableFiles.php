<?php

namespace Arsavinel\Arshwell\Table;

use Arsavinel\Arshwell\Table\Files\Image;
use Arsavinel\Arshwell\Table\Files\ImageGroup;
use Arsavinel\Arshwell\Table\Files\Doc;
use Arsavinel\Arshwell\Table\Files\DocGroup;
use Arsavinel\Arshwell\Table;

final class TableFiles {
    private $files = array(); // has files - images & docs

    function __construct (string $class, int $id_table = NULL) {
        foreach (($class)::FILES as $filekey => $info) {
            switch (($info['type'] ?? Table::IMAGE)) {
                case Table::IMAGE: {
                    $this->files[$filekey] = new Image($class, $id_table, $filekey);
                    break;
                }
                case Table::IMAGE_GROUP: {
                    $this->files[$filekey] = new ImageGroup($class, $id_table, $filekey);
                    break;
                }
                case Table::DOC: {
                    $this->files[$filekey] = new Doc($class, $id_table, $filekey);
                    break;
                }
                case Table::DOC_GROUP: {
                    $this->files[$filekey] = new DocGroup($class, $id_table, $filekey);
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
