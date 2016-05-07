<?php

/**
 * =============================================================
 *  SUCH MECHA . VERY ARRAY . MANY FUNCTION(S)
 * =============================================================
 *
 * -- CODE: ----------------------------------------------------
 *
 *    Mecha::eat($array)->shake()->vomit();
 *
 * -------------------------------------------------------------
 *
 */

class Mecha extends __ {

    protected $stomach = array();
    protected $i = 0;

    public function __construct($array) {
        $this->stomach = $array;
        $this->i = 0;
        return $this;
    }

    // Prevent `$e` exceeds the value of `$min` and `$max`
    public static function edge($e, $min = 0, $max = 9999) {
        if($e < $min) $e = $min;
        if($e > $max) $e = $max;
        return $e;
    }

    // Handle missing array variable(s)
    public static function extend(&$default, $alternate) {
        $default = array_replace_recursive($default, $alternate);
        return $default;
    }

    // Convert array to object
    public static function O($a) {
        return is_array($a) ? (object) array_map('self::O', $a) : $a;
    }

    // Convert object to array
    public static function A($o) {
        return is_object($o) ? array_map('self::A', (array) $o) : $o;
    }

    // Set array value recursively
    public static function SVR(&$array, $segments, $value = "") {
        $segments = explode('.', $segments);
        while(count($segments) > 1) {
            $segment = array_shift($segments);
            if( ! array_key_exists($segment, $array)) {
                $array[$segment] = array();
            }
            $array =& $array[$segment];
        }
        $array[array_shift($segments)] = $value;
    }

    // Get array value recursively
    public static function GVR(&$array, $segments = null, $fallback = false) {
        if(is_null($segments)) {
            return $array;
        }
        foreach(explode('.', $segments) as $segment) {
            if( ! is_array($array) || ! array_key_exists($segment, $array)) {
                return $fallback;
            }
            $array =& $array[$segment];
        }
        return $array;
    }

    // Unset array value recursively
    public static function UVR(&$array, $segments) {
        $segments = explode('.', $segments);
        while(count($segments) > 1) {
            $segment = array_shift($segments);
            if(array_key_exists($segment, $array)) {
                $array =& $array[$segment];
            }
        }
        if(is_array($array) && array_key_exists($segment = array_shift($segments), $array)) {
            unset($array[$segment]);
        }
    }

    // Initialize with eating
    public static function eat($array) {
        return new Mecha($array);
    }

    // Walk through the array
    public static function walk($array, $fn = null) {
        if(is_callable($fn)) {
            foreach($array as $k => &$v) {
                $v = is_array($v) ? array_merge($v, self::walk($v, $fn)) : call_user_func($fn, $v, $k);
            }
            unset($v);
            return $array;
        }
        return self::eat($array);
    }

    // Check if `$array` contain `$key` -- should be faster than `in_array($key, $array)`
    public function has($key, $x = "\x1A") {
        $s = is_array($this->stomach) ? implode($x, $this->stomach) : (string) $this->stomach;
        return strpos($x . $s . $x, $x . $key . $x) !== false;
    }

    // Sort array based on its value's key
    public function order($order = 'ASC', $key = null, $preserve_key = false, $default = "\x1A") {
        if( ! is_null($key)) {
            $before = array();
            $after = array();
            if( ! empty($this->stomach)) {
                foreach($this->stomach as $k => $v) {
                    $v = (array) $v;
                    if(array_key_exists($key, $v)) {
                        $before[$k] = $v[$key];
                    } else if($default !== "\x1A") {
                        $before[$k] = $default;
                        $this->stomach[$k][$key] = $default;
                    }
                }
                if($order === 'DESC') {
                    arsort($before);
                } else {
                    asort($before);
                }
                foreach($before as $k => $v) {
                    $after[$k] = $this->stomach[$k];
                }
            }
            $this->stomach = $after;
            unset($before, $after);
        } else {
            $this->stomach = (array) $this->stomach;
            if($order === 'DESC') {
                arsort($this->stomach);
            } else {
                asort($this->stomach);
            }
        }
        if( ! $preserve_key) {
            $this->stomach = array_values($this->stomach);
        }
        return $this;
    }

    // Array shake
    public function shake() {
        shuffle($this->stomach);
        return $this;
    }

    // Vomit! BLARGH!
    public function vomit($param = null, $fallback = false) {
        return self::GVR($this->stomach, $param, $fallback);
    }

    // Move to next array index
    public function next($skip = 0) {
        $this->i = self::edge($this->i + 1 + $skip, 0, $this->count() - 1);
        return $this;
    }

    // Move to previous array index
    public function prev($skip = 0) {
        $this->i = self::edge($this->i - 1 - $skip, 0, $this->count() - 1);
        return $this;
    }

    // Move to `$index` array index
    public function to($index) {
        $this->i = is_int($index) ? $index : self::index($index, $index);
        return $this;
    }

    // Insert `$food` before current array index
    public function before($food, $key = null) {
        if(is_null($key)) $key = $this->i;
        $this->stomach = array_slice($this->stomach, 0, $this->i, true) + array($key => $food) + array_slice($this->stomach, $this->i, null, true);
        $this->i = self::edge($this->i - 1, 0, $this->count() - 1);
        return $this;
    }

    // Insert `$food` after current array index
    public function after($food, $key = null) {
        if(is_null($key)) $key = $this->i + 1;
        $this->stomach = array_slice($this->stomach, 0, $this->i + 1, true) + array($key => $food) + array_slice($this->stomach, $this->i + 1, null, true);
        $this->i = self::edge($this->i + 1, 0, $this->count() - 1);
        return $this;
    }

    // Replace current array index value with `$food`
    public function replace($food) {
        $i = 0;
        foreach($this->stomach as $k => $v) {
            if($i === $this->i) {
                $this->stomach[$k] = $food;
                break;
            }
            $i++;
        }
        return $this;
    }

    // Append `$food` to array
    public function append($food, $key = null) {
        $this->i = $this->count() - 1;
        return $this->after($food, $key);
    }

    // Prepend `$food` to array
    public function prepend($food, $key = null) {
        $this->i = 0;
        return $this->before($food, $key);
    }

    // Get first array value
    public function first() {
        $this->i = 0;
        return reset($this->stomach);
    }

    // Get last array value
    public function last() {
        $this->i = $this->count() - 1;
        return end($this->stomach);
    }

    // Get current array index
    public function current() {
        return $this->i;
    }

    // Get selected array value
    public function get($index = null, $fallback = false) {
        if( ! is_null($index)) {
            if(is_int($index)) {
                $index = $this->key($index, $index);
            }
            return array_key_exists($index, $this->stomach) ? $this->stomach[$index] : $fallback;
        }
        $i = 0;
        foreach($this->stomach as $k => $v) {
            if($i === $this->i) {
                return $this->stomach[$k];
            }
            $i++;
        }
    }

    // Get array length
    public function count() {
        return count($this->stomach);
    }

    // Get array key by position
    public function key($index, $fallback = false) {
        $array = array_keys($this->stomach);
        return isset($array[$index]) ? $array[$index] : $fallback;
    }

    // Get position by array key
    public function index($key, $fallback = false) {
        $key = array_search($key, array_keys($this->stomach));
        return $key !== false ? $key : $fallback;
    }

    // Generate chunk(s) of array
    public function chunk($index = null, $count = 25) {
        if( ! is_array($this->stomach)) return $this;
        $results = array();
        // 0-based index with `vomit($index)`
        // `Mecha::eat($foo)->chunk(25)->vomit(1);`
        if(func_num_args() === 1) {
            $count = $index;
            $index = null;
        }
        $chunk = array_chunk($this->stomach, $count, true);
        // 1-based index with `chunk($index)`
        // `Mecha::eat($foo)->chunk(2, 25)->vomit();`
        if( ! is_null($index)) {
            $chunk = isset($chunk[$index - 1]) ? $chunk[$index - 1] : false;
            $this->stomach = $chunk ? array_values($chunk) : array();
        // `Mecha::eat($foo)->chunk(null, 25)->vomit()`
        } else {
            $this->stomach = $chunk;
        }
        return $this;
    }

    // Shortcut for string-based `switch` and `case`
    public static function alter($case, $cases, $default = null) {
        if(is_null($default)) $default = $case;
        return array_key_exists($case, $cases) ? $cases[$case] : $default;
    }

}