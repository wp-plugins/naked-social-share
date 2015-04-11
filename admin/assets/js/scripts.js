(function ($) {

    // Add Color Picker to all inputs that have 'ng-color-field' class
    $(function () {
        $('.ng-color-field').wpColorPicker();
    });

    // Show/hide fields based on requirements.
    $('.ng-field-switch label').click(function(e) {

        var parent = $(this).parents('.ng-field-switch');

        // We want to enable this feature.
        if ($(this).hasClass('ng-enable')) {
            // Remove 'selected' class from the disabled toggle.
            $('.ng-disable', parent).removeClass('selected');
            // Add 'selected' class to the current element.
            $(this).addClass('selected');
            // Check the checkbox on.
            $('input[type="checkbox"]', parent).attr('checked', true);
            // Show the associated field groups.
            $('.requires-' + $(this).data('id')).fadeIn(300).removeClass('ng-hide');
        }

        // We want to disable this feature.
        if ($(this).hasClass('ng-disable')) {
            // Remove 'selected' class from the enabled toggle.
            $('.ng-enable', parent).removeClass('selected');
            // Add 'selected' class to the current element.
            $(this).addClass('selected');
            // Uncheck the checkbox.
            $('input[type="checkbox"]', parent).attr('checked', false);
            // Hide the associated field groups.
            $('.requires-' + $(this).data('id')).fadeOut(300).addClass('ng-hide');
        }

    });

})(jQuery);