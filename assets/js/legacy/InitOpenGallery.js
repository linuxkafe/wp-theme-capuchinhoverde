jQuery(function($) {
    "use strict";

    function InitOpenGallery(){
    // Slider
    $('.gallery-slider').flexslider({
        animation: "slide",
        smoothHeight: true,
        useCSS: false,
        nextText: "",
        prevText: ""
    });
}

InitOpenGallery();

});