<?php

namespace Arshwell\Monolith\Table\Files;

use Arshwell\Monolith\Table\TableSegment;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\File;
use Arshwell\Monolith\Text;
use Arshwell\Monolith\Func;
use Arshwell\Monolith\Web;
use Arshwell\Monolith\StaticHandler;

use Verot\Upload\Upload;

use Exception;

final class Image implements TableSegment
{
    private $class;
    private $id_table = NULL;
    private $filekey;
    private $folder;
    private $config = array();
    private $paths = array(); // filepaths
    private $urls = array(); // urls
    private $smallest = array(); // urls
    private $biggest = array(); // urls
    private $sizes = array();
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

        $this->config = array_replace_recursive(
            array(
                'default'   => false, // doesn't copy the files where some lg don't have
                'quality'   => 100,
                'bytes'     => NULL, // we don't use it here anyway, but instead in TableValidation
                'sizes'     => array(),
                'watermark' => array(
                    NULL, '50%', '50%', false
                )
            ),
            ($this->class)::FILES[$filekey]
        );

        if (empty($this->config['sizes'])) { // if no sizes, like TableView
            foreach (Folder::children($this->uploadsPath .'files/'. $this->folder .'/'. ($class::TRANSLATOR)::get(), true) as $size) {
                list($width, $height) = explode('x', $size);

                $this->config['sizes'][$size]['width'] = array($width, $width);
                $this->config['sizes'][$size]['height'] = array($height, $height);
            }
        }

        // setup urls
        if (!empty($this->config['sizes'])) { // if had sizes or created above
            foreach ($this->config['sizes'] as $size => $ranges) {
                $ranges['width'] = array_values((array)($ranges['width'] ?? array(NULL)));

                    if (array_key_exists(1, $ranges['width']) == false) {
                        $ranges['width'][1] = $ranges['width'][0];
                    }

                $ranges['height'] = array_values((array)($ranges['height'] ?? array(NULL)));

                    if (array_key_exists(1, $ranges['height']) == false) {
                        $ranges['height'][1] = $ranges['height'][0];
                    }

                $this->config['sizes'][$size] = $ranges;
            }

            $this->setup($this->config['default']);
        }
    }

    function __destruct () {
        // if (!$this->id_table) {
        //     throw new \Exception("Image object should get an id", 1);
        // }
    }

    /**
     * (string|bool) $default
     */
    private function setup ($default = false): void {
        $site = Web::site();

        // reset urls
        $this->smallest = array();
        $this->biggest  = array();
        $this->urls     = array();

        $files = File::tree($this->uploadsPath .'files/'. $this->folder, NULL, false, true);

        if ($files) {
            foreach (Folder::children($this->uploadsPath .'files/'. $this->folder, true) as $lg) {
                $lg_files = File::rFolder($this->uploadsPath .'files/'. $this->folder .'/'. $lg, NULL, true, true);

                if ($lg_files) {
                    $lg_sized_files = array_map(function ($file) {
                        $data = getimagesize(Folder::realpath($file));

                        return ($data[0]*$data[1]);
                    }, $lg_files);

                    $this->smallest[$lg] = $site . ltrim(preg_replace('~^'. $this->uploadsPath .'~', 'uploads/', $lg_files[Func::keyFromSmallest($lg_sized_files)]), '/');
                    $this->biggest[$lg] = $site . ltrim(preg_replace('~^'. $this->uploadsPath .'~', 'uploads/', $lg_files[Func::keyFromBiggest($lg_sized_files)]), '/');
                }
            }

            if (!defined($this->class .'::TRANSLATOR')) {
                throw new Exception("|Arshwell| {$this->class} has FILES; so should contain const TRANSLATOR");
            }

            foreach ((($this->class)::TRANSLATOR)::LANGUAGES as $language) {
                if ($default == true && !isset($files[$language])) {
                    $first_lang = array_key_first($files);

                    $files[$language] = $files[$first_lang];
                    Folder::copy($this->uploadsPath .'files/'. $this->folder .'/'. $first_lang, $this->uploadsPath .'files/'. $this->folder .'/'. $language);
                }

                foreach ($this->config['sizes'] as $size => $ranges) {
                    if ($default == true && !isset($files[$language][$size][0])) {
                        $biggest = NULL;

                        // biggest language size from existent ones
                        if (!empty($files[$language])) {
                            $lg_files = File::rFolder($this->uploadsPath .'files/'. $this->folder .'/'. $language, NULL, true, true);

                            if ($lg_files) {
                                $biggest = File::parsePath($lg_files[Func::keyFromBiggest(array_map(function ($file) {
                                    $data = getimagesize($file);

                                    return ($data[0]*$data[1]);
                                }, $lg_files))], 'size');
                            }
                        }

                        if ($biggest != $size && isset($files[$language][$biggest][0])) {
                            $files[$language][$size][0] = $files[$language][$biggest][0];
                            $filepath = $language .'/'. $biggest .'/'. $files[$language][$biggest][0];
                        }
                        else {
                            foreach (array_keys($files) as $lg) {
                                foreach (array_keys($files[$lg]) as $sz) {
                                    if ($lg != $language && $size == $sz) {
                                        // we found the proper file: another language and same size
                                        break 2;
                                    }
                                }
                            }

                            // We get a file in any case, because:
                            //    - could be only one language
                            //    - your size name could be no more existent
                            $files[$language][$size][0] = $files[$lg][$sz][0];
                            $filepath = $lg .'/'. $sz .'/'. $files[$lg][$sz][0];
                        }

                        $imagesize = getimagesize($this->uploadsPath .'files/'. $this->folder .'/'. $filepath);

                        if (($ranges['width'][1] != NULL && $imagesize[0] > $ranges['width'][1]) || ($ranges['height'][1] != NULL && $imagesize[1] > $ranges['height'][1])) {
                            $resizer = new Upload($this->uploadsPath .'files/'. $this->folder .'/'. $filepath);

                            $resizer->file_new_name_body    = strtolower(File::name($filepath));
                            $resizer->file_overwrite        = true;

                            $resizer->png_compression   = 9; // (slow but smaller files)
                            $resizer->webp_quality      = 100;
                            $resizer->jpeg_quality      = 100;

                            $resizer->file_safe_name    = false;
                            $resizer->image_resize      = true;

                            if ($ranges['height'][1] == NULL) {
                                $resizer->image_x = min($imagesize[0], $ranges['width'][1]); // width
                                $resizer->image_ratio_y = true;
                                $resizer->image_ratio_crop = false;
                            }
                            else if ($ranges['width'][1] == NULL) {
                                $resizer->image_y = min($imagesize[1], $ranges['height'][1]); // height
                                $resizer->image_ratio_x = true;
                                $resizer->image_ratio_crop = false;
                            }
                            else {
                                $resizer->image_x = min($imagesize[0], $ranges['width'][1]); // width
                                $resizer->image_y = min($imagesize[1], $ranges['height'][1]); // height
                                $resizer->image_ratio_crop = true;
                            }

                            $resizer->process($this->uploadsPath .'files/'. $this->folder .'/'. $language .'/'. $size);
                        }
                        else if (is_dir($this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size) || mkdir($this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size, 0755, true)) {
                            copy($this->uploadsPath .'files/'.$this->folder.'/'.$filepath, $this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size.'/'.basename($filepath));
                        }
                    }

                    // if file found
                    if (isset($files[$language][$size][0])) {
                        $this->paths[$language][$size] = ($this->uploadsPath . 'files/' . $this->folder .'/'. $language .'/'. $size .'/'. $files[$language][$size][0]);

                        $this->sizes[$language][$size] = getimagesize($this->uploadsPath . 'files/' . $this->folder .'/'. $language .'/'. $size .'/'. $files[$language][$size][0]);

                        $this->urls[$language][$size] = ($site .'uploads/files/'. $this->folder .'/'. $language .'/'. $size .'/'. $files[$language][$size][0]);
                    }
                }
            }
        }
    }

    function class (): string {
        return $this->class;
    }

    function id (int $id_table = NULL): ?int {
        if ($id_table) {
            $this->id_table = $id_table;
        }

        return $this->id_table;
    }

    function key (): string {
        return $this->filekey;
    }

    function isTranslated (): bool {
        return true;
    }

    function value (string $size = NULL, string $lang = NULL): ?string {
        if ($lang == NULL) {
            $lang = (($this->class)::TRANSLATOR)::get();
        }
        if ($size == NULL) {
            $size = array_key_first($this->paths[$lang]);
        }

        return file_get_contents($this->paths[$lang][$size]) ?? NULL;
    }

    function urls (string $lang = NULL): array {
        return $this->urls[($lang ?: (($this->class)::TRANSLATOR)::get())] ?? array();
    }
    function url (string $size, string $lang = NULL): ?string {
        return $this->urls[($lang ?: (($this->class)::TRANSLATOR)::get())][$size] ?? NULL;
    }
    function smallest (string $lang = NULL): ?string {
        return $this->smallest[($lang ?: (($this->class)::TRANSLATOR)::get())] ?? NULL;
    }
    function biggest (string $lang = NULL): ?string {
        return $this->biggest[($lang ?: (($this->class)::TRANSLATOR)::get())] ?? NULL;
    }

    function getimagesize (string $lang = NULL, string $sizename = NULL, string $key = NULL) {
        $info = $this->sizes[($lang ?: (($this->class)::TRANSLATOR)::get())] ?? NULL;

        if (empty($info)) {
            return NULL;
        }

        if ($sizename && $key != NULL) {
            return $info[$sizename][$key];
        }
        else if ($sizename) {
            return $info[$sizename];
        }
        else if ($key != NULL) {
            return $info[array_key_first($info)][$key];
        }

        return $info ?? NULL;
    }

    function configSizes (): ?array {
        return $this->config['sizes'] ?? NULL;
    }

    function __call (string $method, array $args) {
        return $this->{$method}; // class, id_table, filekey, folder
    }

    function rename (string $name, string $language = NULL): string {
        $language = ($language ?: (($this->class)::TRANSLATOR)::default());

        $file_ext = ('.'. File::extension(File::rFirst($this->uploadsPath .'files/'. $this->folder .'/'. $language)));
        $name = Text::slug($name);

        foreach (File::rFolder($this->uploadsPath .'files/'. $this->folder .'/'. $language) as $file) {
            rename($file, dirname($file) .'/'. $name . $file_ext);
        }

        $this->setup();

        return $name;
    }

    function update (array $data, string $language = NULL): void {
        $site       = Web::site();
        $language   = ($language ?: (($this->class)::TRANSLATOR)::default());

        $imagesize = getimagesize($data['tmp_name']);

        ini_set('max_execution_time', ini_get('max_execution_time') + ($data['size'] / 1048600)); // 1s for every 1MB
        ini_set(
            'memory_limit',
            File::bytesSize(ini_get('memory_limit')) + ($imagesize[0] * $imagesize[1] * 8)
        );

        foreach ($this->config['sizes'] as $size => $ranges) {
            ini_set('memory_limit', ini_get('memory_limit') + 1048600); // + 1MB

            Folder::remove($this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size);

            // width OR height is not NULL - so a resize is needed
            if (($ranges['width'][1] != NULL && $imagesize[0] > $ranges['width'][1]) || ($ranges['height'][1] != NULL && $imagesize[1] > $ranges['height'][1])) {
                $resizer = new Upload($data['tmp_name']);

                $resizer->file_new_name_body    = strtolower(File::name($data['name']));
                $resizer->file_overwrite        = true;

                $resizer->png_compression   = 9; // (slow but smaller files)
                $resizer->webp_quality      = ($ranges['quality'] ?? $this->config['quality']);
                $resizer->jpeg_quality      = ($ranges['quality'] ?? $this->config['quality']);

                // size custom watermark
                if (!empty($ranges['watermark'])) {
                    $resizer->image_watermark    = ($ranges['watermark'][0] ?? $this->config['watermark'][0]);
                    $resizer->image_watermark_x  = ($ranges['watermark'][1] ?? $this->config['watermark'][1] ?? '50%');
                    $resizer->image_watermark_y  = ($ranges['watermark'][2] ?? $this->config['watermark'][2] ?? '50%');
                    $resizer->image_watermark_no_zoom_in = ($ranges['watermark'][3] ?? $this->config['watermark'][3] ?? false);
                }
                // file default watermark
                else if (!empty($this->config['watermark'][0])) {
                    $resizer->image_watermark    = $this->config['watermark'][0];
                    $resizer->image_watermark_x  = $this->config['watermark'][1];
                    $resizer->image_watermark_y  = $this->config['watermark'][2];
                    $resizer->image_watermark_no_zoom_in = $this->config['watermark'][3];
                }

                $resizer->file_max_size = 209715200; // 200MB
                $resizer->file_safe_name = true;
                $resizer->image_resize = true;

                if ($ranges['height'][1] == NULL) {
                    $resizer->image_x = min($imagesize[0], $ranges['width'][1]); // width
                    $resizer->image_ratio_y = true;
                    $resizer->image_ratio_crop = false;
                }
                else if ($ranges['width'][1] == NULL) {
                    $resizer->image_y = min($imagesize[1], $ranges['height'][1]); // height
                    $resizer->image_ratio_x = true;
                    $resizer->image_ratio_crop = false;
                }
                else {
                    $resizer->image_x = min($imagesize[0], $ranges['width'][1]); // width
                    $resizer->image_y = min($imagesize[1], $ranges['height'][1]); // height
                    $resizer->image_ratio_crop = true;
                }

                $resizer->process($this->uploadsPath .'files/'. $this->folder .'/'. $language .'/'. $size);

                if ($resizer->processed == false) {
                    throw new \ErrorException($resizer->error);
                }
            }
            // no resize needed
            else if (is_dir($this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size) || mkdir($this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size, 0755, true)) {
                copy($data['tmp_name'], $this->uploadsPath .'files/'.$this->folder.'/'.$language.'/'.$size.'/'.$data['name']);
            }
        }

        $this->setup();
    }

    function delete (string $language = NULL, bool $removeEmpty = true): bool {
        if (Folder::remove($this->uploadsPath .'files/'. $this->folder .'/'. ($language ?? ''), $removeEmpty)) {
            $this->setup();
        }

        if ($removeEmpty) {
            Folder::removeEmpty($this->uploadsPath .'files/'. dirname($this->folder));
        }

        return true;
    }
}
