<?php

namespace Arsavinel\Arshwell\Table\Files;

use Arsavinel\Arshwell\Table\TableSegment;
use Arsavinel\Arshwell\Tygh\Upload;
use Arsavinel\Arshwell\Folder;
use Arsavinel\Arshwell\File;
use Arsavinel\Arshwell\Func;
use Arsavinel\Arshwell\Web;
use Arsavinel\Arshwell\ENV;

final class DocGroup implements TableSegment {
    private $class;
    private $id_table = NULL;
    private $filekey;
    private $folder;
    private $urls = array(); // if no files in uploads/

    function __construct (string $class, int $id_table = NULL, string $filekey) {
        $this->class    = $class;
        $this->id_table = $id_table;
        $this->filekey  = $filekey;
        $this->folder   = (Folder::encode($class) .'/'. $id_table .'/'. $filekey);

        $files = File::tree(ENV::uploads('files'). $this->folder, NULL, false, true);

        if ($files) {
            $site = Web::site();

            foreach ((($this->class)::TRANSLATOR)::LANGUAGES as $language) {
                if (!isset($files[$language])) {
                    $first_lang = array_key_first($files);

                    $files[$language] = $files[$first_lang];
                    Folder::copy(ENV::uploads('files'). $this->folder .'/'. $first_lang, ENV::uploads('files'). $this->folder .'/'. $language);
                }

                foreach ($files[$language] as $filename) {
                    $this->urls[$language][] = ($site .ENV::uploads('files'). $this->folder .'/'. $language .'/'. $filename);
                }
            }
        }
    }

    function class (): string {
        return $this->class;
    }

    function id (): ?int {
        return $this->id_table;
    }

    function key (): string {
        return $this->filekey;
    }

    function isTranslated (): bool {
        return true;
    }

    function urls (string $lang = NULL): array {
        return $this->urls[($lang ?: (($this->class)::TRANSLATOR)::get())] ?? array();
    }

    function __call (string $method, array $args) {
        return $this->{$method}; // class, id_table, filekey, folder
    }

    function insert (array $data, string $language = NULL): void {
        $site       = Web::site();
        $language   = ($language ?: (($this->class)::TRANSLATOR)::default());

        for ($i=0; $i<count($data['name']); $i++) {
            $dirname = (ENV::uploads('files').$this->folder.'/'.$language);

            if (is_dir($dirname) || mkdir($dirname, 0755, true)) {
                copy($data['tmp_name'][$i], $dirname.'/'.$data['name'][$i]);
            }

            $this->urls[$language][] = $site.$dirname .'/'. $data['name'][$i];
        }
    }

    function rename (array $names, string $language = NULL): void {
        $files = File::tree(ENV::uploads('files'). $this->folder .'/'. ($language ?: (($this->class)::TRANSLATOR)::default()), NULL, true, true, true);

        foreach ($names as $key => $name) {
            $names[$key] = (basename($name) .'.'. File::extension($key));
        }

        $duplicates = array(); // for avoiding overwriting
        foreach ($files as $key => $file) {
            $duplicates[$key] = tempnam(sys_get_temp_dir(), '');
            copy($file, $duplicates[$key]);
        }

        foreach ($names as $key => $name) {
            if (isset($files[$key])) { // for preventing non-existent input filename
                // remove file if its name isn't used for renaming
                if (is_file($files[$key]) && !in_array($key, $names)) {
                    unlink($files[$key]);
                }
                rename($duplicates[$key], $dirname .'/'. $names[$key]);
            }
        }
    }

    function delete (array $names = NULL, string $language = NULL): int {
        $count = 0;

        foreach (($language ? array($language) : Folder::children(ENV::uploads('files'). $this->folder, true)) as $lg) {
            foreach (File::tree(ENV::uploads('files'). $this->folder .'/'. $lg, NULL, true, true, true) as $files) {
                foreach (($names ? array_intersect_key($files, $names) : $files) as $file) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
            }
        }

        Folder::removeEmpty(ENV::uploads('files'). dirname($this->folder));

        if (!is_dir(ENV::uploads('files'). $this->folder)) {
            $this->urls = NULL;
        }

        return $count;
    }
}
