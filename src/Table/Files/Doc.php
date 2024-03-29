<?php

namespace Arshwell\Monolith\Table\Files;

use Arshwell\Monolith\Table\TableSegment;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\Web;
use Arshwell\Monolith\StaticHandler;

final class Doc implements TableSegment {
    private $class;
    private $id_table = NULL;
    private $filekey;
    private $folder;
    private $paths = array(); // filepaths
    private $urls = NULL; // if no files in uploads/
    private $uploadsPath;

    function __construct (string $class, int $id_table = NULL, string $filekey, string $fileStorageKey = null) {
        $this->class    = $class;
        $this->id_table = $id_table;
        $this->filekey  = $filekey;

        if ($fileStorageKey) {
            // fyi: because the path could be outside of project

            $filestorage = StaticHandler::getEnvConfig("filestorages")[$fileStorageKey];

            if (!empty($filestorage['aliases']) && in_array($class, $filestorage['aliases'])) {
                // file class becomes the alias class
                $class = array_search($class, $filestorage['aliases']);
            }

            $this->uploadsPath = StaticHandler::getEnvConfig()->getFileStoragePath($fileStorageKey, 'uploads');
        }
        else {
            // fyi: the path is in this project
            $this->uploadsPath = StaticHandler::getEnvConfig()->getFileStoragePathByIndex(0, 'uploads');
        }

        $this->folder = (Folder::encode($class) .'/'. $id_table .'/'. $filekey);

        $files = File::tree($this->uploadsPath . 'files/'. $this->folder, NULL, false, true);

        if ($files) {
            $site = Web::site();

            foreach ((($this->class)::TRANSLATOR)::LANGUAGES as $language) {
                if (!isset($files[$language])) {
                    $first_lang = array_key_first($files);

                    if (Folder::copy($this->uploadsPath . 'files/'. $this->folder .'/'. $first_lang, $this->uploadsPath . 'files/'. $this->folder .'/'. $language)) {
                        $files[$language] = $files[$first_lang];
                    }
                }

                if (!empty($files[$language])) {
                    $this->paths[$language] = ($this->uploadsPath . 'files/' . $this->folder .'/'. $language .'/'. array_values($files[$language])[0]);

                    $this->urls[$language] = ($site .'uploads/files/'. $this->folder .'/'. $language .'/'. array_values($files[$language])[0]);
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

    function value (string $lang = NULL): ?string {
        if ($lang == NULL) {
            $lang = (($this->class)::TRANSLATOR)::get();
        }

        return file_get_contents($this->paths[$lang]) ?? NULL;
    }

    function url (string $lang = NULL): ?string {
        return $this->urls[($lang ?: (($this->class)::TRANSLATOR)::get())];
    }

    function __call (string $method, array $args) {
        return $this->{$method}; // class, id_table, filekey, folder
    }

    function rename (string $name, string $language = NULL): void {
        $language = ($language ?: (($this->class)::TRANSLATOR)::default());

        $file_ext = ('.'. File::extension(File::rFirst($this->uploadsPath . 'files/'. $this->folder .'/'. $language)));

        foreach (File::rFolder($this->uploadsPath . 'files/'. $this->folder .'/'. $language) as $file) {
            rename($file, dirname($file) .'/'. $name . $file_ext);
        }
    }

    function update (array $data, string $language = NULL): void {
        $language = ($language ?: (($this->class)::TRANSLATOR)::default());

        $dirname = $this->uploadsPath . 'files/'.$this->folder.'/'.$language;

        Folder::remove($dirname);
        mkdir($dirname, 0755, true);

        if (isset($data['content'])) {
            file_put_contents(
                $this->uploadsPath . 'files/'.$this->folder.'/'.$language.'/'.$data['name'],
                $data['content'],
                LOCK_EX
            );
        }
        else {
            copy($data['tmp_name'], $this->uploadsPath . 'files/'.$this->folder.'/'.$language.'/'.$data['name']);
        }

        $this->urls[$language] = Web::site().'uploads/files/'.$this->folder.'/'.$language.'/'.$data['name'];
    }

    function delete (string $language = NULL): bool {
        Folder::remove($this->uploadsPath . 'files/'. $this->folder .'/'. ($language ?? ''));

        Folder::removeEmpty($this->uploadsPath . 'files/'. dirname($this->folder));

        if (!$language) {
            $this->urls = NULL;
        }

        return true;
    }
}
