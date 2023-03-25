<?php

namespace Arshwell\Monolith;

use Arshwell\Monolith\Session;
use Arshwell\Monolith\Folder;
use Arshwell\Monolith\Piece;
use Arshwell\Monolith\Table;
use Arshwell\Monolith\File;
use Arshwell\Monolith\Func;
use Arshwell\Monolith\StaticHandler;
use Arshwell\Monolith\Web;

use MatthiasMullie\Minify\JS as JsMin;
use ScssPhp\ScssPhp\Compiler as ScssPhp;

/**
 * Class for compiling scss/js, getting utils and links.

 * @package https://github.com/arshwell/monolith
*/
final class Layout {
    private static $css_suffixes = array(''); // ex: for certain users

    /* media */
        static function mediaSCSS (string $folder, array $pieces, bool $css = false): array {
        	$extension = ($css ? 'css' : 'scss');

        	$return = array(
        		'utils' => array(),
        		'json'	=> array(
        			'layout'	=> array(),
        			'outcome'	=> array(),
        			'pieces'	=> array()
        		),
        		'files'	=> array()
        	);

        	$array = self::utils('outcomes/'. $folder);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['outcome'] = $array['json'];

        	$array = self::utils('layouts/'. $return['json']['outcome']['layout']);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['layout'] = $array['json'];

            // find all scss files required by layout from utils
            foreach ($return['json']['layout']['scss']['files'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/scss/$rsrc_pattern.scss") as $rsrc_file) {
                        $return['files'][] = array(
                            'name' => (File::name($rsrc_file, false) .'.'. $extension)
                        );
                    }
                }
            }

            $files = array();
            foreach (File::folder('layouts/'. $return['json']['outcome']['layout'] .'/.scss', array('scss')) as $resolution) {
        		if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.scss'), $basename)
                && (!isset($basename[3]) || $basename[1] != $basename[4])) {
                    $files[] = array(
        				'name' => (File::name($resolution, false) .'.'. $extension),
        				'range' => array(
        	                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        	                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        				)
        			);
                }
            }

            // sorts the .scss folder categories in ascending order
            usort($files, function (array $a, array $b) {
                if (isset($a['range']['max']) && isset($b['range']['max'])) {
                    return $b['range']['max'] <=> $a['range']['max'];
                }
                return $a['range']['min'] <=> $b['range']['min'];
            });

            $return['files'] = array_merge(
                $return['files'],
                $files
            );

            // find all scss files required by outcome from utils
            foreach ($return['json']['outcome']['scss']['files'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/scss/$rsrc_pattern.scss") as $rsrc_file) {
                        $return['files'][] = array(
                            'name' => (File::name($rsrc_file, false) .'.'. $extension)
                        );
                    }
                }
            }

            $files = array();
            foreach (File::folder('outcomes/'. $folder .'/.scss', array('scss')) as $resolution) {
                if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.scss'), $basename)
                && (!isset($basename[3]) || $basename[1] != $basename[4])) {
        			$files[] = array(
        				'name' => (File::name($resolution, false) .'.'. $extension),
        				'range' => array(
        	                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        	                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        				)
        			);
                }
            }

            // sorts the .scss folder categories in ascending order
            usort($files, function (array $a, array $b) {
                if (isset($a['range']['max']) && isset($b['range']['max'])) {
                    return $b['range']['max'] <=> $a['range']['max'];
                }
                return $a['range']['min'] <=> $b['range']['min'];
            });

            $return['files'] = array_merge(
                $return['files'],
                $files
            );

            foreach ($pieces as $piece) {
        		$array = self::utils('pieces/' . $piece);
        		$return['utils'] = array_merge($return['utils'], $array['utils']);
        		$return['json']['pieces'] = array_merge_recursive($return['json']['pieces'], $array['json']);

                // find all scss files required by piece from utils
                foreach ($array['json']['scss']['files'] as $rsrc_folder => $rsrc_patterns) {
                    foreach ($rsrc_patterns as $rsrc_pattern) {
                        foreach (glob("$rsrc_folder/scss/$rsrc_pattern.scss") as $rsrc_file) {
                            $return['files'][] = array(
                                'name'	=> (File::name($rsrc_file, false) .'.'. $extension)
                            );
                        }
                    }
                }

                $folders = explode('/', $piece);
                $path = '';

                foreach ($folders as $folder) {
                    $path .= ('/'. $folder);

                    $files = array();
                    foreach (File::folder('pieces'. $path .'/.scss', array('scss')) as $resolution) {
                        if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.scss'), $basename)
                        && (!isset($basename[3]) || $basename[1] != $basename[4])) {
        					$files[] = array(
        						'name' => (File::name($resolution, false) .'.'. $extension),
        						'range' => array(
        			                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        			                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        						)
        					);
                        }
                    }

                    // sorts the .scss folder categories in ascending order
                    usort($files, function (array $a, array $b) {
                        if (isset($a['range']['max']) && isset($b['range']['max'])) {
                            return $b['range']['max'] <=> $a['range']['max'];
                        }
                        return $a['range']['min'] <=> $b['range']['min'];
                    });

                    $return['files'] = array_merge(
                        $return['files'],
                        $files
                    );
                }
            }

        	return array(
        		'utils' => $return['utils'],
        		'json'	=> array_merge_recursive(
        			$return['json']['layout'],
        			$return['json']['outcome'],
        			$return['json']['pieces']
        		),
        		'files' => Func::rUnique($return['files'])
        	);
        }

        static function mediaJSHeader (string $folder, array $pieces): array {
        	$return = array(
        		'utils' => array(),
        		'json'	=> array(
        			'layout'	=> array(),
        			'outcome'	=> array(),
        			'pieces'	=> array()
        		),
        		'files'	=> array(
                    array(
                        'name' => 'vendor/arshwell/monolith/DevTools/tools/files/design/js/Arshwell/http_build_query/v1.js',
                        'range' => array(
                            'min' => 0 // guarantees will compile even if there are no other js files
                        )
                    ),
                    array(
                        'name' => 'vendor/arshwell/monolith/DevTools/tools/files/design/js/Arshwell/Form/v2.js',
                        'range' => array(
                            'min' => 0 // guarantees will compile even if there are no other js files
                        )
                    ),
                    array(
                        'name' => 'vendor/arshwell/monolith/DevTools/tools/files/design/js/Arshwell/VanillaJS/v1.js',
                        'range' => array(
                            'min' => 0 // guarantees will compile even if there are no other js files
                        )
                    )
                )
        	);

        	$array = self::utils('outcomes/'. $folder);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['outcome'] = $array['json'];

        	$array = self::utils('layouts/'. $return['json']['outcome']['layout']);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['layout'] = $array['json'];

            // find all header js files required by layout from utils
            foreach ($return['json']['layout']['js']['files']['header'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/js/$rsrc_pattern.js") as $rsrc_file) {
                        $return['files'][] = array(
                            'name' => $rsrc_file
                        );
                    }
                }
            }

            $files = array();
        	foreach (File::folder('layouts/'. $return['json']['outcome']['layout'] .'/.js', array('js')) as $resolution) {
        		if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.js'), $basename)
                && (!isset($basename[3]) || $basename[1] != $basename[4])) {
                    $files[] = array(
        				'name' => $resolution,
        				'range' => array(
        	                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        	                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        				)
        			);
                }
            }

            // sorts the .js folder categories in ascending order
            usort($files, function (array $a, array $b) {
                if (isset($a['range']['max']) && isset($b['range']['max'])) {
                    return $b['range']['max'] <=> $a['range']['max'];
                }
                return $a['range']['min'] <=> $b['range']['min'];
            });

            $return['files'] = array_merge(
                $return['files'],
                $files
            );

            // find all header js files required by outcome from utils
            foreach ($return['json']['outcome']['js']['files']['header'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/js/$rsrc_pattern.js") as $rsrc_file) {
                        $return['files'][] = array(
                            'name' => $rsrc_file
                        );
                    }
                }
            }

        	foreach ($pieces as $piece) {
        		$array = self::utils('pieces/' . $piece);
        		$return['utils'] = array_merge($return['utils'], $array['utils']);
        		$return['json']['pieces'] = array_merge_recursive($return['json']['pieces'], $array['json']);

        		// find all header js files required by piece from utils
                foreach ($array['json']['js']['files']['header'] as $rsrc_folder => $rsrc_patterns) {
                    foreach ($rsrc_patterns as $rsrc_pattern) {
                        foreach (glob("$rsrc_folder/js/$rsrc_pattern.js") as $rsrc_file) {
                            $return['files'][] = array(
                                'name' => $rsrc_file
                            );
                        }
                    }
                }
        	}

        	return array(
        		'utils' => $return['utils'],
        		'json'	=> array_merge_recursive(
        			$return['json']['layout'],
        			$return['json']['outcome'],
        			$return['json']['pieces']
        		),
        		'files' => Func::rUnique($return['files'])
        	);
        }

        static function mediaJSFooter (string $folder, array $pieces, bool $nonexistent_in_header = false): array {
        	$return = array(
        		'utils' => array(),
        		'json'	=> array(
        			'layout'	=> array(),
        			'outcome'	=> array(),
        			'pieces'	=> array()
        		),
        		'files'	=> array(
                    array(
                        'name' => 'vendor/arshwell/monolith/DevTools/tools/files/design/js/Arshwell/body/v1.js',
                        'range' => array(
        	                'min' => 0 // guarantees will compile even if there are no other js files
        				)
                    )
                )
        	);

        	$array = self::utils('outcomes/'. $folder);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['outcome'] = $array['json'];

        	$array = self::utils('layouts/'. $return['json']['outcome']['layout']);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['layout'] = $array['json'];

        	if ($nonexistent_in_header) {
        		$js_header_files = array_column(
        			self::mediaJSHeader($folder, $pieces)['files'],
        			'name'
        		);
        	}

        	// find all footer js files required by layout from utils
            foreach ($return['json']['layout']['js']['files']['footer'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/js/$rsrc_pattern.js") as $rsrc_file) {
                        if (!$nonexistent_in_header || !in_array($rsrc_file, $js_header_files)) {
                            $return['files'][] = array(
                                'name' => $rsrc_file
                            );
                        }
                    }
                }
            }

        	// find all footer js files required by outcome from utils
            foreach ($return['json']['outcome']['js']['files']['footer'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/js/$rsrc_pattern.js") as $rsrc_file) {
                        if (!$nonexistent_in_header || !in_array($rsrc_file, $js_header_files)) {
                            $return['files'][] = array(
                                'name' => $rsrc_file
                            );
                        }
                    }
                }
            }

            $files = array();
        	foreach (File::folder('outcomes/'. $folder .'/.js', array('js')) as $resolution) {
                if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.js'), $basename)
                && (!isset($basename[3]) || $basename[1] != $basename[4])) {
        			$files[] = array(
        				'name' => $resolution,
        				'range' => array(
        	                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        	                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        				)
        			);
                }
            }

            // sorts the .js folder categories in ascending order
            usort($files, function (array $a, array $b) {
                if (isset($a['range']['max']) && isset($b['range']['max'])) {
                    return $b['range']['max'] <=> $a['range']['max'];
                }
                return $a['range']['min'] <=> $b['range']['min'];
            });

            $return['files'] = array_merge(
                $return['files'],
                $files
            );

        	foreach ($pieces as $piece) {
        		$array = self::utils('pieces/' . $piece);
        		$return['utils'] = array_merge($return['utils'], $array['utils']);
        		$return['json']['pieces'] = array_merge_recursive($return['json']['pieces'], $array['json']);

        		// find all footer js files required by piece from utils
                foreach ($array['json']['js']['files']['footer'] as $rsrc_folder => $rsrc_patterns) {
                    foreach ($rsrc_patterns as $rsrc_pattern) {
                        foreach (glob("$rsrc_folder/js/$rsrc_pattern.js") as $rsrc_file) {
                            if (!$nonexistent_in_header || !in_array($rsrc_file, $js_header_files)) {
                                $return['files'][] = array(
                                    'name' => $rsrc_file
                                );
                            }
                        }
                    }
                }

        		$folders = explode('/', $piece);
        		$path = '';

        		foreach ($folders as $folder) {
                    $path .= ('/'. $folder);

                    $files = array();
                    foreach (File::folder('pieces'. $path .'/.js', array('js')) as $resolution) {
                        if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.js'), $basename)
                        && (!isset($basename[3]) || $basename[1] != $basename[4])) {
        					$files[] = array(
        						'name' => $resolution,
        						'range' => array(
        			                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        			                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        						)
        					);
                        }
                    }

                    // sorts the .js folder categories in ascending order
                    usort($files, function (array $a, array $b) {
                        if (isset($a['range']['max']) && isset($b['range']['max'])) {
                            return $b['range']['max'] <=> $a['range']['max'];
                        }
                        return $a['range']['min'] <=> $b['range']['min'];
                    });

                    $return['files'] = array_merge(
                        $return['files'],
                        $files
                    );
                }
        	}

        	return array(
        		'utils' => $return['utils'],
        		'json'	=> array_merge_recursive(
        			$return['json']['layout'],
        			$return['json']['outcome'],
        			$return['json']['pieces']
        		),
        		'files' => Func::rUnique($return['files'])
        	);
        }

        static function mediaMailSCSS (string $folder, array $pieces, bool $css = false): array {
            $extension = ($css ? 'css' : 'scss');

            $return = array(
        		'utils' => array(),
        		'json'	=> array(
                    'mail'      => array(),
                    'pieces'    => array()
        		),
        		'files'	=> array()
        	);

        	$array = self::utils('mails/'. $folder);
        	$return['utils'] = array_merge($return['utils'], $array['utils']);
        	$return['json']['mail'] = $array['json'];

            // find all scss files required by mail from utils
            foreach ($return['json']['mail']['scss']['files'] as $rsrc_folder => $rsrc_patterns) {
                foreach ($rsrc_patterns as $rsrc_pattern) {
                    foreach (glob("$rsrc_folder/scss/$rsrc_pattern.scss") as $rsrc_file) {
                        $return['files'][] = array(
                            'name' => (File::name(Folder::shorter($rsrc_file), false) .'.'. $extension)
                        );
                    }
                }
            }

            $files = array();
            foreach (File::folder(Folder::realpath('mails/'. $folder .'/.scss'), array('scss')) as $resolution) {
                $resolution = Folder::shorter($resolution);

                if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.scss'), $basename)
                && (!isset($basename[3]) || $basename[1] != $basename[4])) {
        			$files[] = array(
        				'name' => (File::name($resolution, false) .'.'. $extension),
        				'range' => array(
        	                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        	                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        				)
        			);
                }
            }

            // sorts the .scss folder categories in ascending order
            usort($files, function (array $a, array $b) {
                if (isset($a['range']['max']) && isset($b['range']['max'])) {
                    return $b['range']['max'] <=> $a['range']['max'];
                }
                return $a['range']['min'] <=> $b['range']['min'];
            });

            $return['files'] = array_merge(
                $return['files'],
                $files
            );

            foreach (array_unique($pieces) as $piece) {
        		$array = self::utils('pieces/' . $piece);
        		$return['utils'] = array_merge($return['utils'], $array['utils']);
        		$return['json']['pieces'] = array_merge_recursive($return['json']['pieces'], $array['json']);

        		// find all scss files required by piece from utils
                foreach ($array['json']['scss']['files'] as $rsrc_folder => $rsrc_patterns) {
                    foreach ($rsrc_patterns as $rsrc_pattern) {
                        foreach (glob("$rsrc_folder/scss/$rsrc_pattern.scss") as $rsrc_file) {
                            $return['files'][] = array(
                                'name' => (File::name(Folder::shorter($rsrc_file), false) .'.'. $extension)
                            );
                        }
                    }
                }

                $folders = explode('/', $piece);
                $path = '';

                foreach ($folders as $folder) {
                    $path .= ('/'. $folder);

                    $files = array();
                    foreach (File::folder(Folder::realpath('pieces'. $path .'/.scss'), array('scss')) as $resolution) {
                        $resolution = Folder::shorter($resolution);

                        if (preg_match("/^(\-|\+)(\d+)((\-|\+)(\d+))?$/", basename($resolution, '.scss'), $basename)
                        && (!isset($basename[3]) || $basename[1] != $basename[4])) {
        					$files[] = array(
        						'name' => (File::name($resolution, false) .'.'. $extension),
        						'range' => array(
        			                'min' => ($basename[1] == '+' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '+' ? ($basename[5]) : 0)),
        			                'max' => ($basename[1] == '-' ? ($basename[2]) : (isset($basename[3]) && $basename[4] == '-' ? ($basename[5]) : NULL))
        						)
        					);
                        }
                    }

                    // sorts the .scss folder categories in ascending order
                    usort($files, function (array $a, array $b) {
                        if (isset($a['range']['max']) && isset($b['range']['max'])) {
                            return $b['range']['max'] <=> $a['range']['max'];
                        }
                        return $a['range']['min'] <=> $b['range']['min'];
                    });

                    $return['files'] = array_merge(
                        $return['files'],
                        $files
                    );
                }
            }

        	return array(
        		'utils' => $return['utils'],
        		'json'	=> array_merge_recursive(
        			$return['json']['mail'],
        			$return['json']['pieces']
        		),
        		'files' => Func::rUnique($return['files'])
        	);
        }

    /* compile */
        private static function SASSify (array $vars, \closure $fn = NULL): string {
            foreach ($vars as $key => $value) {
                if (is_array($value)) {
                    $value = self::SASSify($value, $fn);
                }
                else {
                    if ($fn) {
                        $value = $fn($value, $key);
                    }
                    if (is_string($value) && !preg_match("/^#[0-9A-F]{6}$/i", $value)
                    && !preg_match("/[a-zA-Z][a-zA-Z0-9-]+[a-zA-Z0-9]\(.*\)/", $value)) { // isn't color
                        $value = ('"'. $value .'"');
                    }
                }

                $vars[$key] = ('"'.$key.'": '. $value);
            }

            return '('.implode(',', $vars).')';
        }

        private static function VARify (array $array, \closure $fn = NULL, string $prefix = NULL): array {
            if ($prefix == NULL) {
                $prefix = '-';
            }

            $cssvars = array();

            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $cssvars = array_merge($cssvars, self::VARify($value, $fn, $prefix.'-'.$key));
                }
                else {
                    if ($fn) {
                        $value = $fn($value, $key);
                    }
                    if (is_string($value) && !preg_match("/^#[0-9A-F]{6}$/i", $value)
                    && !preg_match("/[a-zA-Z][a-zA-Z0-9-]+[a-zA-Z0-9]\(.*\)/", $value)) { // isn't color
                        $value = ('"'. $value .'"');
                    }

                    $cssvars[] = ($prefix.'-'.$key.': '. $value.';');
                }
            }

            return $cssvars;
        }

        static function compileSCSS (string $folder, array $pieces, string $url = NULL, string $destination = NULL, Table $row = NULL, array $vars = NULL): bool {
            $pieces = array_unique($pieces);
            $media  = self::mediaSCSS($folder, $pieces);

            if ($destination && substr($destination, -1) != '/') {
                $destination .= '/';
            }
            if ($pieces) {
                sort($pieces);

                $css_file_path = ($destination.'uploads/design/css/'.$folder .'/.p/'. strtolower(implode('/.p/', $pieces)) .'/');
            }
            else {
                $css_file_path = ($destination.'uploads/design/css/'.$folder .'/');
            }
            if (!$url) {
                $url = StaticHandler::getEnvConfig('web.URL');
            }
            $time   = time();
            $return = false;

            $scss = new ScssPhp();

            $cssvars = implode("\n", self::VARify($media['json']['scss']['vars']));
            $scss->addVariables(array_merge(
                array_map(function ($value) {
                    return (is_array($value) ? self::SASSify($value) : $value);
                }, $media['json']['scss']['vars']),
                array(
                    'arshwell--env-root' => trim((strstr($url, '/') ?: ''), '/'), // the url path for root project
                    'arshwell--web-paths' => self::SASSify(StaticHandler::getEnvConfig('locations'), function (string $path = NULL, string $folder) use ($url): string {
                        // in url we use only folder, not real path
                        return ('//'. $url .'/'. trim($folder, '/') . '/'); // having one, and only one, slash at the end
                    })
                ),
                ($vars ? array_map(function ($value) {
                    return (is_array($value) ? self::SASSify($value) : $value);
                }, $vars) : array())
            ));
            $scss->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);

            $mins = array_unique(array_column(array_merge(array_column($media['files'], 'range')), 'min'));

            foreach (array_diff(File::folder($css_file_path, ['css'], false, false), $mins) as $old) {
                unlink($css_file_path.$old.'.css');
            }

            foreach ($mins as $min) {
                $files = array();
                foreach ($media['files'] as $file) {
                    if (!isset($file['range']) || $file['range']['min'] <= $min) {
                        $files[] = $file;
                    }
                }

                $css_file = ($css_file_path. $min .($row ? ('.'. str_replace('\\', '.', get_class($row)) .'.'. $row->id()) : ''). '.css');

                do {
                    if (is_file($css_file) && ($last_update = filemtime($css_file)) && !$vars
                    && (!$destination || $last_update > Folder::mTime('config/'))) {
                        foreach ($media['utils'] as $util) {
                            if (filemtime($util) >= $last_update) {
                                break 2; // go to compile
                            }
                        }
                        foreach ($files as $file) {
                            if (filemtime($file['name']) >= $last_update
                            || (!$row && !is_file('uploads/design/dev/'. File::name($file['name'], false) .'.css'))) {
                                break 2; // go to compile
                            }
                        }
                        continue 2; // skip this .css because doesn't have changes
                    }
                    break; // go to compile
                } while (false);

                $css = "
                    :root {
                        $cssvars
                    }
                ";
                foreach ($files as $file) {
                    $ranges = call_user_func(function ($range) {
                        foreach ($range as $key => $value) {
                            if ($value) {
                                $range[$key] = ($key.'-width: '.$value.'px');
                            }
                            else {
                                unset($range[$key]);
                            }
                        }
                        return $range;
                    }, $file['range'] ?? array());

                    // Is .scss for layouts & outcomes
                    if ((strpos($file['name'], 'layouts/') === 0 || strpos($file['name'], 'outcomes/') === 0) && $ranges) {
                        $css .= "@media (".implode(') and (', $ranges).") {
                                    @import '". $file['name'] ."';
                                }";
                    }
                    // Is .scss for piece
                    else if (preg_match("!^pieces(/.*)/\.scss/[^/]+\.scss$!", $file['name'], $matches)) {
                        if ($ranges) {
                            $css .= "@media (".implode(') and (', $ranges).') {';
                        }

                        $css .= "div.arshpiece". strtolower(str_replace('/', '.', $matches[1])) ." { ".
                            "display: block;".
                            "@import '". $file['name'] ."';".
                        "}";

                        if ($ranges) {
                            $css .= "}";
                        }
                    }
                    // Is .scss from resources/
                    else {
                        $css .= "@import '". $file['name'] ."';";
                    }

                    if (next($files)) {
                        // It's only a delimiter so we can split css in many dev css files.
                        $css .= '#arsavinel-arshwell'.$time.'{color:#57201412;}';
                    }
                }

                ini_set('max_execution_time', ini_get('max_execution_time') + 6);

                if (!is_dir(dirname($css_file))) {
                    mkdir(dirname($css_file), 0755, true);
                }
                file_put_contents(
                    $css_file,
                    str_replace(
                        "#arsavinel-arshwell".$time."{color:#57201412}", '',
                        self::signature($url).PHP_EOL.$scss->compileString($css)->getCss().PHP_EOL.self::signature($url)
                    ),
                    LOCK_EX
                );

                $return = true;
            }

            if ($return && !$row && !$destination) {
                ini_set('max_execution_time', ini_get('max_execution_time') + 6);

                $css = "
                    :root {
                        $cssvars
                    }
                ";
                foreach ($media['files'] as $file) {
                    $ranges = call_user_func(function ($range) {
                        foreach ($range as $key => $value) {
                            if ($value) {
                                $range[$key] = ($key.'-width: '.$value.'px');
                            }
                            else {
                                unset($range[$key]);
                            }
                        }
                        return $range;
                    }, $file['range'] ?? array());

                    // Is .scss for layouts & outcomes
                    if ((strpos($file['name'], 'layouts/') === 0 || strpos($file['name'], 'outcomes/') === 0) && $ranges) {
                        $css .= "@media (".implode(') and (', $ranges).") {
                                    @import '". $file['name'] ."';
                                }";
                    }
                    // Is .scss for piece
                    else if (preg_match("!^pieces(/.*)/\.scss/[^/]+\.scss$!", $file['name'], $matches)) {
                        if ($ranges) {
                            $css .= "@media (".implode(') and (', $ranges).') {';
                        }

                        $css .= "div.arshpiece". strtolower(str_replace('/', '.', $matches[1])) ." { ".
                            "display: block;".
                            "@import '". $file['name'] ."';".
                        "}";

                        if ($ranges) {
                            $css .= "}";
                        }
                    }
                    // Is .scss from resources/
                    else {
                        $css .= "@import '". $file['name'] ."';";
                    }

                    if (next($media['files'])) {
                        // NOTE: It's only a delimiter, so we can split compiled css in many dev css files.
                        $css .= '#arsavinel-arshwell'.$time.'{color:#57201412;}';
                    }
                }

                $scss->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::EXPANDED);

                $files = array_values($media['files']);
                foreach (preg_split("/#arsavinel-arshwell".$time."\s{\s+color:\s#57201412;\s+}/", $scss->compileString($css)->getCss()) as $nr => $code) {
                    $filename = 'uploads/design/dev/';

                    // if file has vars, we create unique dev file
                    if (!empty($media['json']['scss']['vars']) && array_filter(array_keys($media['json']['scss']['vars']), function ($var) use ($files, $nr) {
                        return (strpos(file_get_contents($files[$nr]['name']), $var) !== false);
                    })) {
                        $filename .= 'config/forks/' . $folder . '/';
                    }

                    $filename .= File::name($files[$nr]['name'], false) .'.css';

                    if (is_dir(dirname($filename)) || mkdir(dirname($filename), 0755, true)) {
                        file_put_contents($filename, $code);
                    }
                }
            }

            return $return;
        }

        static function compileJSHeader (string $folder, array $pieces, string $url = NULL, string $destination = NULL): bool {
            $pieces = array_unique($pieces);
            $media  = self::mediaJSHeader($folder, $pieces);

            if ($destination && substr($destination, -1) != '/') {
                $destination .= '/';
            }
            if ($pieces) {
                sort($pieces);

                $jsHeader_path = ($destination.'uploads/design/js/h/'.$folder .'/.p/'. strtolower(implode('/.p/', $pieces)) .'/');
            }
            else {
                $jsHeader_path = ($destination.'uploads/design/js/h/'.$folder .'/');
            }
            if (!$url) {
                $url = StaticHandler::getEnvConfig('web.URL');
            }

            $return = false;
            $forksmtime = Folder::mTime('config/forks');

            $mins = array_unique(array_column(array_merge(array_column($media['files'], 'range')), 'min'));

            foreach (array_diff(File::folder($jsHeader_path, ['js'], false, false), $mins) as $old) {
                unlink($jsHeader_path.$old.'.js');
            }

            foreach ($mins as $min) {
                $files = array();
                foreach ($media['files'] as $file) {
                    if (!isset($file['range']) || $file['range']['min'] <= $min) {
                        $files[] = $file;
                    }
                }

                $jsHeader = ($jsHeader_path. $min .'.js');

                do {
                    if (is_file($jsHeader) && ($last_update = filemtime($jsHeader))
                    && (!$destination || $last_update > Folder::mTime('config/'))) {
                        if (!is_file('uploads/design/dev/' .'dynamic/'. $folder .'/web.js')
                        || ($forksmtime >= $last_update)) {
                            break; // go to compile
                        }

                        foreach ($media['utils'] as $util) {
                            if (filemtime($util) >= $last_update) {
                                break 2; // go to compile
                            }
                        }
                        foreach ($files as $file) {
                            if (filemtime($file['name']) >= $last_update
                            || !is_file('uploads/design/dev/'.$file['name'])) {
                                break 2; // go to compile
                            }
                        }
                        continue 2; // skip this .js because doesn't have changes
                    }
                    if (!is_dir(dirname($jsHeader))) {
                        mkdir(dirname($jsHeader), 0755, true);
                    }
                    break; // go to compile
                } while (false);

                ini_set('max_execution_time', ini_get('max_execution_time') + 2);

                if (!isset($js_web_class_vars)) {
                    $routes = array();

                    $web_groups = Web::groups();
                    $exceptions = array_unique($media['json']['js']['routes']['exceptions']);
                    foreach (array_unique($media['json']['js']['routes']['groups']) as $group) {
                        if (isset($web_groups[$group])) {
                            foreach ($web_groups[$group] as $route) {
                                foreach ($exceptions as $restricted) {
                                    if (strpos($route, $restricted.'.') === 0 || $route == $restricted)
                                        continue 2;
                                }
                                $paginations = Web::route($route)[3];
                                $routes[$route] = array(
                                    'url'           => preg_replace('/\/{2,}$/', '/', $url .'/'. Web::pattern($route)),
                                    'pagination'    => array_combine(array_keys($paginations), array_column($paginations, 0))
                                );
                            }
                        }
                        else {
                            foreach ($exceptions as $restricted) {
                                if (strpos($group, $restricted.'.') === 0 || $group == $restricted) {
                                    break 2;
                                }
                            }
                            $paginations = Web::route($group)[3];
                            $routes[$group] = array(
                                'url'           => preg_replace('/\/{2,}$/', '/', $url . Web::pattern($group)),
                                'pagination'    => array_combine(array_keys($paginations), array_column($paginations, 0))
                            );
                        }
                    }

                    $route_name  = Web::nameByFolder($folder);
                    $paginations = Web::route($route_name)[3];

                    $js_minifier = new JsMin();

                    $js_minifier->add(preg_replace(
                        array("/Web\.vars\.site;/", "/Web\.vars\.statics;/", "/Web\.vars\.key;/", "/Web\.vars\.route;/", "/Web\.vars\.routes;/"),
                        array(
                            'Web.vars.site = "'. $url .'/";',
                            'Web.vars.paths = '. json_encode(array_map(function ($folder) use ($url) {
                                return $url .'/'. $folder;
                            }, array_keys(StaticHandler::getEnvConfig('locations')))) .';',
                            'Web.vars.key = "'. $route_name .'";',
                            'Web.vars.route = '. json_encode(array(
                                'url'           => preg_replace('/\/{2,}$/', '/', $url .'/'. Web::pattern($route_name)),
                                'pagination'    => array_combine(array_keys($paginations), array_column($paginations, 0))
                            )) .';',
                            'Web.vars.routes = '. json_encode($routes) .';',
                        ),
                        file_get_contents('vendor/arshwell/monolith/DevTools/tools/files/design/js/Arshwell/Web/v2.js')
                    ));

                    $js_web_class = $js_minifier->minify();
                }

                file_put_contents(
                    $jsHeader,
                    self::signature($url).PHP_EOL. $js_web_class .PHP_EOL. implode(';'.PHP_EOL.PHP_EOL, array_map(function (array $file): string {
                        $js_minifier = new JsMin($file['name']);

                        return $js_minifier->minify();
                    }, $files)) .PHP_EOL.self::signature($url),
                    LOCK_EX
                );

                $return = true;
            }

            if ($return && !$destination) {
                ini_set('max_execution_time', ini_get('max_execution_time') + 1);

                foreach ($media['files'] as $file) {
                    $dirname = dirname('uploads/design/dev/'. $file['name']);

                    if (is_dir($dirname) || mkdir($dirname, 0755, true)) {
                        file_put_contents('uploads/design/dev/'. $file['name'], '"use strict"; ' . file_get_contents($file['name']));
                    }
                }

                $js_web_class_file = 'uploads/design/dev/' .'dynamic/'. $folder .'/web.js';
                if (is_dir(dirname($js_web_class_file)) || mkdir(dirname($js_web_class_file), 0755, true)) {
                    file_put_contents($js_web_class_file, $js_web_class, LOCK_EX);
                }
            }

            return $return;
        }

        static function compileJSFooter (string $folder, array $pieces, string $destination = NULL): bool {
            $pieces = array_unique($pieces);
            $media  = self::mediaJSFooter($folder, $pieces, true);

            if ($destination && substr($destination, -1) != '/') {
                $destination .= '/';
            }
            if ($pieces) {
                sort($pieces);

                $jsFooter_path = ($destination.'uploads/design/js/f/'.$folder .'/.p/'. strtolower(implode('/.p/', $pieces)) .'/');
            }
            else {
                $jsFooter_path = ($destination.'uploads/design/js/f/'.$folder .'/');
            }
            $return = false;

            $mins = array_unique(array_column(array_merge(array_column($media['files'], 'range')), 'min'));

            foreach (array_diff(File::folder($jsFooter_path, ['js'], false, false), $mins) as $old) {
                unlink($jsFooter_path.$old.'.js');
            }

            foreach ($mins as $min) {
                $files = array();
                foreach ($media['files'] as $file) {
                    if (!isset($file['range']) || $file['range']['min'] <= $min) {
                        $files[] = $file;
                    }
                }

                $jsFooter = ($jsFooter_path. $min .'.js');

                do {
                    if (is_file($jsFooter) && ($last_update = filemtime($jsFooter))
                    && (!$destination || $last_update > Folder::mTime('config/'))) {
                        foreach ($media['utils'] as $util) {
                            if (filemtime($util) >= $last_update) {
                                break 2; // go to compile
                            }
                        }
                        foreach ($files as $file) {
                            if (filemtime($file['name']) >= $last_update
                            || !is_file('uploads/design/dev/'. $file['name'])) {
                                break 2; // go to compile
                            }
                        }
                        continue 2; // skip this .js because doesn't have changes
                    }
                    if (!is_dir(dirname($jsFooter))) {
                        mkdir(dirname($jsFooter), 0755, true);
                    }
                    break; // go to compile
                } while (false);

                ini_set('max_execution_time', ini_get('max_execution_time') + 1);

                file_put_contents(
                    $jsFooter,
                    self::signature().PHP_EOL. implode(';'.PHP_EOL.PHP_EOL, array_map(function ($file) {
                        $js_minifier = new JsMin($file['name']);

                        return $js_minifier->minify();
                    }, $files)) .PHP_EOL.self::signature(),
                    LOCK_EX
                );

                $return = true;
            }

            if ($return && !$destination) {
                ini_set('max_execution_time', ini_get('max_execution_time') + 1);

                foreach ($media['files'] as $file) {
                    $dirname = dirname('uploads/design/dev/'. $file['name']);

                    if (is_dir($dirname) || mkdir($dirname, 0755, true)) {
                        file_put_contents('uploads/design/dev/'. $file['name'], '"use strict"; ' . file_get_contents($file['name']), LOCK_EX);
                    }
                }
            }

            return $return;
        }

        static function compileMailSCSS (string $folder, array $pieces, string $url = NULL, string $destination = NULL, Table $row = NULL, array $vars = NULL): bool {
            $pieces = array_unique($pieces);
            $media  = self::mediaMailSCSS($folder, Piece::used($folder));

            if ($destination && substr($destination, -1) != '/') {
                $destination .= '/';
            }
            $destination = $destination ? Folder::realpath($destination) : Folder::root();

            if ($pieces) {
                sort($pieces);

                $css_file_path = Folder::realpath($destination).'uploads/design/mails/'.$folder .'/.p/'. strtolower(implode('/.p/', $pieces)) .'/';
            }
            else {
                $css_file_path = Folder::realpath($destination).'uploads/design/mails/'.$folder .'/';
            }
            $return = false;
            $time = time();

            $scss = new ScssPhp();

            $scss->addVariables(array_merge(
                $media['json']['scss']['vars'],
                array(
                    'env-statics' => self::SASSify(StaticHandler::getEnvConfig('locations.statics'), function (string $value) use ($url): string {
                        return ('//'. $url .'/'. substr($value, 0, -1));
                    })
                ),
                ($row ? ($vars ?? array()) : array()) // because we need custom file for custom vars
            ));

            // We need root because also crons can use this class
            $scss->setImportPaths(Folder::root());

            $scss->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);

            $mins = array_unique(array_column(array_merge(array_column($media['files'], 'range')), 'min'));

            // removing resolutions non existent anymore
            foreach (array_diff(File::folder($css_file_path, ['css'], false, false), $mins) as $old) {
                unlink($css_file_path.$old.'.css');
            }

            foreach ($mins as $min) {
                $files = array();
                foreach ($media['files'] as $file) {
                    if (!isset($file['range']) || $file['range']['min'] <= $min) {
                        $files[] = $file;
                    }
                }

                $css_file = ($css_file_path. $min .($row ? ('.'. str_replace('\\', '.', get_class($row)) .'.'. $row->id()) : ''). '.css');

                do {
                    if (is_file($css_file) && ($last_update = filemtime($css_file))
                    && (!$destination || $last_update > Folder::mTime('config/'))) {
                        foreach ($media['utils'] as $util) {
                            if (filemtime($util) >= $last_update) {
                                break 2; // go to compile
                            }
                        }
                        foreach ($files as $file) {
                            if (filemtime(Folder::realpath($file['name'])) >= $last_update
                            || !is_file(Folder::realpath('uploads/design/dev/'). File::name($file['name'], false) .'.css')) {
                                break 2; // go to compile
                            }
                        }
                        continue 2; // skip this .css because doesn't have changes
                    }
                    break; // go to compile
                } while (false);

                $css = '';

                foreach ($files as $file) {
                    // Is .scss for mail
                    if (strpos($file['name'], 'mails/') === 0) {
                        $css .= "@media ".($file['range']['min'] ? "(min-width: ".$file['range']['min']."px)" : '').
                                ($file['range']['max'] ?
                                    ($file['range']['min'] ? ' and ' : '') .
                                    "(max-width: ".$file['range']['max']."px) " : '') .
                                "{".
                                    file_get_contents(Folder::realpath($file['name'])) .
                                "}";
                    }
                    // Is .scss for piece
                    else if (preg_match("!^pieces(/.*)/.scss/[^/]+\.scss$!", $file['name'], $matches)) {
                        $css .= "@media ".($file['range']['min'] ? "(min-width: ".$file['range']['min']."px)" : '').
                                    ($file['range']['max'] ?
                                    ($file['range']['min'] ? ' and ' : '') .
                                    "(max-width: ".$file['range']['max']."px) " : '') .
                                "{".
                                    "div.arshpiece". strtolower(str_replace('/', '.', $matches[1])) ."{".
                                        "display: block;".
                                        file_get_contents(Folder::realpath($file['name'])) .
                                    "}".
                                "}";
                    }
                    // Is .scss from resources/
                    else {
                        $css .= "@import '". $file['name'] ."';";
                    }

                    if (next($files)) {
                        // It's only a delimiter so we can split css in many dev css files.
                        $css .= '#arsavinel-arshwell'.$time.'{color:#57201412;}';
                    }
                }

                ini_set('max_execution_time', ini_get('max_execution_time') + 6);

                if (!is_dir(dirname($css_file))) {
                    mkdir(dirname($css_file), 0755, true);
                }
                file_put_contents(
                    $css_file,
                    str_replace(
                        "#arsavinel-arshwell".$time."{color:#57201412}", '',
                        self::signature($url).PHP_EOL.$scss->compileString($css)->getCss().PHP_EOL.self::signature($url)
                    ), LOCK_EX);

                $return = true;
            }

            if ($return && !$row && $destination == Folder::root()) {
                ini_set('max_execution_time', ini_get('max_execution_time') + 6);

                $css = '';
                foreach ($media['files'] as $file) {
                    // Is .scss for mail
                    if (strpos($file['name'], 'mails/') === 0) {
                        $css .= "@media ".($file['range']['min'] ? "(min-width: ".$file['range']['min']."px)" : '').
                                ($file['range']['max'] ?
                                    ($file['range']['min'] ? ' and ' : '') .
                                    "(max-width: ".$file['range']['max']."px) " : '') .
                                "{".
                                    file_get_contents(Folder::realpath($file['name'])) .
                                "}";
                    }
                    // Is .scss for piece
                    else if (preg_match("!^pieces(/.*)/.scss/[^/]+\.scss$!", $file['name'], $matches)) {
                        $css .= "@media ".($file['range']['min'] ? "(min-width: ".$file['range']['min']."px)" : '').
                                    ($file['range']['max'] ?
                                    ($file['range']['min'] ? ' and ' : '') .
                                    "(max-width: ".$file['range']['max']."px) " : '') .
                                "{".
                                    "div.arshpiece". strtolower(str_replace('/', '.', $matches[1])) ."{".
                                        "display: block;".
                                        file_get_contents(Folder::realpath($file['name'])) .
                                    "}".
                                "}";
                    }
                    // Is .scss from resources/
                    else {
                        $css .= "@import '". $file['name'] ."';";
                    }

                    if (next($media['files'])) {
                        // It's only a delimiter so we can split css in many dev css files.
                        $css .= '#arsavinel-arshwell'.$time.'{color:#57201412;}';
                    }
                }

                $scss->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::EXPANDED);

                $files = array_values($media['files']);
                foreach (preg_split("/#arsavinel-arshwell".$time."\s{\s+color:\s#57201412;\s+}/", $scss->compileString($css)->getCss()) as $nr => $code) {
                    $filename = Folder::realpath('uploads/design/dev/'). File::name(Folder::shorter($files[$nr]['name']), false) .'.css';

                    if (is_dir(dirname($filename)) || mkdir(dirname($filename), 0755, true)) {
                        file_put_contents($filename, $code, LOCK_EX);
                    }
                }
            }

            return $return;
        }

    /* recompile */
        static function recompileSCSS (string $dirname = NULL, Table $row = NULL, array $vars = NULL, string $url = NULL, string $destination = NULL): int {
            $path       = 'uploads/design/css/';
            $compiled   = 0;

            if ($dirname) {
                $dirname .= '/';
            }

            if (is_dir($path.$dirname)) {
                foreach (Folder::children($path.$dirname, true) as $dir) {
                    $files = File::folder($path.$dirname.$dir, ['css'], false, false);

                    if ($files) {
                        $pieces = explode('/.p/', $dirname.$dir);
                        $folder = array_shift($pieces);
                        $route  = Web::nameByFolder($folder);

                        if (!$route || !Web::exists($route) || !Web::allows('GET', $route)
                        || ($pieces && call_user_func(function () use ($pieces) {
                            foreach ($pieces as $piece) {
                                if (!is_dir('pieces/'.$piece)) {
                                    return true;
                                }
                            }
                            return false;
                        }))) {
                            // we don't remove entire folder 'cause there could be subfolders
                            foreach (File::folder($path.$dirname.$dir) as $file) {
                                unlink($file);
                            }
                        }
                        else {
                            if (!$row) {
                                $custom_css = array();

                                foreach ($files as $filename) {
                                    if (substr_count($filename, '.') > 1
                                    && preg_match("/^\d+\.(?<table>.*)\.(?<id_table>\d+)$/", $filename, $matches)) {
                                        $custom_css[$matches['table'].$matches['id_table']] = $matches; // adding key for not having duplicates
                                    }
                                }

                                if (self::compileSCSS($folder, $pieces, $url, $destination, NULL, $vars)) {
                                    $compiled++;
                                }

                                foreach ($custom_css as $matches) {
                                    $matches['table'] = str_replace('.', '\\', $matches['table']);
                                    $object = (new $matches['table'])::get($matches['id_table'], $matches['table']::CUSTOM_SCSS_VARS_COLUMN);

                                    if (self::compileSCSS(
                                        $folder, $pieces, $url, $destination, $object,
                                        unserialize($object->{$matches['table']::CUSTOM_SCSS_VARS_COLUMN})
                                    )) {
                                        $compiled++;
                                    }
                                }
                            }
                            else if (self::compileSCSS($folder, $pieces, $url, $destination, $row, $vars)) {
                                $compiled++;
                            }
                        }
                    }

                    $compiled += self::recompileSCSS($dirname.$dir, $row, $vars, $url, $destination);
                }

                Folder::removeEmpty($path);
            }

            return $compiled;
        }

        static function recompileJSHeader (string $dirname = NULL, string $url = NULL, string $destination = NULL): int {
            $path       = 'uploads/design/js/h/';
            $compiled   = 0;

            if ($dirname) {
                $dirname .= '/';
            }

            if (is_dir($path.$dirname)) {
                foreach (Folder::children($path.$dirname, true) as $dir) {
                    $files = File::folder($path.$dirname.$dir, ['js'], false, false);

                    if ($files) {
                        $pieces = explode('/.p/', $dirname.$dir);
                        $folder = array_shift($pieces);
                        $route  = Web::nameByFolder($folder);

                        if (!$route || !Web::exists($route) || !Web::allows('GET', $route)
                        || ($pieces && call_user_func(function () use ($pieces) {
                            foreach ($pieces as $piece) {
                                if (!is_dir('pieces/'.$piece)) {
                                    return true;
                                }
                            }
                            return false;
                        }))) {
                            // we don't remove entire folder 'cause there could be subfolders
                            foreach (File::folder($path.$dirname.$dir) as $file) {
                                unlink($file);
                            }
                        }
                        else if (self::compileJSHeader($folder, $pieces, $url, $destination)) {
                            $compiled++;
                        }
                    }

                    $compiled += self::recompileJSHeader($dirname.$dir, $url, $destination);
                }

                Folder::removeEmpty($path);
            }

            return $compiled;
        }

        static function recompileJSFooter (string $dirname = NULL, string $destination = NULL): int {
            $path       = 'uploads/design/js/f/';
            $compiled   = 0;

            if ($dirname) {
                $dirname .= '/';
            }

            if (is_dir($path.$dirname)) {
                foreach (Folder::children($path.$dirname, true) as $dir) {
                    $files = File::folder($path.$dirname.$dir, ['js'], false, false);

                    if ($files) {
                        $pieces = explode('/.p/', $dirname.$dir);
                        $folder = array_shift($pieces);
                        $route  = Web::nameByFolder($folder);

                        if (!$route || !Web::exists($route) || !Web::allows('GET', $route)
                        || ($pieces && call_user_func(function () use ($pieces) {
                            foreach ($pieces as $piece) {
                                if (!is_dir('pieces/'.$piece)) {
                                    return true;
                                }
                            }
                            return false;
                        }))) {
                            // we don't remove entire folder 'cause there could be subfolders
                            foreach (File::folder($path.$dirname.$dir) as $file) {
                                unlink($file);
                            }
                        }
                        else if (self::compileJSFooter($folder, $pieces, $destination)) {
                            $compiled++;
                        }
                    }

                    $compiled += self::recompileJSFooter($dirname.$dir, $destination);
                }

                Folder::removeEmpty($path);
            }

            return $compiled;
        }

        static function recompileMailSCSS (string $dirname = NULL, Table $row = NULL, array $vars = NULL, string $url = NULL, string $destination = NULL): int {
            $path       = 'uploads/design/mails/';
            $compiled   = 0;

            if ($dirname) {
                $dirname .= '/';
            }

            if (is_dir($path.$dirname)) {
                foreach (Folder::children($path.$dirname, true) as $dir) {
                    $files = File::folder($path.$dirname.$dir, ['css'], false, false);

                    if ($files) {
                        $pieces = explode('/.p/', $dirname.$dir);
                        $folder = array_shift($pieces);

                        if ($pieces && call_user_func(function () use ($pieces) {
                            foreach ($pieces as $piece) {
                                if (!is_dir('pieces/'.$piece)) {
                                    return true;
                                }
                            }
                            return false;
                        })) {
                            // we don't remove entire folder 'cause there could be subfolders
                            foreach (File::folder($path.$dirname.$dir) as $file) {
                                unlink($file);
                            }
                        }
                        else {
                            if (!$row) {
                                $custom_css = array();

                                foreach ($files as $filename) {
                                    if (substr_count($filename, '.') > 1
                                    && preg_match("/^\d+\.(?<table>.*)\.(?<id_table>\d+)$/", $filename, $matches)) {
                                        $custom_css[$matches['table'].$matches['id_table']] = $matches; // adding key for not having duplicates
                                    }
                                }

                                if (self::compileMailSCSS($folder, $pieces, $url, $destination)) {
                                    $compiled++;
                                }

                                foreach ($custom_css as $matches) {
                                    $matches['table'] = str_replace('.', '\\', $matches['table']);
                                    $object = (new $matches['table'])::get($matches['id_table'], $matches['table']::CUSTOM_SCSS_VARS_COLUMN);

                                    if (self::compileMailSCSS(
                                        $folder, $pieces, $url, $destination, $object,
                                        unserialize($object->{$matches['table']::CUSTOM_SCSS_VARS_COLUMN})
                                    )) {
                                        $compiled++;
                                    }
                                }
                            }
                            else if (self::compileMailSCSS($folder, $pieces, $url, $destination, $row, $vars)) {
                                $compiled++;
                            }
                        }
                    }

                    $compiled += self::recompileMailSCSS($dirname.$dir, $row, $vars, $url, $destination);
                }

                Folder::removeEmpty($path);
            }

            return $compiled;
        }

    static function utils (string $path): array {
        $utils = array();
        $json  = array(
            'scss' => array(
                'vars'  => array(),
                'files' => array()
            ),
            'js'   => array(
                'routes' => array(
                    'groups'        => array(),
                    'exceptions'    => array()
                ),
                'files' => array(
                    'header' => array(),
                    'footer' => array()
                )
            )
        );

        $type = substr(explode('/', $path, 2)[0], 0, -1); // layout / mail / outcome / piece

    	$dirname = (is_file(Folder::realpath($path)) ? dirname($path) : $path);

    	do {
            if (is_file(($file = Folder::realpath($dirname .'/utils.'. $type .'.json')))) {
    			$utils[] = $file;
                $json = array_merge_recursive(
    				json_decode(file_get_contents($file), true),
    				$json
    			);
    		}

    		$dirname = dirname($dirname, 1);
    	} while ($dirname != '.');

        if (!empty($json['layout']) && is_array($json['layout'])) {
            $json['layout'] = $json['layout'][array_key_last($json['layout'])];
        }

    	return array(
            'utils' => $utils,
            'json'  => $json
        );
    }

    static function useCustomCSS (Table ...$rows): void {
        self::$css_suffixes = array_map(function ($row) {
            return ('.'. str_replace('\\', '.', get_class($row)) .'.'. $row->id());
        }, $rows);

        self::$css_suffixes[] = '';
    }

    static function devFiles (string $route = NULL, array $pieces = NULL): array {
        $path   = StaticHandler::getEnvConfig()->getSiteRoot() . '/uploads/design/dev/';
        $pieces = array_unique($pieces ?? Piece::used());
        $folder = Web::folder($route);

        $links = array(
            'css'   => array_map(function (array $file) use ($folder, $path) {
                    if (is_file('uploads/design/dev/config/forks/'.$folder.'/'.$file['name'])) {
                        return $path.'config/forks/'.$folder.'/'.$file['name'];
                    }
                    return $path.$file['name'];
                },
                Layout::mediaSCSS($folder, $pieces, true)['files']
            ),
            'js'    => array(
                'header' => array_map(function (array $file) use ($folder, $path) {
                        return $path.$file['name'];
                    },
                    Layout::mediaJSHeader($folder, $pieces)['files']
                ),
                'footer' => array_map(function (array $file) use ($folder, $path) {
                        return $path.$file['name'];
                    },
                    Layout::mediaJSFooter($folder, $pieces, true)['files']
                )
            )
        );

        array_unshift($links['js']['header'], $path.'dynamic/'. $folder .'/web.js');

        return $links;
    }

    static function mediaLinks (string $route = NULL, array $pieces = NULL): array {
        $site           = Web::site();
        $pieces         = array_unique($pieces ?? Piece::used()); // copy so we can sort it
        $pieces_path    = NULL;
        $folder         = Web::folder($route);

        if ($pieces) {
            sort($pieces);

            $pieces_path = ('.p/'. strtolower(implode('/.p/', $pieces)) .'/');
        }

        $css_files  = File::folder('uploads/design/css/'. $folder .'/'. $pieces_path, array('css'), false, false);
        $css_path   = 'uploads/design/css/'. $folder .'/'. $pieces_path . (Session::design() ? Func::closestDown(Session::design(), $css_files) : max($css_files));

        foreach (self::$css_suffixes as $suffix) {
            if (is_file($css_path . $suffix . '.css')) {
                $mediaLinks = array(
                    'paths' => array(
                        'css'   => $css_path . $suffix . '.css',
                        'js'    => array(
                            'header' => call_user_func(function (array $js_header_files) use ($site, $folder, $pieces_path) {
                                    return ('uploads/design/js/h/'. $folder .'/'. $pieces_path . (Session::design() ? Func::closestDown(Session::design(), $js_header_files) : max($js_header_files)) .'.js');
                                },
                                File::folder('uploads/design/js/h/'. $folder .'/'. $pieces_path, array('js'), false, false)
                            ),
                            'footer' => call_user_func(function (array $js_footer_files) use ($site, $folder, $pieces_path) {
                                    return ('uploads/design/js/f/'. $folder .'/'. $pieces_path . (Session::design() ? Func::closestDown(Session::design(), $js_footer_files) : max($js_footer_files)) .'.js');
                                },
                                File::folder('uploads/design/js/f/'. $folder .'/'. $pieces_path, array('js'), false, false)
                            )
                        )
                    )
                );
                $mediaLinks['urls'] = array(
                    'css'   => $site . $mediaLinks['paths']['css'],
                    'js'    => array(
                        'header' => $site . $mediaLinks['paths']['js']['header'],
                        'footer' => $site . $mediaLinks['paths']['js']['footer']
                    )
                );
                return $mediaLinks;
            }
        }
    }

    /**
     * Signature added in production final media files (css/js).

     * @return string
     */
    private static function signature (string $site = NULL): string {
        $credits = StaticHandler::getEnvConfig('development.credits');

        $text_1 = 'PHP Framework used: https://github.com/arshwell/monolith';
        $text_2 = 'Website'. ($site ? " ($site)" : '') .' developed by ['. implode(' | ', $credits) .']';

        $len_1 = strlen($text_1);
        $len_2 = strlen($text_2);
        $maxlen = max($len_1, $len_2);

        return (
            '/*****'. str_repeat('*', $maxlen) .'******'. PHP_EOL.
            '***   '. str_repeat(' ', $maxlen) .'   ***'. PHP_EOL.

            // show PHP framework used
            '***   '. $text_1 .str_repeat(' ', $maxlen - $len_1).'   ***'. PHP_EOL.

            // empty line
            ($credits ? ('***   '. str_repeat(' ', $maxlen) .'   ***'.PHP_EOL) : '').

            // show credits for website, if any
            ($credits ? ('***   '. $text_2 .str_repeat(' ', $maxlen - $len_2).'   ***'. PHP_EOL) : '').

            '***   '. str_repeat(' ', $maxlen) .'   ***'.PHP_EOL.
            '******'. str_repeat('*', $maxlen) .'*****/'
        );
    }
}
