/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.trusted_shops = window.trusted_shops || {};

( function( $, wp, trusted_shops ) {

    /**
     * Order Data Panel
     */
    trusted_shops.admin = {

        params: {},
		optionPrefix: '',

        init: function() {
            this.params       = trusted_shops_params;
            this.optionPrefix = this.params.option_prefix;

            $( document )
                .on( 'click', '#wc-gzd-trusted-shops-export', this.onClickExport )
                .on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_integration_mode', this.onChangeIntegrationMode )
                .on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_review_reminder_checkbox', this.onChangeReviewReminder )
                .on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_review_reminder_enable', this.onChangeReviewReminderEnable )
                .on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_enable_reviews', this.onChangeEnableReviews )
                .on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_product_sticker_enable', this.onProductSticketEnable )
                .on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_product_widget_enable', this.onProductWidgetEnable );

            $( document ).find( '#woocommerce_' + this.optionPrefix + 'trusted_shops_integration_mode' ).trigger( 'change' );
            $( document ).find( '#woocommerce_' + this.optionPrefix + 'trusted_shops_enable_reviews' ).trigger( 'change' );
            $( document ).find( '#woocommerce_' + this.optionPrefix + 'trusted_shops_review_reminder_enable' ).trigger( 'change' );

            $( document ).on( 'submit', '#mainform', this.onSaveForm );
        },

        getSettingsWrapper: function() {
            var self   = trusted_shops.admin;
            var prefix = self.optionPrefix.replace( '_', '-' );

            return $( '.wc-' + prefix + 'admin-settings' );
        },

        addNotice: function( type, texts ) {
            var self = trusted_shops.admin;

            self.getSettingsWrapper().find( '#message' ).remove();
            self.getSettingsWrapper().prepend( '<div id="message" class="notice ' + type + ' inline"><p>' + texts.join( '<br/>' ) + '</p></div>' );

            $( 'html, body' ).animate( {
                scrollTop: ( self.getSettingsWrapper().offset().top - 100 )
            }, 1000 );
        },

        onSaveForm: function() {
            var self     = trusted_shops.admin;
            var doSubmit = true;
            var errorMsg = [];
            var mandatory = {};

            $( 'textarea, input, select' ).removeClass( 'has-error' );

            if ( self.isExpertMode() ) {
                mandatory = self.params.expert_mode_mandatory;
            } else {
                mandatory = self.params.standard_mode_mandatory;
            }

            $.each( mandatory, function( id, errorMessage ) {
                if ( $( '#' + id ).val() === '' ) {
                    $( '#' + id ).addClass( 'has-error' );
                    doSubmit = false;
                    errorMsg.push( errorMessage );
                }
            });

            var offset = parseFloat( $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_trustbadge_y' ).val() );

            if ( offset < 0 ) {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_trustbadge_y' ).addClass( 'has-error' );
                errorMsg.push( self.params.i18n_error_y_offset );
                doSubmit = false;
            }

            if ( ! doSubmit && errorMsg.length > 0 ) {
                self.addNotice( 'error', errorMsg );
            }

            return doSubmit;
        },

        isExpertMode: function() {
            var self = trusted_shops.admin;
            return $( '#woocommerce_' + this.optionPrefix + 'trusted_shops_integration_mode' ).val() === 'expert';
        },

        onClickExport: function() {
        	var self = trusted_shops.admin;
            var href_org = $( this ).data( 'href-org' );

            $( this ).attr( 'href', href_org + '&interval=' + $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_collector' ).val() + '&days=' + $( '#woocommerce_' + self.params.option_prefix + 'trusted_shops_review_collector_days_to_send' ).val() );
		},

        onChangeIntegrationMode: function() {
        	var self = trusted_shops.admin;

        	// Hide gateway options
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_gateway_bacs' ).parents( 'table.form-table' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_gateway_bacs' ).parents( 'table' ).prev( 'h3,h2' ).hide();

            if ( $( this ).val() === 'expert' ) {

                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_gateway_bacs' ).parents( 'table.form-table' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_gateway_bacs' ).parents( 'table' ).prev( 'h3,h2' ).show();

                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_trustbadge_code' ).parents( 'tr' ).show();
                // Show notice
                $( '.wc-gzd-trusted-shops-expert-mode-note' ).appendTo( $( this ).parents( 'td' ) ).show();
            } else {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_trustbadge_code' ).parents( 'tr' ).hide();
                $( '.wc-gzd-trusted-shops-expert-mode-note' ).hide();
            }

            $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_enable_reviews' ).trigger( 'change' );
		},

		onChangeReviewReminder: function() {
            var self = trusted_shops.admin;

            // Hide options
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_mandatory' ).parents( 'tr' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_text' ).parents( 'tr' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_opt_out' ).parents( 'tr' ).hide();

            if ( $( this ).is( ':checked' ) ) {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_mandatory' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_text' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_opt_out' ).parents( 'tr' ).show();
            }
        },

        onChangeReviewReminderEnable: function() {
            var self = trusted_shops.admin;

            // Hide options
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_days' ).parents( 'tr' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox' ).parents( 'tr' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_mandatory' ).parents( 'tr' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_text' ).parents( 'tr' ).hide();
            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_opt_out' ).parents( 'tr' ).hide();

            if ( $( this ).is( ':checked' ) ) {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_days' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_mandatory' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox_text' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_opt_out' ).parents( 'tr' ).show();
            }

            $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_reminder_checkbox' ).trigger( 'change' );
        },

        onChangeEnableReviews: function() {
            var self = trusted_shops.admin;

            console.log( $(this) );
            console.log( $( this ).is( ':checked' ) );

            if ( $( this ).is( ':checked' ) ) {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_gtin_attribute' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_brand_attribute' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_mpn_attribute' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).show();
            } else {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_gtin_attribute' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_brand_attribute' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_mpn_attribute' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).removeAttr( 'checked' );
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).removeAttr( 'checked' );
            }

            $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).trigger( 'change' );
            $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).trigger( 'change' );
        },

        onProductSticketEnable: function() {
            var self = trusted_shops.admin;

            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_code' ).parents( 'tr' ).hide();

            if ( $( this ).is( ':checked' ) ) {

                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_border_color' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_star_color' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_star_size' ).parents( 'tr' ).show();

                if ( $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_integration_mode' ).val() === 'expert' ) {
                    $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_code' ).parents( 'tr' ).show();
                }

            } else {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_border_color' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_star_color' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_star_size' ).parents( 'tr' ).hide();
            }
        },

        onProductWidgetEnable: function() {
            var self = trusted_shops.admin;

            $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_code' ).parents( 'tr' ).hide();

            if ( $( this ).is( ':checked' ) ) {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_star_color' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_star_size' ).parents( 'tr' ).show();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_font_size' ).parents( 'tr' ).show();

                if ( $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_integration_mode' ).val() === 'expert' ) {
                    $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_code' ).parents( 'tr' ).show();
                }

            } else {
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_star_color' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_star_size' ).parents( 'tr' ).hide();
                $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_font_size' ).parents( 'tr' ).hide();
            }
        }
    };

    $( document ).ready( function() {
        trusted_shops.admin.init();
    });

})( jQuery, wp, window.trusted_shops );