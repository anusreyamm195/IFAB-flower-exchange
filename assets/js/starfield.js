(function () {
    'use strict';
    var root = document.documentElement;
    var ticking = false;
    var isLoginPage = document.body && document.body.classList.contains('login-theme');
    if (isLoginPage) {
        return;
    }
    var flowerLayer = document.createElement('div');
    flowerLayer.className = 'falling-flower-layer';
    flowerLayer.setAttribute('aria-hidden', 'true');
    var flowers = ['✿', '❀', '✾', '✽', '❁'];
    for (var i = 0; i < 24; i += 1) {
        var flower = document.createElement('span');
        flower.className = 'falling-flower';
        flower.textContent = flowers[i % flowers.length];
        flower.style.left = (Math.random() * 100) + '%';
        flower.style.fontSize = (14 + Math.random() * 18) + 'px';
        flower.style.animationDelay = (-Math.random() * 18) + 's';
        flower.style.animationDuration = (12 + Math.random() * 13) + 's';
        flower.style.setProperty('--flower-drift', ((Math.random() * 160) - 80) + 'px');
        flowerLayer.appendChild(flower);
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(flowerLayer);
    });
    function updateStars() {
        root.style.setProperty('--star-scroll', String(window.scrollY || window.pageYOffset || 0));
        ticking = false;
    }
    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(updateStars);
            ticking = true;
        }
    }, { passive: true });
    updateStars();
})();
