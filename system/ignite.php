<?php


// error_reporting(E_ALL);
// ini_set('display_errors', 1);

ini_set('error_log', SYSTEM . DS . 'log' . DS . 'errors.txt');
ini_set('session.gc_probability', 1);


/**
 * => `http://www.php.net/manual/en/security.magicquotes.disabling.php`
 * --------------------------------------------------------------------
 */

$gpc = array(&$_GET, &$_POST, &$_REQUEST, &$_COOKIE);

array_walk_recursive($gpc, function(&$value) {
    $value = str_replace("\r", "", $value);
    if(get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }
});


/**
 * Loading Workers
 * ---------------
 */

spl_autoload_register(function($worker) {
    $path = SYSTEM . DS . 'kernel' . DS . strtolower($worker) . '.php';
    if(file_exists($path)) require $path;
});


/**
 * Start the Sessions
 * ------------------
 */

session_save_path(SYSTEM . DS . 'log' . DS . 'sessions');
session_start();


/**
 * Load the Configuration Data
 * ---------------------------
 */

Config::load();

$config = Config::get();
$speak = Config::speak();


/**
 * First Installation
 * ------------------
 */

if(File::exist(ROOT . DS . 'install.php')) {
    Guardian::kick($config->url . '/install.php');
}


/**
 * Set Default Time Zone Before Launch
 * -----------------------------------
 */

date_default_timezone_set($config->timezone);


/**
 * Inject Widget's CSS and JavaScript
 * ----------------------------------
 */

Weapon::add('shell_before', function() {
    echo Asset::stylesheet('cabinet/shields/widgets.css');
});

Weapon::add('SHIPMENT_REGION_BOTTOM', function() {
    echo Asset::javascript('cabinet/shields/widgets.js');
});


/**
 * Loading Plugins
 * ---------------
 */

foreach(glob(PLUGIN . DS . '*', GLOB_ONLYDIR) as $plugin) {
    if( ! $language = File::exist($plugin . DS . 'languages' . DS . $config->language . DS . 'speak.txt')) {
        $language = $plugin . DS . 'languages' . DS . 'en_US' . DS . 'speak.txt';
    }
    if(File::exist($language)) {
        Config::merge('speak', Text::toArray(File::open($language)->read(), ':', '  '));
    }
    if($launch = File::exist($plugin . DS . 'launch.php')) {
        include $launch;
    }
}


/**
 * Include User Defined Functions
 * ------------------------------
 */

if($function = File::exist(SHIELD . DS . $config->shield . DS . 'functions.php')) {
    include $function;
}


/**
 * Handle Shortcode in Contents
 * ----------------------------
 */

Filter::add('shortcode', function($content) use($config, $speak) {
    if(strpos($content, '{{') === false) {
        return $content;
    }
    $shortcodes = include STATE . DS . 'repair.shortcodes.php';
    if($file = File::exist(STATE . DS . 'shortcodes.txt')) {
        $shortcodes = array_replace_recursive($shortcodes, File::open($file)->unserialize());
    }
    $regex = array();
    foreach($shortcodes as $key => $value) {
        $regex['#(?!`)' . str_replace(
            array(
                '%s'
            ),
            array(
                '(.*?)'
            ),
        preg_quote($key, '#')) . '(?!`)#'] = $value;
    }
    $content = preg_replace(array_keys($regex), array_values($regex), $content);
    if(strpos($content, '{{php}}') !== false) {
        $content = preg_replace_callback('#(?!`)\{\{php\}\}(?!`)([\s\S]+?)(?!`)\{\{\/php\}\}(?!`)#m', function($matches) {
            return Converter::phpEval($matches[1]);
        }, $content);
    }
    return preg_replace('#`\{\{(.*?)\}\}`#', '{{$1}}', $content);
}, 10);


/**
 * Others
 * ------
 *
 * I'm trying to not touching the source code of the Markdown plugin at all.
 *
 * [1]. Add bordered class for tables in contents.
 * [2]. Add `rel="nofollow"` attribute in external links.
 *
 */

Filter::add('content', function($content) use($config) {
    return preg_replace(
        array(
            '#<table>#',
            '#<a href="(https?\:\/\/)(?!' . preg_quote($config->host, '/') . ')#'
        ),
        array(
            '<table class="table-bordered table-full">',
            '<a rel="nofollow" href="$1'
        ),
    $content);
}, 10);


/**
 * Set Page Metadata
 * -----------------
 */

Weapon::add('meta', function() {
    $config = Config::get();
    $speak = Config::speak();
    $html  = O_BEGIN . '<meta charset="' . $config->charset . '"' . ES . NL;
    $html .= str_repeat(TAB, 2) . '<meta name="viewport" content="width=device-width"' . ES . NL;
    if(isset($config->article->description)) {
        $html .= str_repeat(TAB, 2) . '<meta name="description" content="' . strip_tags($config->article->description) . '"' . ES . NL;
    } elseif(isset($config->page->description)) {
        $html .= str_repeat(TAB, 2) . '<meta name="description" content="' . strip_tags($config->page->description) . '"' . ES . NL;
    } else {
        $html .= str_repeat(TAB, 2) . '<meta name="description" content="' . strip_tags($config->description) . '"' . ES . NL;
    }
    $html .= str_repeat(TAB, 2) . '<meta name="author" content="' . $config->author . '"' . ES . NL;
    echo Filter::apply('meta', $html, 1);
}, 10);

Weapon::add('meta', function() {
    $config = Config::get();
    $html  = str_repeat(TAB, 2) . '<title>' . strip_tags($config->page_title) . '</title>' . NL;
    $html .= str_repeat(TAB, 2) . '<!--[if IE]><script src="' . $config->protocol . 'html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->' . NL;
    echo Filter::apply('meta', $html, 2);
}, 20);

Weapon::add('meta', function() {
    $config = Config::get();
    $speak = Config::speak();
    $html  = str_repeat(TAB, 2) . '<link href="' . $config->url . '/favicon.ico" rel="shortcut icon" type="image/x-icon"' . ES . NL;
    $html .= str_repeat(TAB, 2) . '<link href="' . $config->url_current . '" rel="canonical"' . ES . NL;
    $html .= str_repeat(TAB, 2) . '<link href="' . $config->url . '/sitemap" rel="sitemap"' . ES . NL;
    $html .= str_repeat(TAB, 2) . '<link href="' . $config->url . '/feeds/rss" rel="alternate" type="application/rss+xml" title="' . $speak->feeds . $config->title_separator . $config->title . '"' . ES . O_END;
    echo Filter::apply('meta', $html, 3);
}, 30);

Weapon::add('SHIPMENT_REGION_TOP', function() {
    Weapon::fire('meta');
}, 10);