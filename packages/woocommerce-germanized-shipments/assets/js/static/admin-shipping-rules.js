/* global wc_gzd_shipments_admin_shipping_rules_params, ajaxurl */
( function( $, data, wp, ajaxurl ) {
    $( function() {
        var $tbody            = $( '.wc-gzd-shipments-shipping-rules-rows' ),
            $table            = $( '.wc-gzd-shipments-shipping-rules' ),
            $row_template     = wp.template( 'wc-gzd-shipments-shipping-rules-row' ),
            $condition_row_template = wp.template( 'wc-gzd-shipments-shipping-rules-condition-row' ),
            $packaging_info   = wp.template( 'wc-gzd-shipments-shipping-rules-packaging-info' ),
            shippingRuleViews = {},

            // Backbone model
            ShippingRule = Backbone.Model.extend({
                updateRules: function( rules, packaging ) {
                    if ( 0 === Object.keys( rules ).length ) {
                        rules = {};
                    }

                    var currentRules = { ...this.get( 'rules' ) };
                    currentRules[ String( packaging ) ] = rules;

                    this.set( 'rules', currentRules );
                },
                updateConditions: function( conditions, packaging, ruleId ) {
                    if ( 0 === Object.keys( conditions ).length ) {
                        conditions = {};
                    }

                    const theRuleId = 'rule_' === String( ruleId ).substring( 0, 5 ) ? ruleId : 'rule_' + String( ruleId );
                    const rule = { ...this.getRule( packaging, ruleId ) };
                    var currentRules = { ...this.get( 'rules' ) };

                    rule['conditions'] = conditions;
                    currentRules[ String( packaging ) ][ theRuleId ] = rule;

                    this.set( 'rules', currentRules );
                },
                getRulesByPackaging: function( packaging ) {
                    var rules = { ...this.get( 'rules' ) };

                    return rules.hasOwnProperty( String( packaging ) ) ? rules[ String( packaging ) ] : {};
                },
                getRule: function( packaging, ruleId ) {
                    const rules = this.getRulesByPackaging( packaging );
                    const theRuleId = 'rule_' === String( ruleId ).substring( 0, 5 ) ? ruleId : 'rule_' + String( ruleId );

                    return rules.hasOwnProperty( theRuleId ) ? rules[ theRuleId ] : {};
                },
                getConditionsByRuleId: function( packaging, ruleId ) {
                    const rule = this.getRule( packaging, ruleId );

                    return rule.hasOwnProperty( 'conditions' ) ? rule['conditions'] : {};
                }
            } ),

            // Backbone view
            ShippingRuleView = Backbone.View.extend({
                rowTemplate: $row_template,
                conditionViews: {},
                packaging: '',

                initialize: function() {
                    this.packaging = String( $( this.el ).data( 'packaging' ) );
                    this.conditionViews = {};

                    $( this.el ).on( 'change', { view: this }, this.updateModelOnChange );
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
                getRules: function() {
                    return this.model.getRulesByPackaging( this.packaging );
                },
                render: function() {
                    var rules = this.getRules(),
                        view  = this;

                    this.$el.empty();
                    this.unblock();

                    if ( _.size( rules ) ) {
                        // Populate $tbody with the current classes
                        $.each( rules, function( id, rowData ) {
                            view.renderRow( rowData );
                        } );
                    }
                },
                renderRow: function( rowData ) {
                    var view = this;
                    view.$el.append( view.rowTemplate( rowData ) );

                    var $tr = view.$el.find( 'tr[data-id="' + rowData.rule_id + '"]');

                    var condition = new ConditionRowView({
                        model: view.model,
                        el: $tr.find( '.wc-gzd-shipments-shipping-rules-condition-rows' )[0]
                    } );

                    condition.ruleId     = rowData.rule_id;
                    condition.packaging  = view.packaging;
                    condition.parentView = view;

                    condition.render();

                    view.initRow( rowData );
                },
                initFields: function( $tr, rowData ) {
                    $( document.body ).trigger( 'wc-enhanced-select-init' );

                    // Support select2 select boxes
                    $tr.find( 'select' ).each( function() {
                        var attribute = $( this ).data( 'attribute' );

                        if ( ! rowData[ attribute ] ) {
                            return;
                        }

                        var selected  = Array.isArray( rowData[ attribute ] ) ? rowData[ attribute ] : Array( String( rowData[ attribute ] ) ),
                            $select = $( this );

                        $.each( selected, function( i, sel ) {
                            $isSelected = $select.find( 'option[value="' + String( sel ) + '"]' );

                            if ( $isSelected.length > 0 ) {
                                $isSelected.prop( 'selected', true );
                            }
                        } );

                        if ( $select.hasClass( 'enhanced' ) ) {
                            $select.trigger( 'change' );
                        }
                    } );

                    $tr.find( 'input.decimal' ).on( 'change', { view: this }, this.onChangeDecimal );
                    $tr.find( 'input.wc_input_price' ).on( 'change', { view: this }, this.onChangePrice );
                    $tr.find( 'input.decimal, input.wc_input_price' ).trigger( 'change' );
                },
                initRow: function( rowData ) {
                    var view = this,
                        $tr = view.$el.find( 'tr[data-id="' + rowData.rule_id + '"]'),
                        $tbody = $tr.parents( 'tbody' );

                    view.initFields( $tr, rowData );

                    $tr.find( '.shipping-rule-remove' ).on( 'click', { view: this }, this.onDeleteRow );
                    $tr.find( '.shipping-rule-add' ).on( 'click', { view: this }, this.onAddRow );

                    if ( $tbody.find( 'tr.wc-gzd-shipments-shipping-rules-packaging-info' ).length <= 0 ) {
                        $tbody.prepend( $packaging_info );
                        var $packagingTr = $tbody.find( 'tr.wc-gzd-shipments-shipping-rules-packaging-info' );

                        $packagingTr.find( '.packaging-title' ).html( $tbody.data( 'title' ) );

                        if ( $tbody.data( 'help-tip' ) ) {
                            $packagingTr.find( '.woocommerce-help-tip' ).attr( 'data-tip', $tbody.data( 'help-tip' ) );
                        } else {
                            $packagingTr.find( '.woocommerce-help-tip' ).remove();
                        }

                        if ( $tbody.data( 'edit-url' ) ) {
                            $packagingTr.find( '.packaging-title' ).attr( 'href', $tbody.data( 'edit-url' ) );
                        }
                    }

                    $( document.body ).trigger( 'init_tooltips' );
                },
                onChangeDecimal: function( event ) {
                    var $tr     = $( this ).closest( 'tr' ),
                        $input  = $( this );

                    $input.val( $input.val().replace( '.', data.decimal_separator ) );
                },
                onChangePrice: function( event ) {
                    var $tr     = $( this ).closest( 'tr' ),
                        $input  = $( this );

                    $input.val( $input.val().replace( '.', data.price_decimal_separator ) );
                },
                onDeleteRow: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        rules      = view.getRules(),
                        rule_id    = $( this ).closest( 'tr' ).data( 'id' ),
                        rule_index = "rule_" + rule_id;

                    if ( rules[ rule_index ] ) {
                        delete rules[ rule_index ];

                        model.updateRules( rules, view.packaging );
                    }

                    view.render();

                    if ( view.$el.find( 'tr.shipping-rule:last' ).length > 0 ) {
                        view.$el.find( 'tr.shipping-rule:last' ).addClass( 'current' );
                    }

                    return false;
                },
                onAddRow: function( event ) {
                    var view         = event.data.view,
                        model        = view.model,
                        rules        = view.getRules(),
                        rule_id      = $( this ).closest( 'tr' ).data( 'id' ),
                        rule_index   = "rule_" + rule_id,
                        current_rule = rules[ rule_index ],
                        newRow       = _.extend( {}, current_rule, {
                            rule_id: 'new-' + _.size( rules ) + '-' + Date.now(),
                            newRow : true,
                            conditions: {},
                            costs: '',
                        } );

                    var index = 0;

                    $.each( current_rule['conditions'], function( conditionId, conditionData ) {
                        var newConditionId = 'new-c-' + Date.now() + index;

                        newRow['conditions']['condition_' + newConditionId] = _.extend( {}, conditionData, {
                            condition_id: newConditionId,
                            rule_id: newRow.rule_id,
                            newRow : true
                        } );

                        index++;
                    } );

                    rules[ 'rule_' + newRow.rule_id ] = newRow;
                    model.updateRules( rules, view.packaging );

                    view.renderRow( newRow );
                    view.$el.find( 'tr[data-id="' + newRow.rule_id + '"]').trigger( 'focus' );

                    return false;
                },
                updateModelOnChange: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        $target    = $( event.target ),
                        rule_id    = $target.closest( 'tr' ).data( 'id' ),
                        rule_index = "rule_" + rule_id,
                        attribute  = $target.data( 'attribute' ),
                        value      = $target.val(),
                        rules      = view.getRules();

                    if ( $target.parents( '.wc-gzd-shipments-shipping-rules-condition-rows' ).length > 0 ) {
                        return false;
                    }

                    if ( ! $target.is( ':visible' ) ) {
                        return false;
                    }

                    if ( $target.is( ':checkbox' ) ) {
                        value = $target.is( ':checked' ) ? value : '';
                    } else if ( value && $target.hasClass( 'decimal' ) ) {
                        value = parseFloat( value.replace( data.decimal_separator , '.' ) );
                    } else if ( value && $target.hasClass( 'wc_input_price' ) ) {
                        value = parseFloat( value.replace( data.price_decimal_separator, '.' ) );
                    }

                    if ( ! rules[ rule_index ] || String( rules[ rule_index ][ attribute ] ) !== String( value ) ) {
                        rules[ rule_index ][ attribute ] = value;

                        if ( String( rules[ rule_index ]['packaging'] ) !== String( view.packaging ) ) {
                            var newPackaging = rules[ rule_index ]['packaging'],
                                newView = shippingRuleViews[ newPackaging ],
                                newRules = newView.getRules();

                            newRules[ rule_index ] = { ...rules[ rule_index ] };
                            delete rules[ rule_index ];

                            model.updateRules( rules, view.packaging );
                            newView.model.updateRules( newRules, newPackaging );

                            view.render();
                            newView.render();
                        } else {
                            model.updateRules( rules, view.packaging );
                        }
                    }
                }
            }
        ),

        shippingRule = new ShippingRule({
            rules: data.rules
        } ),

        // Backbone view
        ConditionRowView = ShippingRuleView.extend({
            rowTemplate: $condition_row_template,
            ruleId    : 0,
            packaging: '',
            parentView: false,

            initialize: function() {
                $( this.el ).on( 'change', { view: this }, this.updateModelOnChange );
            },
            getRules: function() {
                return this.model.getConditionsByRuleId( this.packaging, this.ruleId );
            },
            render: function() {
                var rules = this.getRules(),
                    view  = this;

                this.$el.empty();

                if ( _.size( rules ) ) {
                    // Populate $tbody with the current classes
                    $.each( rules, function( id, rowData ) {
                        view.renderRow( rowData );
                    } );
                }
            },
            renderRow: function( rowData ) {
                var view = this;
                view.$el.append( view.rowTemplate( rowData ) );

                view.initRow( rowData );
            },
            initRow: function( rowData ) {
                var view = this,
                    $tr = view.$el.find( 'tr[data-condition="' + rowData.condition_id + '"]'),
                    $tbody = $tr.parents( 'tbody' );

                view.initFields( $tr, rowData );

                if ( 0 === $tr.index() ) {
                    $tr.find( '.condition-remove' ).hide();
                }

                // Make the rows function
                $tr.find( '.shipping-rules-type-container' ).hide();
                $tr.find( '.conditions-column:not(.conditions-when,.conditions-actions)' ).hide();

                $tr.find( '.shipping-rules-condition-type' ).on( 'change', { view: this }, this.onChangeRuleType );
                $tr.find( '.shipping-rules-condition-type' ).trigger( 'change' );

                $tr.find( '.condition-remove' ).on( 'click', { view: this }, this.onDeleteRow );
                $tr.find( '.condition-add' ).on( 'click', { view: this }, this.onAddRow );

                $( document.body ).trigger( 'init_tooltips' );
            },
            onChangeRuleType: function( event ) {
                var $tr     = $( this ).closest( 'tr' ),
                    rule    = $( this ).val(),
                    view    = event.data.view;

                $tr.find( '.shipping-rules-condition-type-container' ).hide();
                $tr.find( '.conditions-column:not(.conditions-when,.conditions-actions)' ).hide();
                $tr.find( '.shipping-rules-condition-type-container-' + rule ).show();

                if ( $tr.find( '.shipping-rules-condition-type-container-' + rule + '.shipping-rule-condition-type-operator :input' ).length > 0 ) {
                    $tr.find( '.shipping-rules-condition-type-container-' + rule + '.shipping-rule-condition-type-operator :input' ).trigger( 'change' );
                } else {
                    view.updateModel( $tr.data( 'condition' ), 'operator', '' );
                }

                $tr.find( '.shipping-rules-condition-type-container-' + rule + '.shipping-rule-condition-type-operator :input' ).trigger( 'change' );
                $tr.find( '.shipping-rules-condition-type-container-' + rule ).parents( '.conditions-column' ).show();
            },
            updateModel: function( conditionId, attribute, value ) {
                var model      = this.model,
                    conditionIndex = "condition_" + conditionId,
                    conditions      = this.getRules();

                if ( ! conditions[ conditionIndex ] || String( conditions[ conditionIndex ][ attribute ] ) !== String( value ) ) {
                    conditions[ conditionIndex ][ attribute ] = value;

                    model.updateConditions( conditions, this.packaging, this.ruleId );
                }
            },
            updateModelOnChange: function( event ) {
                var view       = event.data.view,
                    $target    = $( event.target ),
                    conditionId    = $target.closest( 'tr' ).data( 'condition' ),
                    attribute  = $target.data( 'attribute' ),
                    value      = $target.val();

                if ( ! $target.is( ':visible' ) ) {
                    return false;
                }

                if ( $target.is( ':checkbox' ) ) {
                    value = $target.is( ':checked' ) ? value : '';
                } else if ( value && $target.hasClass( 'decimal' ) ) {
                    value = parseFloat( value.replace( data.decimal_separator , '.' ) );
                } else if ( value && $target.hasClass( 'wc_input_price' ) ) {
                    value = parseFloat( value.replace( data.price_decimal_separator, '.' ) );
                }

                view.updateModel( conditionId, attribute, value );
            },
            onDeleteRow: function( event ) {
                var view       = event.data.view,
                    model      = view.model,
                    conditions  = view.getRules(),
                    $tr         = $( this ).closest( 'tr' ),
                    condition_id = $tr.data( 'condition' ),
                    condition_index = "condition_" + condition_id;

                if ( conditions[ condition_index ] && 0 !== $tr.index() ) {
                    delete conditions[ condition_index ];

                    model.updateConditions( conditions, view.packaging, view.ruleId );
                }

                view.render();

                return false;
            },
            onAddRow: function( event ) {
                var view         = event.data.view,
                    model        = view.model,
                    conditions   = view.getRules(),
                    conditionId      = $( this ).closest( 'tr' ).data( 'condition' ),
                    conditionIndex   = "condition_" + conditionId,
                    currentCondition = conditions[ conditionIndex ],

                    newCondition       = _.extend( {}, currentCondition, {
                        condition_id: 'new-c-' + _.size( conditions ) + '-' + Date.now(),
                        newRow : true,
                        costs: '',
                    } );

                if ( -1 !== $.inArray( newCondition.type, ['weight', 'total'] ) ) {
                    newCondition[newCondition.type + '_from'] = newCondition[newCondition.type + '_to'];
                    newCondition[newCondition.type + '_to'] = '';
                }

                conditions[ 'condition_' + newCondition.condition_id ] = newCondition;
                model.updateConditions( conditions, view.packaging, view.ruleId );

                view.renderRow( newCondition );

                return false;
            }
        } );

        $tbody.each( function() {
            var view = new ShippingRuleView({
                model: shippingRule,
                el: $( this )
            } );

            view.render();

            shippingRuleViews[ $( this ).data( 'packaging' ) ] = view;

            // Sorting
            $( this ).sortable({
                items: 'tr',
                cursor: 'move',
                axis: 'y',
                handle: 'td.sort',
                scrollSensitivity: 40,
                helper: function ( event, ui ) {
                    ui.children().each( function () {
                        $( this ).width( $( this ).width() );
                    } );
                    ui.css( 'left', '0' );
                    return ui;
                },
                start: function ( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop: function ( event, ui ) {
                    ui.item.removeAttr( 'style' );
                    ui.item.trigger( 'updateMoveButtons' );
                    ui.item.trigger( 'focus' );
                },
            } );
        } );

        $( document ).on( 'change', '.wc-gzd-shipments-shipping-rules-cb-all', function() {
            var $table = $( this ).parents( 'table' );

            if ( $( this ).is( ':checked' ) ) {
                $table.find( 'input.cb' ).prop( 'checked', true );
            } else {
                $table.find( 'input.cb' ).prop( 'checked', false );
            }
        } );

        $( document ).on( 'click', '.wc-gzd-shipments-shipping-rule-add', function() {
            var packagingId = $( '.new-shipping-packaging' ).val(),
                view = shippingRuleViews[ packagingId ],
                rules = view.model.getRulesByPackaging( packagingId ),
                newRow  = _.extend( {}, data.default_shipping_rule, {
                    rule_id: 'new-' + _.size( rules ) + '-' + Date.now(),
                    packaging: packagingId,
                    newRow : true
                } ),
                conditionId = 'new-c-' + Date.now(),
                defaultCondition = newRow['conditions'][0];

                newRow['conditions'] = {};
                newRow['conditions']['condition_' + conditionId] = _.extend( {}, defaultCondition, {
                    condition_id: conditionId,
                    rule_id: newRow.rule_id,
                    newRow : true
                } );

            rules[ 'rule_' + newRow.rule_id ] = newRow;
            view.model.updateRules( rules, packagingId );

            shippingRuleViews[ packagingId ].renderRow( newRow );
            shippingRuleViews[ packagingId ].$el.find( 'tr[data-id="' + newRow.rule_id + '"]').trigger( 'focus' );

            return false;
        } );

        $( document ).on( 'click', '.wc-gzd-shipments-shipping-rule-remove', function() {
            var rules = shippingRule.get( 'rules' ),
                $button = $( this ),
                $table = $button.parents( 'table' ),
                packagingIds = [];

            $table.find( 'input.cb:checked' ).each( function() {
                var rule_id = $( this ).val(),
                    rule_index = 'rule_' + rule_id,
                    $tr = $( this ).parents( 'tr' ),
                    packagingId = $tr.find( '.shipping-packaging' ).val();

                if ( ! packagingIds.includes( packagingId ) ) {
                    packagingIds.push( packagingId );
                }

                delete rules[ packagingId ][ rule_index ];
            });

            shippingRule.set( 'rules', rules );

            packagingIds.forEach( function ( packagingId, index) {
                shippingRuleViews[ packagingId ].render();
            });

            $button.addClass( 'disabled' );
            $table.find( '.wc-gzd-shipments-shipping-rules-cb-all' ).prop( 'checked', false );

            return false;
        } );

        $( document ).on( 'keydown', function( e ) {
            var $selectedRow   = $table.find( 'tr.current' ),
                $selectedTable = $( '.wc-gzd-shipments-shipping-rules.has-focus' );

            if ( $selectedRow.length === 0 && $selectedTable.length === 0 ) {
                return;
            }

            var command_or_ctrl = e.metaKey || e.ctrlKey; // command/ctrl

            if ( command_or_ctrl && ( 'd' === e.key || '-' === e.key ) ) {
                $selected = $table.find( 'input.cb:checked' );

                if ( $selected.length > 0 ) {
                    $table.find( '.wc-gzd-shipments-shipping-rule-remove' ).trigger( 'click' );
                } else if ( $selectedRow ) {
                    $selectedRow.find( '.shipping-rule-remove' ).trigger( 'click' );
                }

                e.preventDefault();
                e.stopPropagation();

                return false;
            } else if ( $selectedRow && command_or_ctrl && '+' === e.key ) {
                $selectedRow.find( '.shipping-rule-add' ).trigger( 'click' );

                e.preventDefault();
                e.stopPropagation();

                return false;
            } else if ( $selectedTable && command_or_ctrl && 'a' === e.key ) {
                $selectedTable.find( '.wc-gzd-shipments-shipping-rules-cb-all' ).prop( 'checked', ! $selectedTable.find( '.wc-gzd-shipments-shipping-rules-cb-all' ).is( ':checked' ) );
                $selectedTable.find( '.wc-gzd-shipments-shipping-rules-cb-all' ).trigger( 'change' );

                e.preventDefault();
                e.stopPropagation();

                return false;
            }
        } );

        $( document ).on( 'change', '.wc-gzd-shipments-shipping-rules-rows input.cb, .wc-gzd-shipments-shipping-rules-cb-all', function() {
             $selected = $( this ).parents( 'table' ).find( 'input.cb:checked' );

             if ( $selected.length > 0 ) {
                 $table.find( '.wc-gzd-shipments-shipping-rule-remove' ).removeClass( 'disabled' );
             } else {
                 $table.find( '.wc-gzd-shipments-shipping-rule-remove' ).addClass( 'disabled' );
             }
        } );

        // Remove current focus
        $( document ).on( 'mouseup', function( e ) {
            var container = $( 'table.wc-gzd-shipments-shipping-rules' );

            // if the target of the click isn't the container nor a descendant of the container
            if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
                container.removeClass( 'has-focus' );
                container.find( 'tr' ).removeClass( 'current' );
            } else {
                container.addClass( 'has-focus' );
            }
        } );

        // Focus on inputs within the table if clicked instead of trying to sort.
        $( document ).on( 'click', '.wc-gzd-shipments-shipping-rules tbody, .wc-gzd-shipments-shipping-rules input', function () {
            $( this ).trigger( 'focus' );
        } );

        $( document ).on( 'focus click', '.wc-gzd-shipments-shipping-rules input, .wc-gzd-shipments-shipping-rules tr', function (e) {
            $( this ).parents( 'table' ).find( 'tr' ).removeClass( 'current' );
            $( this ).closest( 'tr.shipping-rule' ).addClass( 'current' );
        } );
    });
})( jQuery, wc_gzd_shipments_admin_shipping_rules_params, wp, ajaxurl );
