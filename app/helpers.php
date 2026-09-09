<?php

if (!function_exists('h')) {
    function h($string) {
        if (is_array($string)) {
            $parts = [];
            foreach ($string as $k => $v) {
                if (is_array($v)) {
                    $parts[] = h($v);
                } elseif ($v !== null && $v !== '') {
                    $parts[] = (string)$v;
                }
            }
            return htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
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
        $cleanPath = ltrim($path, '/');
        $fullPath = dirname(__DIR__) . '/public/assets/' . $cleanPath;
        $ver = file_exists($fullPath) ? '?v=' . filemtime($fullPath) : '';
        return url('assets/' . $cleanPath) . $ver;
    }
}
