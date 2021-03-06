<?php


/**
 * ===================================================================
 *  APPLY FILTER WITH PREFIX
 * ===================================================================
 *
 * -- CODE: ----------------------------------------------------------
 *
 *    Filter::colon('page:title', $content);
 *
 *    // is equal to ...
 *
 *    Filter::apply(array('page:title', 'title'), $content);
 *
 * -------------------------------------------------------------------
 *
 */

Filter::plug('colon', function($name, $value) {
    $arguments = func_get_args();
    if(strpos($name, ':') !== false) {
        $s = explode(':', $name, 2);
        $arguments[0] = array($name, $s[1]);
    }
    return call_user_func_array('Filter::apply', $arguments);
});

// Include comment(s) data to the post
function do_comments_field($results, $FP, $data) {
    if( ! isset($data['id'])) return $results;
    $speak = Config::speak();
    $c = array();
    $cc = 0;
    $ccc = '0 ' . $speak->comments;
    if($comments = Get::comments('ASC', 'post:' . $data['id'], (Guardian::happy() ? 'txt,hold' : 'txt'))) {
        $cc = $comments !== false ? count($comments) : 0;
        $ccc = $cc . ' ' . ($cc === 1 ? $speak->comment : $speak->comments);
        foreach($comments as $comment) {
            $c[] = Get::comment($comment, array(), File::D($data['path']), $FP);
        }
        $results['comments'] = Filter::colon($FP . 'comments', $c, $results);
    }
    $results['total_comments'] = Filter::colon($FP . 'total_comments', $cc, $results);
    $results['total_comments_text'] = Filter::colon($FP . 'total_comments_text', $ccc, $results);
    unset($comments, $c, $cc, $ccc);
    return $results;
}

// Include custom CSS and JS data to the post
function do_custom_field($results, $FP, $data) {
    if( ! isset($data['path'])) return $results;
    if($file = File::exist(CUSTOM . DS . Date::slug($data['time']) . '.' . File::E($data['path']))) {
        $custom = explode(SEPARATOR, File::open($file)->read());
        $css = Converter::DS(trim($custom[0]));
        $js = isset($custom[1]) ? Converter::DS(trim($custom[1])) : "";
        // css_raw
        // post:css_raw
        // custom:css_raw
        // shortcode
        // post:shortcode (already generated by `Page::text()`)
        // custom:shortcode
        // css:shortcode
        // css
        // post:css
        // custom:css
        $css = Filter::colon($FP . 'css_raw', $css, $results);
        $results['css_raw'] = Filter::apply('custom:css_raw', $css, $results);
        $css = Filter::colon('css:shortcode', $css, $results);
        $css = Filter::apply('custom:shortcode', $css, $results);
        $css = Filter::colon($FP . 'css', $css, $results);
        $results['css'] = Filter::apply('custom:css', $css, $results);
        // js_raw
        // post:js_raw
        // custom:js_raw
        // shortcode
        // post:shortcode (already generated by `Page::text()`)
        // custom:shortcode
        // js:shortcode
        // js
        // post:js
        // custom:js
        $js = Filter::colon($FP . 'js_raw', $js, $results);
        $results['js_raw'] = Filter::apply('custom:js_raw', $js, $results);
        $js = Filter::colon('js:shortcode', $js, $results);
        $js = Filter::apply('custom:shortcode', $js, $results);
        $js = Filter::colon($FP . 'js', $js, $results);
        $results['js'] = Filter::apply('custom:js', $js, $results);
    }
    return $results;
}

// Decode the obfuscated `email` value
function do_email_field_decode($email) {
    return strpos($email, ';') !== false ? Text::parse($email, '->unite_entity') : $email;
}

// Set response, comment and user `status` as `pilot`, `passenger` and `intruder`
function do_status_field_alter($status) {
    return Mecha::alter($status, array(
        0 => 'intruder',
        1 => 'pilot',
        2 => 'passenger'
    ));
}

// `comments` field(s) is applicable only to article post type
Filter::add('article:output', 'do_comments_field', 1);

// `css` and `js` field(s) are applicable to all post type
foreach(glob(POST . DS . '*', GLOB_NOSORT | GLOB_ONLYDIR) as $v) {
    Filter::add(File::B($v) . ':output', 'do_custom_field', 1);
}

foreach(array('comment', 'response', 'user') as $v) {
    Filter::add($v . ':email', 'do_email_field_decode', 1);
    Filter::add($v . ':status', 'do_status_field_alter', 1);
}