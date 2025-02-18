<?php
global $MESS;
?>

</main>

<footer class="page__footer footer">
    <div class="footer__wrapper">
        <a class="footer__info link" href="https://yuriyplotnikovv.ru/" target="_blank"
           title="<?= $MESS['FOOTER_AUTHOR'] ?>">
            <p class="footer__info-text"><?= $MESS['FOOTER_AUTHOR_TEXT'] ?></p>

            <svg class="footer__info-icon" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg">
                <use xlink:href="/public/img/sprite.svg#icon-developer"/>
            </svg>
        </a>

        <a class="footer__info link" href="https://basmilius.github.io/weather-icons/" target="_blank"
           title="<?= $MESS['FOOTER_ICONS_TEXT'] ?>">
            <p class="footer__info-text"><?= $MESS['FOOTER_ICONS_TEXT'] ?></p>
        </a>
    </div>
</footer>

<script src="/public/js/vendor/swiper-bundle.js"></script>
<script src="<?= Tools::addTimestampToFile('/public/js/script.min.js') ?>"></script>
</body>
</html>
