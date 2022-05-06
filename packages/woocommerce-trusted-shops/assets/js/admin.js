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
            var self          = this;

            $( document ).on( 'click', 'a.woocommerce-ts-input-toggle-trigger', this.onInputToogleClick );

            // Show hide elements
            $( document ).on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_integration_mode', this.onChangeIntegrationMode );
            $( document ).on( 'change', ':input[id$=_enable]', this.onChangeEnable );
            $( document ).on( 'change', '#woocommerce_' + this.optionPrefix + 'trusted_shops_reviews_enable', this.onChangeEnableReviews );

            // Initial triggers
            $( document ).find( '#woocommerce_' + this.optionPrefix + 'trusted_shops_integration_mode' ).trigger( 'change' );
            $( document ).find( ':input[id$=_enable]' ).trigger( 'change' );

            // Exporter
            $( document ).on( 'click', '#wc-gzd-trusted-shops-export', this.onClickExport );

            // Sidebar Switch
            $( document ).on( 'click', 'table.form-table tr', this.onSidebarChange );

            $( ":data(sidebar)" ).each( function() {
                $( this ).parents( 'tr' ).on( 'click', self.onSidebarChange );
            });

            $( document ).on( 'click', 'h2, div[id$="options-description"]', this.onSidebarTitelChange );

            // Form validation
            $( document ).on( 'submit', '#mainform', this.onSaveForm );
        },

        onInputToogleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-ts-input-toggle' ),
                $row      = $toggle.parents( 'tr' ),
                $checkbox = $row.find( 'input[type=checkbox]' ),
                $enabled  = $toggle.hasClass( 'woocommerce-input-toggle--enabled' );

            $toggle.removeClass( 'woocommerce-input-toggle--enabled' );
            $toggle.removeClass( 'woocommerce-input-toggle--disabled' );

            if ( $enabled ) {
                $checkbox.prop( 'checked', false );
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            } else {
                $checkbox.prop( 'checked', true );
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            }

            $checkbox.trigger( 'change' );

            return false;
        },

        onChangeEnableReviews: function() {
            var self = trusted_shops.admin;

            if ( $( this ).is( ':checked' ) ) {
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).show();
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).show();
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_brand_attribute' ).parents( 'tr' ).show();
            } else {
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).prop( 'checked', false );
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).prop( 'checked', false );

                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).parents( 'tr' ).hide();
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).parents( 'tr' ).hide();
                $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_brand_attribute' ).parents( 'tr' ).hide();
            }

            $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_enable' ).trigger( 'change' );
            $( document ).find( '#woocommerce_' + self.optionPrefix + 'trusted_shops_product_widget_enable' ).trigger( 'change' );
        },

        onChangeIntegrationMode: function() {
            var self = trusted_shops.admin;

            $( document ).find( ':input[id$=_enable]' ).trigger( 'change' );
        },

        onChangeEnable: function() {
            self      = trusted_shops.admin;
            self.showHideGroupElements( $( this ) );
        },

        showHideGroupElements: function( $parent ) {
            var id        = $parent.attr( 'id' ),
                self      = trusted_shops.admin,
                postfix   = id.replace( 'woocommerce_' + self.optionPrefix + 'trusted_shops_', '' ),
                group     = postfix.substr( 0, postfix.length - 7 ),
                // Support inputs and HTML fields
                $elements = $( ':input[id^=woocommerce_' + self.optionPrefix + 'trusted_shops_' + group + '_], th[id^=woocommerce_' + self.optionPrefix + 'trusted_shops_' + group + '_]' ),
                show      = false;

            var exclude_hide_experts = [
                'woocommerce_' + self.optionPrefix + 'trusted_shops_rich_snippets_category',
                'woocommerce_' + self.optionPrefix + 'trusted_shops_rich_snippets_product',
                'woocommerce_' + self.optionPrefix + 'trusted_shops_rich_snippets_home',
                'woocommerce_' + self.optionPrefix + 'trusted_shops_product_sticker_tab_text'
            ];

            if ( $parent.is( ':checked' ) ) {
                show = true;
            }

            $elements.each( function() {
                var elementId   = $( this ).attr( 'id' );
                var showElement = show;

                if ( 'woocommerce_' + self.optionPrefix + 'trusted_shops_' + group + '_enable' === elementId ) {
                    return;
                }

                // Code blocks
                if ( 'woocommerce_' + self.optionPrefix + 'trusted_shops_' + group + '_code' === elementId || 'woocommerce_' + self.optionPrefix + 'trusted_shops_' + group + '_selector' === elementId ) {
                    if ( ! self.isExpertMode() && showElement ) {
                        showElement = false;
                    }
                } else if( self.isExpertMode() ) {
                    // Check if parent has code block
                    var $parent = $( this ).parents( 'table.form-table' );

                    // Check if element is excluded from being hidden
                    if ( $parent.find( ':input[id$=_code]' ).length > 0 ) {
                        if ( $.inArray( elementId, exclude_hide_experts ) == -1 ) {
                            showElement = false;
                        }
                    }
                }

                if ( showElement ) {
                    $( this ).parents( 'tr' ).show();
                } else {
                    $( this ).parents( 'tr' ).hide();
                }
            });
        },

        onSidebarTitelChange: function() {
            var $next = $( this ).nextAll( 'table.form-table:first' );
            $next.find( 'tr:first' ).trigger( 'click' );
        },

        onSidebarChange: function() {
            var $sidebar_elem    = $( this ).find( '[data-sidebar]' ),
                $table           = $( this ).parents( '.form-table' ),
                $current_sidebar = $( '.wc-ts-sidebar-active' ),
                $sidebar         = $current_sidebar;

            if ( $sidebar_elem.length <= 0 ) {
                if ( $table.find( '[data-sidebar]' ).length > 0 ) {
                    $sidebar_elem = $table.find( '[data-sidebar]:first' );
                }
            }

            if ( $sidebar_elem.length <= 0 ) {
                $sidebar = $( '#wc-ts-sidebar-default' );
            } else {
                $sidebar = $( '#' + $sidebar_elem.data( 'sidebar' ) );
            }

            $current_sidebar.removeClass( 'wc-ts-sidebar-active' );
            $sidebar.addClass( 'wc-ts-sidebar-active' );
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

        validate: function( $elem ) {
            var self     = trusted_shops.admin,
                isValid  = true,
                id       = $elem.attr( 'id' ),
                isCode   = id.substr( id.length - 5 ) === '_code',
                value    = $elem.val();

            if ( $elem.data( 'validate' ) ) {
                var type = $elem.data( 'validate' );

                if ( 'integer' === type ) {
                    value = parseInt( value );

                    if ( isNaN( value ) ) {
                        isValid = false;
                    }
                }
            } else if( self.isExpertMode() && isCode ) {
                if ( '' === value ) {
                    isValid = false;
                }
            }

            return isValid;
        },

        onSaveForm: function() {
            var self     = trusted_shops.admin;
            var doSubmit = true;

            $( 'textarea, input, select' ).removeClass( 'wc-ts-has-error' );

            $( 'textarea:visible, input:visible, select:visible' ).each( function() {

                var id      = $( this ).attr( 'id' ),
                    isCode  = id.substr( id.length - 5 ) === '_code',
                    $td     = $( this ).parents( 'tr' ).find( 'td' );

                $td.find( '.wc-ts-error' ).remove();

                if ( ! self.validate( $( this ) ) ) {
                    $( this ).addClass( 'wc-ts-has-error' );

                    if ( isCode ) {
                        var message = self.params.i18n_error_mandatory;
                    } else {
                        var message = $( this ).data( 'validate-msg' );
                    }

                    $td.append( '<span class="wc-ts-error">' + message + '</span>' );

                    doSubmit = false;
                }
            });

            if ( ! doSubmit ) {
                $( 'html, body' ).animate( {
                    scrollTop: ( self.getSettingsWrapper().find( '.wc-ts-has-error:first' ).offset().top - 100 )
                }, 1000 );
            }

            return doSubmit;
        },

        isExpertMode: function() {
            var self = trusted_shops.admin;
            return $( '#woocommerce_' + this.optionPrefix + 'trusted_shops_integration_mode' ).val() === 'expert';
        },

        onClickExport: function() {
            var self     = trusted_shops.admin;
            var href_org = $( this ).data( 'href-org' );

            $( this ).attr( 'href', href_org + '&interval=' + $( '#woocommerce_' + self.optionPrefix + 'trusted_shops_review_collector' ).val() + '&days=' + $( '#woocommerce_' + self.params.option_prefix + 'trusted_shops_review_collector_days_to_send' ).val() );
        }
    };

    $( document ).ready( function() {
        trusted_shops.admin.init();
    });

})( jQuery, wp, window.trusted_shops );