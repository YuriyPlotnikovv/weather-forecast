document.addEventListener('DOMContentLoaded', async function () {
  const sliders = document.querySelectorAll('.slider');

  sliders.forEach((slider) => {
    const container = slider.querySelector('.slider__wrapper');

    const defaultOptions = {
      observer: true,
      resizeObserver: true,
      speed: 1500,
      spaceBetween: 20,
      slidesPerView: 'auto',
      mousewheel: true,
      freeMode: {
        enabled: true,
        momentum: true,
        momentumRatio: 1,
        sticky: true,
      },
      pagination: {
        el: '.slider__pagination',
        type: 'bullets',
        clickable: true,
        dynamicBullets: true,
      },
    };

    if (slider.classList.contains('day-forecast__wrapper')) {
      defaultOptions.direction = window.document.documentElement.clientWidth > 824 ? 'vertical' : 'horizontal';
    }

    const swiper = new Swiper(container, defaultOptions);
  });
});
