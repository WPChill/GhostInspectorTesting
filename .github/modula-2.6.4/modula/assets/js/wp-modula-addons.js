(function( $ ){
    "use strict";

    function modula_install_addon( $url , plugin_path, $length) {
        // Process the Ajax to perform the activation.
        var opts = {
            url:      ajaxurl,
            type:     'post',
            async:    true,
            cache:    false,
            dataType: 'json',
            data: {
                action: 'modula-install-addons',
                nonce:  modulaPRO.install_nonce,
                plugin: $url
            },
            success: function( response ) {
                // If there is a WP Error instance, output it here and quit the script.
                if ( response.error ) {
                    console.log( response.error );
                    return;
                }

                // If we need more credentials, output the form sent back to us.
                if ( response.form ) {
                    // Display the form to gather the users credentials.
                    $( '.modula-addons-error' ).html( response.form );

                    $('.modula-addons-error').on('click', '#upgrade', function(e) {
                        // Prevent the default action, let the user know we are attempting to install again and go with it.
                        e.preventDefault();

                        // Now let's make another Ajax request once the user has submitted their credentials.
                        var hostname  = $(this).parent().parent().find('#hostname').val();
                        var username  = $(this).parent().parent().find('#username').val();
                        var password  = $(this).parent().parent().find('#password').val();
                        $( '.modula-addons-error' ).html('');
                        var cred_opts = {
                            url:      ajaxurl,
                            type:     'post',
                            async:    true,
                            cache:    false,
                            dataType: 'json',
                            data: {
                                action:   'modula-install-addons',
                                nonce:    modulaPRO.install_nonce,
                                plugin:   $url,
                                hostname: hostname,
                                username: username,
                                password: password
                            },
                            success: function(response) {
                                // If there is a WP Error instance, output it here and quit the script.
                                if ( response.error ) {
                                    console.log( response.error );
                                    return;
                                }

                                if ( response.form ) {
                                    $( '.modula-addons-error' ).html( '<div class="notice notice-error"><p>' + modulaPRO.connect_error + '</p></div>' );
                                    return;
                                }

                                // The Ajax request was successful, so let's activate the addon.
                                modula_activate_addon( plugin_path, $length );
                            },
                            error: function(xhr, textStatus ,e) {
                                console.log( xhr );
                                console.log( textStatus );
                                console.log( e );
                                return;
                            }
                        };
                        $.ajax(cred_opts);
                    });

                    // No need to move further if we need to enter our creds.
                    return;
                }

                // The Ajax request was successful, so let's update the output.
                modula_activate_addon( plugin_path, $length );
            },
            error: function( xhr, textStatus ,e ) {
                console.log( xhr );
                console.log( textStatus );
                console.log( e );
                return;
            }
        };

        $.ajax(opts);
    }

    function modula_activate_addon( plugin_path, $length ) {
        var addon        = $( '.modula-toggle__input[data-path="' + plugin_path + '"]' ),
            text_wrapper = addon.parents( '.modula-addon-actions' ).find( 'span.modula-action-texts' ),
            status_bar = $( '.modula-status-bar' );

        text_wrapper.removeClass( 'modula-deactivate-addon' ).addClass( 'modula-activate-addon' );

        jQuery.ajax(
            {
                url     : ajaxurl,
                type    : 'post',
                async   : true,
                cache   : false,
                dataType: 'json',
                data    : {
                    action     : 'modula-activate-addon',
                    nonce      : modulaPRO.install_nonce,
                    plugin_path: plugin_path,
                },
                success : function ( response ) {

                    text_wrapper.text( response.text );
                    addon.data( 'action', 'installed' );
                    addon.parents( '.modula-addon-actions' ).find( 'a.modula-addon-action' ).removeAttr( 'disabled' );
                    addon.attr('checked',true);

                    if ( $length && 1 === $length ) {

                        status_bar.empty().html('<span class="modula-all-addons-installed">'+modulaPRO.installing_mass_addons_complete+'</span>');
                        setTimeout(function(){
                            status_bar.empty();
                        },3000);
                    }

                    setTimeout(function(){
                        text_wrapper.text( '' );
                    },1500);
                },
            } );
    }

    function modula_deactivate_addon( plugin_path, $length ) {

        var addon        = $( '.modula-toggle__input[data-path="' + plugin_path + '"]' ),
            text_wrapper = addon.parents( '.modula-addon-actions' ).find( 'span.modula-action-texts' ),
            status_bar = $( '.modula-status-bar' );

        text_wrapper.removeClass( 'modula-activate-addon' ).addClass( 'modula-deactivate-addon' );

        jQuery.ajax(
            {
                url     : ajaxurl,
                type    : 'post',
                async   : true,
                cache   : false,
                dataType: 'json',
                data    : {
                    action     : 'modula-deactivate-addon',
                    nonce      : modulaPRO.install_nonce,
                    plugin_path: plugin_path,
                },
                success : function ( response ) {

                    text_wrapper.text( response.text );
                    addon.data( 'action', 'activate' );
                    addon.parents( '.modula-addon-actions' ).find( 'a.modula-addon-action' ).attr( 'disabled', 'disabled' );
                    addon.attr('checked',false);

                    if ( $length && 1 === $length ) {
                        status_bar.empty().html('<span class="modula-all-addons-deactivated">'+modulaPRO.deactivating_mass_addons_complete+'</span>');
                        setTimeout(function(){
                            status_bar.empty();
                        },3000);
                    }

                    setTimeout(function(){
                        text_wrapper.text( '' );
                    },1500);
                },
            } );

    }

    $( document ).ready( function () {

        // Re-enable install button if user clicks on it, needs creds but tries to install another addon instead.
        $( '.modula-addons-container .modula-toggle__input' ).on( 'change', function ( e ) {
            var url          = $( this ).data( 'addonurl' ),
                action       = $( this ).data( 'action' ),
                text_wrapper = $( this ).parents( '.modula-addon-actions' ).find( 'span.modula-action-texts' ),
                plugin_path = $( this ).data( 'path' );

            e.preventDefault();

            if ( 'install' == action ) {
                text_wrapper.text( modulaPRO.installing_text );
                modula_install_addon( url, plugin_path, false );
            } else if ( 'activate' == action ) {
                text_wrapper.text( modulaPRO.activating_text );
                modula_activate_addon( plugin_path, false );
            } else if ( 'installed' == action ) {
                text_wrapper.text( modulaPRO.deactivating_text );
                modula_deactivate_addon( plugin_path, false );
            }

        });
    });

    $( '.modula-pro-extensions-actions' ).on( 'click', 'a', function (e) {

        e.preventDefault();

        var element = $( this ),
            select = element.parent().find('select'),
            status_bar = $( '.modula-status-bar' ),
            action  = ('modula-install-all-addons' === select.val()) ? 'modula-install-all-addons' : 'modula-uninstall-all-addons';


        $.ajax( {
            url     : ajaxurl,
            type    : 'post',
            async   : true,
            cache   : false,
            dataType: 'json',
            data    : {
                action     : 'modula-get-all-addons',
                nonce      : modulaPRO.install_nonce,
            },
            success: function ( response ) {

                if ( response ) {

                    let $i = 0,
                        $j = Object.keys(response).length;

                    if ( 'modula-install-all-addons' === action ) {
                        status_bar.empty().html( '<span class="modula-installing-addons">' + modulaPRO.installing_mass_addons + '</span>' );
                    } else {
                        status_bar.empty().html( '<span class="modula-unnstalling-addons">' + modulaPRO.deactivating_mass_addons + '</span>' );
                    }

                    $.each( response, function ( index, value ) {
                        let url            = value.download_link,
                            plugin_path_ai = index + '/' + index + '.php',
                            plugin_status  = $( '.modula-toggle__input[data-path="' + plugin_path_ai + '"]' ).data( 'action' );

                            setTimeout( function () {

                                if ( 'modula-install-all-addons' == action ) {

                                    if ( 'install' == plugin_status ) {

                                        modula_install_addon( url, plugin_path_ai, $j );
                                    } else if ( 'activate' == plugin_status ) {

                                        modula_activate_addon( plugin_path_ai, $j );
                                    }
                                } else {

                                    if ( 'installed' == plugin_status ) {

                                        modula_deactivate_addon( plugin_path_ai, $j );
                                    }
                                }

                                $j--;
                            }, $i + 1200 );

                            $i += 1200;
                    } );
                }
            },
        } );
    } );

})(jQuery);