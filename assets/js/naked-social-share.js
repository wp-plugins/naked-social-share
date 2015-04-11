/**
 * Created by Ashley on 04/04/2015.
 */

jQuery.noConflict();

jQuery(document).ready(function ($) {

    $('.naked-social-share a').click(function (e) {
        e.preventDefault();
        var link = $(this).attr('href');
        var left = (screen.width / 2) - (550 / 2);
        var top = (screen.height / 2) - (400 / 2);
        window.open(link, '_blank', 'height=400, width=550, status=yes, toolbar=no, menubar=no, location=no, top=' + top + ', left=' + left);
        return false;
    });

});