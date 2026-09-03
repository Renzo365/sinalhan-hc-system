<?php

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/sinalhan-hc-system/public/index.php';
        $basePath = str_replace('/index.php', '', $scriptName);
        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path = '') {
        return url('assets/' . ltrim($path, '/'));
    }
}
