jQuery(function($) {
    "use strict";

    function InitOpenMenu(){
        // Slider
        $('.menu-slider').flexslider({
            animation: "slide",
            directionNav: false,
            useCSS: false
        });
    }

    InitOpenMenu();
});