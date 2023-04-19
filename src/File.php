<?php

namespace Arshwell\Monolith;

/**
 * Class for manipulating files.
 *
 * It has routine functions.

 * @package https://github.com/arshwell/monolith
*/
final class File {

    static function readableSize (int $bytes, int $precision = 2, string $separator = ''): string {
        $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $step = 1024;
        $i = 0;

        while (($bytes / $step) > 0.9) {
            $bytes = $bytes / $step;
            $i++;
        }
        return round($bytes, $precision).$separator.$units[$i];
    }

    static function bytesSize (string $shortHandBytes): ?int {
        if (!preg_match('/^(?<value>\d+)(?<option>[K|M|G]*)$/i', $shortHandBytes, $matches)) {
            return NULL;
        }

        $value = $matches['value'];

        if ($matches['option']) {
            $option = strtoupper($matches['option']);

            if ($option == 'K') {
                $value *= 1024;
            }
            else if ($option == 'M') {
                $value *= 1024 * 1024;
            }
            else if ($option == 'G') {
                $value *= 1024 * 1024 * 1024;
            }
        }

        return $value;
    }

    static function extension (string $filename): ?string {
        // end(explode('.', $filename)) doesn't cover all situations.

        $strrpos = strrpos($filename, '.');

        // in that way, first char being dot doesn't count
        return ($strrpos ? substr($filename, $strrpos + 1) : NULL);
    }

    /******************************************************************
        Good for when you don't know which is the extension.
        Otherwise, use basename($filename, '.extension'); instead.
    ******************************************************************/
    static function name (string $filename, bool $basename = true): string {
        if ($basename) {
            $filename = basename($filename);
        }

        return substr($filename, 0, strrpos($filename, '.') ?: strlen($filename));
    }

    static function tree (string $folder, array $extensions = NULL, bool $dirname = true, bool $extension = true, bool $assoc = false): array {
        $files = array();
        // $folder = Folder::realpath($folder);

        if (is_dir($folder)) {
            $folder = rtrim($folder, '/');

            foreach (array_values(array_diff(scandir($folder), array('..', '.'))) as $key => $file) {
                if (is_dir($folder.'/'.$file)) {
                    $files[$dirname ? ($folder.'/'.$file) : $file] = self::tree($folder.'/'.$file, $extensions, $dirname, $extension, $assoc);
                }
                else if (!$extensions || in_array(self::extension($file), $extensions)) {
                    if ($extension == false) {
                        // $files[($assoc ? self::name($file) : $key)] = self::name(Folder::shorter($folder).'/'.$file, !$dirname);
                        $files[($assoc ? self::name($file) : $key)] = self::name($folder.'/'.$file, !$dirname);
                    }
                    else if ($dirname == true) {
                        // $files[($assoc ? $file : $key)] = Folder::shorter($folder).'/'.$file;
                        $files[($assoc ? $file : $key)] = $folder.'/'.$file;
                    }
                    else {
                        $files[($assoc ? $file : $key)] = $file;
                    }
                }
            }
        }

        return $files;
    }

    static function folder (string $folder, array $extensions = NULL, bool $dirname = true, bool $extension = true, bool $assoc = false): array {
        $files  = array();
        // $folder = Folder::realpath($folder);

        if (is_dir($folder)) {
            $folder = rtrim($folder, '/');

            foreach (array_values(array_diff(scandir($folder), array('..', '.'))) as $key => $file) {
                if (is_file($folder.'/'.$file)
                && (!$extensions || (in_array(self::extension($file), $extensions)))) {
                    // $folder = Folder::shorter($folder);

                    if ($extension == false) {
                        $files[($assoc ? self::name($file) : $key)] = self::name($folder.'/'.$file, !$dirname);
                    }
                    else if ($dirname == true) {
                        $files[($assoc ? $file : $key)] = $folder.'/'.$file;
                    }
                    else {
                        $files[($assoc ? $file : $key)] = $file;
                    }
                }
            }
        }

        return $files;
    }

    static function rFolder (string $folder, array $extensions = NULL, bool $dirname = true, bool $extension = true): array {
        $files = array();
        // $folder = Folder::realpath($folder);

        if (is_dir($folder)) {
            $folder = rtrim($folder, '/');

            foreach (scandir($folder) as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($folder.'/'.$file)) {
                        $files = array_merge($files, self::rFolder($folder.'/'.$file, $extensions, $dirname, $extension));
                    }
                    else if (!$extensions || in_array(self::extension($file), $extensions)) {
                        if ($extension == false) {
                            // $files[] = self::name(Folder::shorter($folder).'/'.$file, !$dirname);
                            $files[] = self::name($folder.'/'.$file, !$dirname);
                        }
                        else if ($dirname == true) {
                            // $files[] = Folder::shorter($folder).'/'.$file;
                            $files[] = $folder.'/'.$file;
                        }
                        else {
                            $files[] = $file;
                        }
                    }
                }
            }
        }

        return $files;
    }

    static function first (string $folder, array $extensions = NULL, bool $dirname = true, bool $extension = true): ?string {
        $files = array();

        if (is_dir($folder)) {
            foreach (scandir($folder) as $file) {
                if ($file != '.' && $file != '..' && is_file($folder.'/'.$file)
                && (!$extensions || (in_array(self::extension($file), $extensions)))) {
                    if ($extension == false) {
                        return self::name($folder.'/'.$file, !$dirname);
                    }
                    else if ($dirname == true) {
                        return $folder.'/'.$file;
                    }
                    else {
                        return $file;
                    }
                }
            }
        }

        return NULL;
    }

    static function rFirst (string $folder, array $extensions = NULL, bool $dirname = true, bool $extension = true): ?string {
        $files = array();

        if (is_dir($folder)) {
            foreach (scandir($folder) as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($folder.'/'.$file)) {
                        if (($return = self::rFirst($folder.'/'.$file, $extensions, $dirname, $extension))) {
                            return $return;
                        }
                    }
                    else if (!$extensions || (in_array(self::extension($file), $extensions))) {
                        if ($extension == false) {
                            return self::name($folder.'/'.$file, !$dirname);
                        }
                        else if ($dirname == true) {
                            return $folder.'/'.$file;
                        }
                        else {
                            return $file;
                        }
                    }
                }
            }
        }

        return NULL;
    }

    static function findBiggestSibling (string $file, array $siblings = NULL): ?string {
        $filepath = self::parsePath($file);

        if (!$filepath) {
            return NULL; // invalid file path
        }

        $filekeyfolder = StaticHandler::getEnvConfig()->getFileStoragePathByIndex(0, 'uploads') . 'files/' . $filepath['class'] .'/'. $filepath['id_table'] .'/'. $filepath['filekey'];

        if (empty($siblings)) {
            $siblings = File::tree($filekeyfolder, NULL, false, true);
        }

        if (empty($siblings)) {
            return NULL; // no file siblings at all
        }

        /**
         * Proper order:
         *
         * 1. Same LG - biggest Size
         * 2. Another LG - same Size
         * 3. Another LG - biggest Size
         * 4. Same LG - smaller Size
         * 5. Another LG - smaller Size
         */

        $biggest = NULL;

        // biggest language size from existent ones
        if (!empty($siblings[$filepath['language']])) {
            $lg_files = File::rFolder($filekeyfolder .'/'. $filepath['language'], NULL, true, true);

            if ($lg_files) {
                $biggest = File::parsePath($lg_files[Func::keyFromBiggest(array_map(function ($file) {
                    $data = getimagesize($file);

                    return ($data[0]*$data[1]);
                }, $lg_files))], 'size');
            }
        }

        if ($biggest != $filepath['size'] && isset($siblings[$filepath['language']][$biggest][0])) {
            // 1. Same LG - biggest Size
            return ($filekeyfolder .'/'. $filepath['language'] .'/'. $biggest .'/'. $siblings[$filepath['language']][$biggest][0]);
        }
        else {
            $another_file = NULL;

            foreach (array_keys($siblings) as $lg) {
                foreach (array_keys($siblings[$lg]) as $sz) {
                    if ($lg != $filepath['language'] && $filepath['size'] == $sz) {
                        // 2. Another LG - same Size
                        return ($filekeyfolder .'/'. $lg .'/'. $sz .'/'. $siblings[$lg][$sz][0]);
                    }
                    // We get a file in any case, because:
                    //    - could be only one language
                    //    - your size name could be no more existent
                    else if ($lg != $filepath['language'] || $filepath['size'] != $sz) {
                        $another_file = ($filekeyfolder .'/'. $lg .'/'. $sz .'/'. $siblings[$lg][$sz][0]);
                    }
                }
            }

            // We get a file in any case, because:
            //    - could be only one language
            //    - your size name could be no more existent
            return $another_file;
        }

        return NULL;
    }

    /**
     * NOTE: this function has no size limit (beside built-in copy fn).

     * @param int $buffer_size = 1048576 = 1MB
     */
    static function copy (string $source, string $destination, int $buffer_size = 1048576): int {
        $ret = 0;
        $fin = fopen($source, 'rb');
        $fout = fopen($destination, 'w');
        while (!feof($fin)) {
            $ret += fwrite($fout, fread($fin, $buffer_size));
        }
        fclose($fin);
        fclose($fout);

        return $ret; // return number of bytes written
    }

    static function minProperlyRatio (array $sizes): string {
        $widths = array_column(array_column($sizes, 'width'), 0);
        $heights = array_column(array_column($sizes, 'height'), 0);

        $keyFromBiggestWidth = Func::keyFromBiggest($widths);
        $keyFromBiggestHeight = Func::keyFromBiggest($heights);

        if ($keyFromBiggestWidth != $keyFromBiggestHeight) {
            $maxWidth = max($widths);
            $maxHeight = max($heights);

            if ($maxWidth > $maxHeight) {
                if ($heights[$keyFromBiggestWidth] != NULL) {
                    return Math::resizeKeepingRatio($heights[$keyFromBiggestWidth], $maxWidth, $maxHeight).'x'.$maxHeight;
                }
            }
            else {
                if ($widths[$keyFromBiggestHeight] != NULL) {
                    return $maxWidth.'x'.Math::resizeKeepingRatio($widths[$keyFromBiggestHeight], $maxHeight, $maxWidth);
                }
            }
        }
        return (
            ($widths[$keyFromBiggestHeight] ?? '(auto)')
            .'x'.
            ($heights[$keyFromBiggestWidth] ?? '(auto)')
        );
    }

    static function reformat (array $data, int $layers = 1): array {
        if (!$layers) {
            return $data;
        }

        $sub_keys = array_keys($data['name'] ?? $data['type'] ?? $data['tmp_name'] ?? array());

        if (!$sub_keys) {
            return $data;
        }

        $data_keys = array(); // keys which are part from file array
        foreach ($data as $key => $value) {
            if (is_array($value) && array_keys($value) === $sub_keys) {
                $data_keys[] = $key;
            }
        }

        $files = array();
        foreach ($sub_keys as $key) {
            $files[$key] = self::reformat(array_combine(
                $data_keys,
                array_column(array_values($data), $key)
            ), $layers - 1);
        }

        return $files;
    }

    /**
     * @return null|array|string
     */
    static function parsePath (string $path, string $key = NULL) {
        /*
          |>>>>>    file_path    <<<<<|       |>> file <<|
        # [table]/[id_table]/[filekey]/[lang]/[title].[ext]         (for docs)

          |>>>>>    file_path    ><<<<|              |>> file <<|
        # [table]/[id_table]/[filekey]/[lang]/[size]/[title].[ext]  (for images)
        */

        preg_match(
            // TODO: Don't remove it. Is another class match solution
            // NOTE: It is a very old. So needs updates before using it.
            // "~^".'uploads/files/'."(?<class>(?!\d+/)[a-z0-9_.]+(?:/(?!\d+/)[a-z0-9_.]+)*)/(?<id_table>\d+)/(?<filekey>[a-z0-9-_]+)(/\w+)?\.\w+$~",

            "~^".'uploads/files/'."(?<class>\d*+[a-z0-9_.]+(?>/\d*+[a-z0-9_.]+)*)/(?<id_table>\d+)/(?<filekey>[a-z0-9-_]+)/(?<lang>[^/]+)/((?<size>[^/]+)/)?(?<title>[^/]+)\.(?<extension>[^/.]+)$~",
            $path, $matches
        );

        if ($matches) {
            if ($key) {
                return $matches[$key];
            }

            return array(
                'class'     => $matches['class'],
                'id_table'  => $matches['id_table'],
                'filekey'   => $matches['filekey'],
                'language'  => $matches['lang'],
                'size'      => $matches['size'] ?? NULL,
                'extension' => $matches['extension']
            );
        }

        return NULL;
    }

    final static function mimeType (string $filename): ?string {
        if (($type = mime_content_type($filename))) {
            return $type;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $filename);
        finfo_close($finfo);

        return ($type ?: NULL);
    }
}
