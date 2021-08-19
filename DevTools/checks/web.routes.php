<?php

/**
 * Verifies if routes are properly created.

 * @package Arsh/DevTools/checks
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
 */
call_user_func(function () {
    $code = '';
    $routes = array();

    $getforks = function (string $dir) use (&$getforks) {
        $jsons = array();

        foreach (scandir($dir) as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($dir .'/'. $file)) {
                    $jsons = array_replace($jsons, $getforks($dir .'/'. $file));
                }
                else {
                    $jsons[str_replace(
                        '/', '.',
                        preg_replace(
                            "~forks/(.*).json~",
                            "$1",
                            preg_replace(
                                "~(/)(\d+\.)(?!(/|json$))~",
                                "$1$3",
                                $dir .'/'. $file
                            )
                        )
                    )] = json_decode(file_get_contents($dir .'/'. $file), true);

                    if (json_last_error()) {
                        _json_last_error();
                    }
                }
            }
        }

        return $jsons;
    };

    // Getting routes
    $pull = function (string $group = NULL, array $array) use (&$pull, &$routes) {
        foreach ($array as $name => $route) {
            if (is_array(array_values($route)[0])) {
                $pull("$group$name.", $route);
            }
            else {
                $name = ($name != '#' ? ($group.$name) : substr($group, 0, -1));
                array_unshift($route, str_replace('.', '/', $name));

                if (!is_array($route[2]))
                    $route[2] = array($route[2]);
                $routes[$name] = $route;
            }
        }
    };
    $pull(NULL, $getforks('forks'));


    /* Checking Route requests */

        // Parsing routes looking for forbidden combinations of requests
        // foreach ($routes as $name => $route) {
        //     if (count(array_intersect(explode('|', $route[1]), array('GET', 'AJAX'))) == 2) {
        //         if ($code)
        //             $code .= _hr();
        //         $code .= (_string($name) . _result() . _hug('array', _string($route[0]) .', '. _string($route[1]) .', '. _array($route[2])) .',<br>');
        //     }
        // }
        // if ($code) {
        //     _html(
        //         '<i>ways/web.json</i><br>' .
        //         _code($code) .
        //         _error("This combination, of GET and AJAX requests, will spoil things.")
        //     );
        // }


    /* Checking Route Folders */

        foreach (array_column($routes, 0) as $nth => $folder) {
            if (preg_match("~/.(less|js)(/|$)~", $folder)) {
                if ($code)
                    $code .= _hr();

                $name = array_keys($routes)[$nth];
                $route = $routes[$name];

                $code .= (_string($name) . _result() . _hug('array', _string($route[0]) .', '. _string($route[1]) .', '. _array($route[2])) .',<br>');
            }
        }
        if ($code) {
            _html(
                '<i>ways/web.json</i><br>' .
                _code($code) .
                _error("<i>/.less/</i> and <i>/.js/</i> folders are reserved by ArshWell for .less and .js files.")
            );
        }


    /* Checking Route urls */

        $duplicates = array_filter(array_count_values(array_map(function ($route) {
            $route[2] = implode(',', $route[2]);
            return implode(',', $route);
        }, $routes)), function ($count) {
            return ($count > 1);
        });
        foreach ($duplicates as $value => $count) {
            $name = array_search(explode(',', $value), $routes);
            $route = $routes[$name];
            if ($code)
                $code .= _hr();
            $code .= (_string($name) . _result() . _hug('array', _string($route[0]) .', '. _string($route[1]) .', '. _array($route[2])) .',<br>');
        }
        if ($code) {
            _html(
                '<i>ways/web.json</i><br>' .
                _code($code) .
                _error((count($duplicates) == 1 ? "This url is" : "These urls are") . " repeated, the same, many times in file.")
            );
        }
});
