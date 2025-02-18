<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/core/init.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/include/header.php";
?>

<?php Tools::includeFile('current-weather'); ?>
<?php Tools::includeFile('day-forecast'); ?>
<?php Tools::includeFile('next-forecast'); ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/include/footer.php"; ?>
