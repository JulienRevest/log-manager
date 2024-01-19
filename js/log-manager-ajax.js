jQuery(document).ready(function($) {
    var full_clear = {
        action: 'log_manager_clear_archives'
    };

    // since 2.8, ajaxurl is always defined in the admin header and points to admin-ajax.php
    jQuery(".log-manager-clear-archives").on("click", function(event) {
        event.preventDefault();
        jQuery.post(ajaxurl, full_clear, function(response) {
            alert(response);
            location.reload();
        });
    });

    
    var single_clear = {
        action: 'log_manager_clear_single_archives',
        toClear: '',
    };
    jQuery(".dashicons-trash").on("click", function(event) {
        event.preventDefault();
        var button = jQuery( this )
        single_clear.toClear = button.data('path');
        jQuery.post(ajaxurl, single_clear, function(response) {
            alert(response);
            button.closest('tr').remove();
        });
    });
});