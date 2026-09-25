jQuery(function($) {
    "use strict";

    function InitDefault(){
    // Window Descriptor
    var $window = $(window);

    // Parallax Top
    if($('.header-back').length){
        var $obj = $('.header-back');
        var offset = $obj.offset().top;
        $(window).scroll(function()
        {
            if ($window.scrollTop() > offset - window.innerHeight )
            {
                var yPos = -(($window.scrollTop()) / 5 );
                var coords = '50% ' + ( yPos ) + 'px';
                $obj.css({ backgroundPosition:  coords });
            }
        });
    }

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
}

InitDefault();

});