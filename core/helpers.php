<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* FLASH SET */
function flash_set($key, $value)
{
    $_SESSION['flash'][$key] = $value;
}

/* FLASH GET */
function flash_get($key)
{
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

/* Escape HTML */
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
