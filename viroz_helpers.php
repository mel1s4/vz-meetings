<?php 

if (!function_exists('print_x')) {
  function print_x($x) {
    echo '<pre>';
    print_r($x);
    echo '</pre>';
  }
}

if (!function_exists('vz_html')) {
  function vz_html($tag, $text) {
    $txt = __($text, 'vz-am');
    echo "<$tag>$txt</$tag>";
  }
}

if (!function_exists('e_vz'))  {
  function e_vz($text) {
    echo __vz($text);
  }
}

if (!function_exists('__vz')) {
  function __vz($text) {
    return __($text, 'vz-am');
  }
}