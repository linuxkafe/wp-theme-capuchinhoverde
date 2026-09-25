jQuery(function($) {
    "use strict";

    function InitStoryOpen(){
        // Slider
        $('.story-slider').flexslider({
            animation: "slide",
            useCSS: false,
            directionNav: false
        });
    }
    InitStoryOpen();
});