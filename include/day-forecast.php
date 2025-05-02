<?php
global $MESS;
?>

<section class="main__day-forecast section day-forecast loading">
    <div class="section__wrapper day-forecast__wrapper slider">
        <h2 class="section__title day-forecast__title"><?= $MESS['DAY_TITLE'] ?></h2>

        <div class="day-forecast__slider slider__wrapper swiper-container">
            <ul class="day-forecast__list swiper-wrapper"></ul>

            <div class="day-forecast__pagination slider__pagination"></div>
        </div>
    </div>
</section>

