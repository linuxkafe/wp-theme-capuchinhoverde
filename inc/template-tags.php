<?php
if (!function_exists('cg_posted_on')) {
    function cg_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        $time_string = sprintf($time_string, esc_attr(get_the_date(DATE_W3C)), esc_html(get_the_date()));
        printf('<span class="posted-on">%s</span>', $time_string);
    }
}

if (!function_exists('cg_posted_by')) {
    function cg_posted_by() {
        printf('<span class="byline">%s</span>','<span class="author vcard"><a class="url fn n" href="%1$s">%2$s</a></span>');
    }
}
