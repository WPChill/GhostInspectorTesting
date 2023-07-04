jQuery(document).ready(function ($) {

    $(document).on('contextmenu dragstart', function () {
        return false;
    });

    /**
     * Monitor which keys are being pressed
     */
    var modula_protection_keys = {
        'alt': false,
        'shift': false,
        'meta': false,
    };

    $(document).on('keydown', function (e) {

        // Alt Key Pressed
        if (e.altKey) {
            modula_protection_keys.alt = true;
        }

        // Shift Key Pressed
        if (e.shiftKey) {
            modula_protection_keys.shift = true;
        }

        // Meta Key Pressed (e.g. Mac Cmd)
        if (e.metaKey) {
            modula_protection_keys.meta = true;
        }

        if (e.ctrlKey && '85' == e.keyCode) {
            modula_protection_keys.ctrl = true;
        }


    });
    $(document).on('keyup', function (e) {

        // Alt Key Released
        if (!e.altKey) {
            modula_protection_keys.alt = false;
        }

        // Shift Key Released
        if (e.shiftKey) {
            modula_protection_keys.shift = false;
        }

        // Meta Key Released (e.g. Mac Cmd)
        if (!e.metaKey) {
            modula_protection_keys.meta = false;
        }

        if (!e.ctrlKey) {
            modula_protection_keys.ctrl = false;
        }

    });

    /**
     * Prevent automatic download when Alt + left click
     */
    $(document).on('click', '#modula_pro_license_key', function (e) {
        if (modula_protection_keys.alt || modula_protection_keys.shift || modula_protection_keys.meta || modula_protection_keys.ctrl) {
            // User is trying to download - stop!
            e.preventDefault();
            return false;
        }
    });

    $(document).on('keydown click',function(e){
        if (modula_protection_keys.ctrl) {
            // User is trying to view source
            e.preventDefault();
            return false;
        }
    });

    $( document ).on( 'click', '#modula_pro_license_activate', function (event) {

        event.preventDefault();

        $( '.modula-status-bar' ).html( '<span class="success">' + modulaLicense.activatingLicense + '</span>' );
        $('#modula_pro_license_key').parent().find('span.license_activation_status').html( '<span class="success">' + modulaLicense.activatingLicense + '</span>' );

        var alt_server = $( '#modula_pro_alernative_server' ).is( ':checked' ),
        license = $('#modula_pro_license_key').val();

        if ( license.length > 0 ) {
            $.ajax( {
                method: 'post',
                url   : modulaLicense.ajaxURL,
                data  : {
                    altServer       : alt_server,
                    license         : license,
                    license_security: modulaLicense.nonce,
                    action          : 'modula_save_license',
                },
                success: function ( response ) {

                    if ( 'undefined' != typeof response.success && !response.success ) {
                        $( '.modula-status-bar' ).html( '<span class="error">' + response.data + '</span>' );
                        $('#modula_pro_license_key').parent().find('span.license_activation_status').html( '<span class="error">' + response.data + '</span>' );
                    } else {
                        $( '.modula-status-bar' ).html( '<span class="success">' + response + '</span>' );
                        $('#modula_pro_license_key').parent().find('span.license_activation_status').html( '<span class="success">' + response + '</span>' );
                        setTimeout( function () {
                            location.reload();
                        }, 1500 );

                    }
                }
            } );
        } else {
            alert( 'Please enter license key' );
        }
    } );

    $( document ).on( 'click', '#modula_pro_license_deactivate', function ( event ) {

        event.preventDefault();
        $( '.modula-status-bar' ).html( '<span class="success">' + modulaLicense.deactivatingLicense + '</span>' );
        $('#modula_pro_license_key').parent().find('span.license_activation_status').html( '<span class="success">' + modulaLicense.deactivatingLicense + '</span>' );

        $.ajax( {
            method : 'post',
            url    : modulaLicense.ajaxURL,
            data   : {
                license_security: modulaLicense.nonce,
                action          : 'modula_deactivate_license',
            },
            success: function ( response ) {
                if ( 'undefined' != typeof response.success && !response.success ) {
                    $( '.modula-status-bar' ).html( '<span class="error">' + response.data + '</span>' );
                    $('#modula_pro_license_key').parent().find('span.license_activation_status').html( '<span class="error">' + response.data + '</span>' );
                } else {
                    $( '.modula-status-bar' ).html( '<span class="success">' + response + '</span>' );
                    $('#modula_pro_license_key').parent().find('span.license_activation_status').html( '<span class="success">' + response + '</span>' );
                    setTimeout( function () {
                        location.reload();
                    }, 1500 );
                }
            }
        } );
    } );
});