jQuery(document).ready(function () {

    jQuery('#send-newsletter').click(function () {
        jQuery(this).parent().find('span.ajax-loader').show();
        jQuery('form.standard-form .text').attr('readonly', 'readonly');
        jQuery('form.standard-form input#send-newsletter').attr('disabled', 'disabled');
        jQuery('form.standard-form').submit();
    });

    jQuery('div.newsletter-container div.newsletter-footer span.more').click(function () {

        var obj = this;
        var body = jQuery('div.newsletter-container div.newsletter-body');
        var footer = jQuery('div.newsletter-container div.newsletter-footer');
        var loader = jQuery(footer).find('span.ajax-loader');
        var show = 0;

        jQuery(loader).show();

        if (jQuery(footer).data('show')) show = jQuery(footer).data('show');

        jQuery.post(ajaxurl, {
            action: 'buddypress_group_newsletter_more',
            'show': show,
            'cookie': encodeURIComponent(document.cookie),
            '_wpnonce_more': jq("input#group-newsletter-more").val()
        },
		function (response) {
		    jQuery(loader).hide();

		    /* Check for errors and append if found. */
		    if (response['state'] == '1') {
		        jQuery(footer).data('show', response['show']);
		        jQuery(body).append(response['html']);

		        if (response['show_max'] >= response['max']) {
		            jQuery(footer).hide();
		        }
		    } else {
		        jQuery(footer).hide();
		    }
		}, 'json');

    });
});