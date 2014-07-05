<?php

/**
 * ==========================================================
 *  SHIELD ATTACHER
 * ==========================================================
 */

class Shield {

    private static $defines = array();

    private static function pathTrace($name) {
        $name = rtrim($name, '\\/') . '.php';
        if($file = File::exist(SHIELD . DS . Config::get('shield') . DS . ltrim($name, '\\/'))) {
            return $file;
        } else {
            if($file = File::exist(ROOT . DS . ltrim($name, '\\/'))) {
                return $file;
            }
        }
        return $name;
    }

    /**
     * Do Nothing
     * ----------
     */

    private static function desanitize_output($buffer) {
        $buffer = Filter::apply('sanitize:input', $buffer);
        return Filter::apply('sanitize:output', $buffer);
    }

    /**
     * Minify HTML Output
     * ------------------
     */

    private static function sanitize_output($buffer) {
        $buffer = Filter::apply('sanitize:input', $buffer);
        $str = array(
            '#\<\!--(?!\[if)([\s\S]+?)--\>#' => "", // remove comments in HTML
            '#\>[^\S ]+#s' => '>', // strip whitespaces after tags, except space
            '#[^\S ]+\<#s' => '<', // strip whitespaces before tags, except space
            '#\>\s{2,}\<#s' => '><' // strip multiple whitespaces between closing and opening tag
        );
        $buffer = preg_replace(array_keys($str), array_values($str), $buffer);
        return Filter::apply('sanitize:output', $buffer);
    }

    /**
     * Default Shortcut Variables
     * --------------------------
     */

    private static function defines() {
        $config = Config::get();
        $results = array(
            'config' => $config,
            'speak' => $config->speak,
            'articles' => $config->articles,
            'article' => $config->article,
            'pages' => $config->pages,
            'page' => $config->page,
            'responses' => $config->responses,
            'response' => $config->response,
            'files' => $config->files,
            'file' => $config->file,
            'pager' => $config->pagination,
            'manager' => Guardian::happy()
        );
        return array_merge($results, self::$defines);
    }

    /**
     * ==========================================================
     *  DEFINE NEW SHORTCUT VARIABLE(S)
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::define('foo', 'bar')->attach('file');
     *
     *    Shield::define(array(
     *        'foo' => 'bar',
     *        'baz' => 'qux'
     *    ))->attach('file');
     *
     * ----------------------------------------------------------
     *
     */

    public static function define($key, $value = "") {
        if(is_array($key)) {
            self::$defines = array_merge(self::$defines, $key);
        } else {
            self::$defines[$key] = $value;
        }
        return new static;
    }

    /**
     * ==========================================================
     *  UNDEFINE SHORTCUT VARIABLE(S)
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::undefine('foo')->attach('file');
     *
     *    Shield::undefine(array('foo', 'bar'))->attach('file');
     *
     * ----------------------------------------------------------
     *
     */

    public static function undefine($defines) {
        if( ! is_array($defines)) $defines = array($defines);
        foreach($defines as $define) {
            unset(self::$defines[$define]);
        }
        return new static;
    }

    /**
     * ==========================================================
     *  GET SHIELD INFO
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    var_dump(Shield::info('aero'));
     *
     * ----------------------------------------------------------
     *
     */

    public static function info($folder = null) {
        $config = Config::get();
        $speak = Config::speak();
        if(is_null($folder)) {
            $folder = $config->shield;
        }
        // Check whether the localized "about" file is available
        if( ! $info = File::exist(SHIELD . DS . $folder . DS . 'about.' . $config->language . '.txt')) {
            $info = SHIELD . DS . $folder . DS . 'about.txt';
        }
        $e_shield_page = "Name: " . $speak->unknown . "\n" .
             "Author: " . $speak->unknown . "\n" .
             "URL: #\n" .
             "Version: " . $speak->unknown . "\n" .
             "\n" . SEPARATOR . "\n" .
             "\n" . Config::speak('notify_not_available', array($speak->description));
        $shield_info = File::exist($info) ? Text::toPage(File::open($info)->read(), true, 'shield:') : Text::toPage($e_shield_page, true, 'shield:');
        return Mecha::O($shield_info);
    }

    /**
     * ==========================================================
     *  RENDER A PAGE
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    Shield::attach('article', true, false);
     *
     * ----------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type    | Description
     *  ---------- | ------- | ----------------------------------
     *  $name      | string  | Name of the shield
     *  $minify    | boolean | Minify HTML output?
     *  $cacheable | boolean | Create a cache file on page visit?
     *  ---------- | ------- | ----------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function attach($name, $minify = true, $cacheable = false) {

        $G = array('data' => array('name' => $name, 'minify' => $minify, 'cacheable' => $cacheable));

        Weapon::fire('before_shield_config_redefine', array($G, $G));

        extract(self::defines());

        $base = explode('-', $name, 2);

        Weapon::fire('after_shield_config_redefine', array($G, $G));

        if($_file = File::exist(self::pathTrace($name))) {
            $shield = $_file;
        } elseif($_file = File::exist(self::pathTrace($base[0]))) {
            $shield = $_file;
        } else {
            Guardian::abort(Config::speak('notify_file_not_exist', array('<code>' . self::pathTrace($name) . '</code>')));
        }

        $cache = CACHE . DS . str_replace('/', '.', trim($_SERVER['REQUEST_URI'], '\\/')) . '.cache.txt';

        if($cacheable && File::exist($cache)) {
            echo File::open($cache)->read();
            exit;
        }

        ob_start($minify ? 'self::sanitize_output' : 'self::desanitize_output');

        Weapon::fire('shield_before', array($G, $G));

        require Filter::apply('shield:path', $shield);

        Weapon::fire('shield_after', array($G, $G));

        if($cacheable) {
            $G['data']['cache'] = ob_get_contents();
            File::write($G['data']['cache'])->saveTo($cache);
            Weapon::fire('on_cache_construct', array($G, $G));
        }

        Guardian::forget();

        ob_end_flush();

        exit;

    }

    /**
     * ==========================================================
     *  RENDER A 404 PAGE
     * ==========================================================
     *
     * -- CODE: -------------------------------------------------
     *
     *    [1]. Shield::abort();
     *
     *    [2]. Shield::abort('404-custom');
     *
     * ----------------------------------------------------------
     *
     */

    public static function abort($name = null, $minify = true) {

        $G = array('data' => array('name' => $name, 'minify' => $minify));

        Config::set('page_type', '404');

        Weapon::fire('before_shield_config_redefine', array($G, $G));

        extract(self::defines());

        Weapon::fire('after_shield_config_redefine', array($G, $G));

        if( ! is_null($name) && File::exist(SHIELD . DS . $config->shield . DS . $name . '.php')) {
            $shield = SHIELD . DS . $config->shield . DS . $name . '.php';
        } else {
            $shield = SHIELD . DS . $config->shield . DS . '404.php';
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

        ob_start($minify ? 'self::sanitize_output' : 'self::desanitize_output');

        Weapon::fire('shield_before', array($G, $G));

        require Filter::apply('shield:path', $shield);

        Weapon::fire('shield_after', array($G, $G));

        Guardian::forget();

        ob_end_flush();

        exit;

    }

}