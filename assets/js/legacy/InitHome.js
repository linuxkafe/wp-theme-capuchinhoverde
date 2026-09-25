jQuery(function($) {
    "use strict";

    function InitHome(){

    // Hide, While Loading
    $(window).load(function(){
        $('#load-hide').animate({opacity:'1'});
        $('#load-logo').css('display','none');
    });

    // Slider
    $('.slider').flexslider({
        animation: "fade",
        controlNav: false,
        prevText: "",
        nextText: "",
        start: function(){
            $('.slider .box,.slider .shadow').removeClass('slideUp');
            $('.slider li:first .box,.slider li:first .shadow').addClass('slideUp');
        },
        after: function(){
            $('.slider .box,.slider .shadow').removeClass('slideUp');
            $('.slider .flex-active-slide .box,.slider .flex-active-slide .shadow').addClass('slideUp');
        },
        useCSS: false
    });

    if($('.our-services').length){
        var serv = $('.our-services .content .circle .img');
        $(window).scroll(function()
        {
            var offset = serv.offset().top;
            var topOfWindow = $(window).scrollTop();

            if (offset < topOfWindow + 500)
            {
                serv.addClass('fadeIn');
            }
        });
    }

    if($('.our-team').length){
        var team = $('.our-team .content .circle img');
        $(window).scroll(function()
        {
            var offset = team.offset().top;
            var topOfWindow = $(window).scrollTop();

            if (offset < topOfWindow + 500)
            {
                team.addClass('fadeIn');
            }
        });
    }

    // Window Descriptor
    var $window = $(window);

    // Parallax Slider
    $('.slider .slides > li').each(function(){
        var $bgobj = $(this);
        $(window).scroll(function()
        {
            var yPos = -($window.scrollTop() / 10 );
            var coords = '50% '+ yPos + 'px';
            $bgobj.css({ backgroundPosition:  coords });
        });
    });

    // Parallax Section
    if($('section').length){
        $('section').each(function(){
            var $obj = $(this);
            var offset = $obj.offset().top;
            $(window).scroll(function()
            {
                if ($window.scrollTop() > offset - window.innerHeight )
                {
                    var yPos = -(($window.scrollTop() - offset) / 5 );
                    var coords = '50% ' + ( yPos ) + 'px';
                    $obj.css({ backgroundPosition:  coords });
                }
            });
            $(window).resize(function()
            {
                offset = $obj.offset().top;
            });
        });
    }


    // Events Slider
    $('div.events').flexslider({
        animation: "slide",
        prevText: "",
        nextText: "",
        useCSS: false
    });


    // Gallery Filter on Home Page
    //============================

    $(window).load(function(){

        if (jQuery.isFunction(jQuery.fn.isotope)) {
            // cache container
            var $container = $('#galcontainer');
            // initialize isotope
            $container.isotope({
                layoutMode: 'fitRows'
            });

            // filter items when filter link is clicked
            $('#filters a').click(function(){
                var selector = $(this).attr('data-filter');
                $container.isotope({ filter: selector });
                $('#filters a').removeClass('active');
                $(this).addClass('active');
                return false;
            });
            $(window).smartresize(function(){
                $container.isotope({
                    layoutMode: 'fitRows'
                });
            });
        }

    });
}

InitHome();

});