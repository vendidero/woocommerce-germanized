/* global shippingZonesLocalizeScript, ajaxurl */
( function( $, data, wp, ajaxurl ) {
    $( function() {
        var $table          = $( '.wc-gzd-legal-checkboxes' ),
            $tbody          = $( '.wc-gzd-legal-checkbox-rows' ),
            $save_button    = $( '.wc-shipping-zone-save' ),
            $row_template   = wp.template( 'wc-gzd-legal-checkbox-row' ),

            // Backbone model
            LegalCheckbox       = Backbone.Model.extend({
                changes: {},
                logChanges: function( changedRows ) {
                    var changes = this.changes || {};

                    _.each( changedRows, function( row, id ) {
                        changes[ id ] = _.extend( changes[ id ] || { id : id }, row );
                    } );

                    this.changes = changes;
                    this.trigger( 'change:checkboxes' );
                },
                discardChanges: function( id ) {
                    var changes      = this.changes || {},
                        set_position = null,
                        checkboxes   = _.indexBy( this.get( 'checkboxes' ), 'id' );

                    // Find current set position if it has moved since last save
                    if ( changes[ id ] && changes[ id ].priority !== undefined ) {
                        set_position = changes[ id ].priority;
                    }

                    // Delete all changes
                    delete changes[ id ];

                    // If the position was set, and this zone does exist in DB, set the position again so the changes are not lost.
                    if ( set_position !== null && checkboxes[ id ] && checkboxes[ id ].priority !== set_position ) {
                        changes[ id ] = _.extend( changes[ id ] || {}, { id : id, priority : set_position } );
                    }

                    this.changes = changes;

                    // No changes? Disable save button.
                    if ( 0 === _.size( this.changes ) ) {
                        legalCheckboxView.clearUnloadConfirmation();
                    }
                },
                save: function() {
                    if ( _.size( this.changes ) ) {
                        $.post( ajaxurl + ( ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=woocommerce_gzd_legal_checkboxes_save_changes', {
                            wc_gzd_legal_checkbox_nonce : data.checkboxes_nonce,
                            changes                     : this.changes
                        }, this.onSaveResponse, 'json' );
                    } else {
                        legalCheckbox.trigger( 'saved:checkboxes' );
                    }
                },
                onSaveResponse: function( response, textStatus ) {
                    if ( 'success' === textStatus ) {
                        if ( response.success ) {
                            legalCheckbox.set( 'checkboxes', response.data.checkboxes );
                            legalCheckbox.trigger( 'change:checkboxes' );
                            legalCheckbox.changes = {};
                            legalCheckbox.trigger( 'saved:checkboxes' );
                        } else {
                            window.alert( data.strings.save_failed );
                        }
                    }
                }
            } ),

            // Backbone view
            LegalCheckboxView = Backbone.View.extend({
                rowTemplate: $row_template,
                initialize: function() {
                    this.listenTo( this.model, 'change:checkboxes', this.setUnloadConfirmation );
                    this.listenTo( this.model, 'saved:checkboxes', this.clearUnloadConfirmation );
                    this.listenTo( this.model, 'saved:checkboxes', this.render );
                    $tbody.on( 'change', { view: this }, this.updateModelOnChange );
                    $tbody.on( 'sortupdate', { view: this }, this.updateModelOnSort );
                    $( window ).on( 'beforeunload', { view: this }, this.unloadConfirmation );
                    $( document.body ).on( 'click', '.wc-gzd-legal-checkbox-add', { view: this }, this.onAddNewRow );
                },
                block: function() {
                    $( this.el ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                unblock: function() {
                    $( this.el ).unblock();
                },
                render: function() {
                    var checkboxes = _.indexBy( this.model.get( 'checkboxes' ), 'id' ),
                        view       = this;

                    view.$el.empty();
                    view.unblock();

                    if ( _.size( checkboxes ) ) {
                        // Sort zones
                        checkboxes = _( checkboxes )
                            .chain()
                            .sortBy( function ( checkbox ) { return parseInt( checkbox.priority, 10 ); } )
                            .value();

                        // Populate $tbody with the current zones
                        $.each( checkboxes, function( id, rowData ) {
                            view.renderRow( rowData );
                        } );
                    }

                    view.initRows();
                },
                renderRow: function( rowData ) {
                    var view = this;
                    view.$el.append( view.rowTemplate( rowData ) );
                    view.initRow( rowData );
                },
                initRow: function( rowData ) {
                    var view = this;
                    var $tr = view.$el.find( 'tr[data-id="' + rowData.id + '"]');

                    // List shipping methods
                    view.renderLocations( rowData.id, rowData.location_titles );
                    view.renderStatus( rowData.id, rowData.is_enabled === 'yes', 'enabled' );
                    view.renderStatus( rowData.id, rowData.is_mandatory === 'yes', 'mandatory' );
                    view.disableDelete( rowData.id, rowData.is_core === 'yes' );

                    $tr.find( '.wc-gzd-legal-checkbox-delete' ).on( 'click', { view: this }, this.onDeleteRow );
                },
                initRows: function() {
                    // Tooltips
                    $( '#tiptip_holder' ).removeAttr( 'style' );
                    $( '#tiptip_arrow' ).removeAttr( 'style' );
                    $( '.tips' ).tipTip({ 'attribute': 'data-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50 });
                },
                disableDelete: function( id, is_core ) {
                    var $tr            = $( '.wc-gzd-legal-checkboxes tr[data-id="' + id + '"]');

                    if ( is_core ) {
                        $tr.find( '.wc-gzd-legal-checkbox-delete' ).remove();
                        $tr.find( '.row-actions .sep' ).remove();
                    }
                },
                renderStatus: function( id, status, column ) {
                    var $tr            = $( '.wc-gzd-legal-checkboxes tr[data-id="' + id + '"]');
                    var $td            = $tr.find('td.wc-gzd-legal-checkbox-' + column);

                    class_name = 'enabled';

                    if ( ! status ) {
                        class_name = 'disabled';
                    }

                    $td.empty();
                    $td.html( '<span class="status-' + class_name + '"></span>' );
                },
                renderLocations: function( id, locations ) {
                    var $tr            = $( '.wc-gzd-legal-checkboxes tr[data-id="' + id + '"]');
                    var $location_list = $tr.find('td.wc-gzd-legal-checkbox-locations ul');

                    $location_list.find( '.wc-gzd-legal-checkbox-location' ).remove();

                    if ( _.size( locations ) ) {
                        _.each( locations, function( title, key ) {
                            $location_list.append( '<li class="wc-gzd-legal-checkbox-location" data-location="' + key + '">' + title + '</li>' );
                        } );
                    }
                },
                onDeleteRow: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        checkboxes = _.indexBy( model.get( 'checkboxes' ), 'id' ),
                        changes    = {},
                        row        = $( this ).closest('tr'),
                        id         = row.data('id');

                    event.preventDefault();

                    if ( window.confirm( data.strings.delete_confirmation_msg ) ) {
                        if ( checkboxes[ id ] ) {
                            delete checkboxes[ id ];
                            changes[ id ] = _.extend( changes[ id ] || {}, { deleted : 'deleted' } );
                            model.set( 'checkboxes', checkboxes );
                            model.logChanges( changes );
                            event.data.view.block();
                            event.data.view.model.save();
                        }
                    }
                },
                setUnloadConfirmation: function() {
                    this.needsUnloadConfirm = true;
                    $save_button.prop( 'disabled', false );
                },
                clearUnloadConfirmation: function() {
                    this.needsUnloadConfirm = false;
                    $save_button.prop( 'disabled', true );
                },
                unloadConfirmation: function( event ) {
                    if ( event.data.view.needsUnloadConfirm ) {
                        event.returnValue = data.strings.unload_confirmation_msg;
                        window.event.returnValue = data.strings.unload_confirmation_msg;
                        return data.strings.unload_confirmation_msg;
                    }
                },
                updateModelOnChange: function( event ) {
                    var model      = event.data.view.model,
                        $target    = $( event.target ),
                        id         = $target.closest( 'tr' ).data( 'id' ),
                        attribute  = $target.data( 'attribute' ),
                        value      = $target.val(),
                        checkboxes = _.indexBy( model.get( 'checkboxes' ), 'id' ),
                        changes = {};

                    if ( ! checkboxes[ id ] || checkboxes[ id ][ attribute ] !== value ) {
                        checkboxes[ id ] = {};
                        checkboxes[ id ][ attribute ] = value;
                    }

                    model.logChanges( changes );
                },
                updateModelOnSort: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        checkboxes = _.indexBy( model.get( 'checkboxes' ), 'id' ),
                        rows       = $( 'tbody.wc-gzd-legal-checkbox-rows tr' ),
                        changes    = {};

                    // Update sorted row position
                    _.each( rows, function( row ) {
                        var id = $( row ).data( 'id' ),
                            old_position = null,
                            new_position = parseInt( $( row ).index(), 10 );

                        if ( checkboxes[ id ] ) {
                            old_position = parseInt( checkboxes[ id ].priority, 10 );
                        }

                        if ( old_position !== new_position ) {
                            changes[ id ] = _.extend( changes[ id ] || {}, { priority : new_position } );
                        }
                    } );

                    if ( _.size( changes ) ) {
                        model.logChanges( changes );
                        event.data.view.block();
                        event.data.view.model.save();
                    }
                }
            } ),
            legalCheckbox = new LegalCheckbox({
                checkboxes: data.checkboxes
            } ),
            legalCheckboxView = new LegalCheckboxView({
                model:    legalCheckbox,
                el:       $tbody
            } );

        legalCheckboxView.render();

        $tbody.sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            handle: 'td.wc-gzd-legal-checkbox-sort',
            scrollSensitivity: 40
        });
    });
})( jQuery, wc_gzd_legal_checkboxes_params, wp, ajaxurl );
