<?php
global $LANG, $MESS;
?>

<!DOCTYPE html>
<html class="page" lang="<?= $LANG ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title><?= $MESS['PAGE_TITLE'] ?></title>
    <meta name="description" content="<?= $MESS['PAGE_DESCRIPTION'] ?>">
    <meta name="keywords" content="<?= $MESS['PAGE_KEYWORDS'] ?>">

    <link rel="preload" href="/public/fonts/ProximaNova-Bold.woff2" as="font"
          type="font/woff2" crossorigin="anonymous">
    <link rel="preload" href="/public/fonts/ProximaNova-Light.woff2" as="font"
          type="font/woff2" crossorigin="anonymous">
    <link rel="preload" href="/public/fonts/ProximaNova-LightIt.woff2" as="font"
          type="font/woff2" crossorigin="anonymous">
    <link rel="preload" href="/public/fonts/ProximaNovaCond-Black.woff2" as="font"
          type="font/woff2" crossorigin="anonymous">

    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="application-name" content="<?= $MESS['PAGE_TITLE'] ?>>">

    <link href="/public/css/vendor/swiper-bundle.css" rel="stylesheet">
    <link href="<?= Tools::addTimestampToFile('/public/css/style.min.css') ?>" rel="stylesheet">
</head>

<body class="page__body">

<header class="page__header header">
    <div class="header__wrapper">
        <div class="header__logo">
            <svg class="header__logo-image" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <use xlink:href="/public/img/sprite.svg#logo"/>
            </svg>

            <p class="header__logo-text"><?= $MESS['PAGE_TITLE'] ?></p>
        </div>
    </div>
</header>

<main class="page__main main">
    <h1 class="visually-hidden"><?= $MESS['MAIN_TITLE'] ?></h1>
