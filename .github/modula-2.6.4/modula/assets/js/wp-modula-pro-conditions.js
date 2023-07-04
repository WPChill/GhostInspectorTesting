wp.Modula = 'undefined' === typeof( wp.Modula ) ? {} : wp.Modula;

var modulaProGalleryConditions = Backbone.Model.extend({

    initialize: function( args ){

		var rows = jQuery('.modula-settings-container tr[data-container]');
		var tabs = jQuery('.modula-tabs .modula-tab');
		this.set( 'rows', rows );
		this.set( 'tabs', tabs );

		this.initEvents();
		this.initValues();

    },

    initEvents: function(){

        this.listenTo(wp.Modula.Settings, 'change:cursor', this.changeCustomCursor );
        this.listenTo(wp.Modula.Settings, 'change:uploadCursor', this.changeUploadCursor );
        this.listenTo(wp.Modula.Settings, 'change:lightbox', this.changedLightbox );
        this.listenTo(wp.Modula.Settings, 'change:lightbox_toolbar', this.changedToolbar );
		this.listenTo(wp.Modula.Settings, 'change:dropdownFilters', this.changedDropdownFilters);
	    this.listenTo(wp.Modula.Settings, 'change:enableMobileDropdownFilters', this.changedMobileDropdownFilters);
	    this.listenTo(wp.Modula.Settings, 'change:enableCollapsibleFilters', this.changedCollapsileFilters);
	    this.listenTo(wp.Modula.Settings, 'change:hide_title', this.changedTitle );
	    this.listenTo(wp.Modula.Settings, 'change:hide_description', this.changedCaption );
	    this.listenTo(wp.Modula.Settings, 'change:lightbox_animationEffect', this.changedAnimation );
	    this.listenTo(wp.Modula.Settings, 'change:lightbox_transitionEffect', this.changedTransition );
	    this.listenTo(wp.Modula.Settings, 'change:showCaptionLightbox', this.changedLightboxCaption );
	    this.listenTo(wp.Modula.Settings, 'change:showTitleLightbox', this.changedLightboxTitle );
	    this.listenTo(wp.Modula.Settings, 'change:lightbox_share', this.changedLightboxShare );
		this.listenTo(wp.Modula.Settings, 'change:lightbox_email', this.changedLightboxEmail );
	    this.listenTo(wp.Modula.Settings, 'change:show_gallery_title', this.showGalleryTitle );
	    this.listenTo(wp.Modula.Settings, 'change:hideAllFilter', this.showAllFilter );
	    this.listenTo(wp.Modula.Settings, 'change:enableCollapsibleFilters', this.showCollapsibleFilters );

		this.listenTo(wp.Modula.Settings, 'toggleAccordeon:lightbox_toolbar', this.toggleLightboxShare);
		this.listenTo(wp.Modula.Settings, 'toggleAccordeon:lightbox_toolbar', this.toggleLightboxEmail);
		this.listenTo(wp.Modula.Settings, 'toggleAccordeon:lightbox_share', this.toggleLightboxEmail);
     },

     initValues: function(){
         this.changeCustomCursor( false, wp.Modula.Settings.get( 'cursor' ) );
         this.changeUploadCursor( false, wp.Modula.Settings.get( 'uploadCursor') );
         this.changedLightbox(false,wp.Modula.Settings.get( 'lightbox' ));
         this.changedToolbar(false,wp.Modula.Settings.get( 'lightbox_toolbar' ));
		 this.changedDropdownFilters(false,wp.Modula.Settings.get( 'dropdownFilters' ));
	     this.changedMobileDropdownFilters(false,wp.Modula.Settings.get( 'enableMobileDropdownFilters' ));
	     this.changedCollapsileFilters(false,wp.Modula.Settings.get( 'enableCollapsibleFilters' ));
	     this.changedAnimation(false,wp.Modula.Settings.get( 'lightbox_animationEffect' ));
	     this.changedTransition(false,wp.Modula.Settings.get( 'lightbox_transitionEffect' ));
	     this.changedLightboxTitle(false,wp.Modula.Settings.get( 'showTitleLightbox' ));
	     this.changedLightboxCaption(false,wp.Modula.Settings.get( 'showCaptionLightbox' ));
	     this.showGalleryTitle(false,wp.Modula.Settings.get( 'show_gallery_title' ));
	     this.showAllFilter(false,wp.Modula.Settings.get( 'hideAllFilter' ));
	     this.showCollapsibleFilters(false,wp.Modula.Settings.get( 'enableCollapsibleFilters' ));
     },

     changeCustomCursor: function( settings, value) {
        var cursorBox = jQuery( '.modula-effects-preview > div' );
        var rows = this.get( 'rows' );
        if ( 'custom' != value ) {
            rows.filter( '[data-container="uploadCursor"]' ).setting_state( this, 'off');
            cursorBox.css('cursor', wp.Modula.Settings.get( 'cursor' ) );
        }else {
            rows.filter( '[data-container="uploadCursor"]' ).setting_state( this, 'on');
            var imageSource;
            if(jQuery("#modula_cursor_preview")[0]) {
                imageSource = jQuery("#modula_cursor_preview")[0].src;

            cursorBox.css('cursor', 'url(' + imageSource + '), auto' );
            }
        }

     },

     changeUploadCursor: function( settings, value ) {
         cursorBox = jQuery( '.modula-effects-preview > div' );
         customCursorValue =  wp.Modula.Settings.get( 'cursor' );
         if ( 0 != value  && 'custom' == customCursorValue ) {
            var imageSource = jQuery("#modula_cursor_preview")[0].src;
            cursorBox.css('cursor', 'url(' + imageSource + '), auto' );
         }else {
             cursorBox.css( 'cursor', wp.Modula.Settings.get( 'cursor' ) );
         }
     },

    changedLightbox: function( settings, value ){
        var rows = this.get( 'rows' ),
            tabs = this.get( 'tabs' ),
            link_options = ['no-link', 'direct', 'attachment-page'];

        if ( 'fancybox' == value ) {

            rows.filter( '[data-container="loop_lightbox"],[data-container="lightbox_keyboard"],[data-container="lightbox_wheel"],[data-container="doubleClick"],[data-container="show_navigation"],[data-container="lightbox_clickSlide"],[data-container="lightbox_animationEffect"],[data-container="lightbox_animationDuration"],[data-container="lightbox_transitionEffect"],[data-container="lightbox_transitionDuration"],[data-container="lightbox_toolbar"],[data-container="lightbox_infobar"],[data-container="lightbox_dblclickSlide"],[data-container="lightbox_touch"],[data-container="lightbox_thumbsAutoStart"],[data-container="lightbox_thumbsAxis"],[data-container="lightbox_bottomThumbs"],[data-container="showAllOnLightbox"],[data-container="lightbox_background_color"],[data-container="showTitleLightbox"],[data-container="showCaptionLightbox"],[data-container="captionPosition"],[data-container="caption_overlap"],[data-container="open_Lightbox_on"],[data-container="mobile_caption_overlap"],[data-container="mobile_caption_copy"],[data-container="lightbox_active_colors"]').show();

            if('1' == wp.Modula.Settings.get('lightbox_toolbar') && '1' == wp.Modula.Settings.get('lightbox_share')){
				if( '1' == wp.Modula.Settings.get('lightbox_email') ){
					rows.filter('[data-container="lightboxEmailSubject"],[data-container="lightboxEmailMessage"]').show();
				}else{
					rows.filter('[data-container="lightboxEmailSubject"],[data-container="lightboxEmailMessage"]').hide();
				}
            	rows.filter('[data-container="lightbox_linkedin"],[data-container="lightbox_facebook"],[data-container="lightbox_whatsapp"],[data-container="lightbox_pinterest"],[data-container="lightbox_twitter"],[data-container="lightbox_email"]').show();
            } else {
				rows.filter('[data-container="lightboxEmailSubject"],[data-container="lightboxEmailMessage"]').hide();
	            rows.filter('[data-container="lightbox_linkedin"],[data-container="lightbox_facebook"],[data-container="lightbox_whatsapp"],[data-container="lightbox_pinterest"],[data-container="lightbox_twitter"],[data-container="lightbox_email"]').hide();
            }
			
            jQuery('.lightbox-afterrow').show();

	        if (1 == wp.Modula.Settings.get('lightbox_toolbar')) {
		        rows.filter('[data-container="lightbox_close"],[data-container="lightbox_thumbs"],[data-container="lightbox_download"],[data-container="lightbox_zoom"],[data-container="lightbox_share"]').show();
	        }else{
				rows.filter('[data-container="lightbox_close"],[data-container="lightbox_thumbs"],[data-container="lightbox_download"],[data-container="lightbox_zoom"],[data-container="lightbox_share"]').hide();
			}
			this.changedToolbar(false,wp.Modula.Settings.get( 'lightbox_toolbar' ));
        }else{

            rows.filter( ' [data-container="loop_lightbox"],[data-container="lightbox_keyboard"], [data-container="lightbox_wheel"],[data-container="doubleClick"],[data-container="show_navigation"],[data-container="lightbox_clickSlide"],[data-container="lightbox_animationEffect"],[data-container="lightbox_animationDuration"],[data-container="lightbox_transitionEffect"],[data-container="lightbox_transitionDuration"],[data-container="lightbox_toolbar"],[data-container="lightbox_close"],[data-container="lightbox_thumbs"],[data-container="lightbox_download"],[data-container="lightbox_zoom"],[data-container="lightbox_share"],[data-container="lightbox_infobar"],[data-container="lightbox_dblclickSlide"],[data-container="lightbox_touch"],[data-container="lightbox_thumbsAutoStart"],[data-container="lightbox_thumbsAxis"],[data-container="lightbox_bottomThumbs"],[data-container="showAllOnLightbox"],[data-container="lightbox_background_color"],[data-container="showTitleLightbox"],[data-container="showCaptionLightbox"],[data-container="captionPosition"],[data-container="lightbox_linkedin"],[data-container="lightbox_facebook"],[data-container="lightbox_whatsapp"],[data-container="lightbox_pinterest"],[data-container="lightbox_twitter"],[data-container="lightbox_email"],[data-container="caption_overlap"],[data-container="open_Lightbox_on"],[data-container="mobile_caption_overlap"],[data-container="mobile_caption_copy"],[data-container="lightbox_active_colors"],[data-container="lightboxEmailSubject"],[data-container="lightboxEmailMessage"]' ).hide();
	        jQuery('.lightbox-afterrow').hide();

        }

    },
    changedToolbar: function( settings, value ){
		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="lightbox_toolbar"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( 1 == value && 'fancybox' == wp.Modula.Settings.get('lightbox') ) {
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
 
            }else{
            	child.setting_state( this, 'off');
            }

        });

        if ( 1 == value && 'fancybox' == wp.Modula.Settings.get('lightbox')) {
			currentRow.addClass( 'modula_accordion_open' );
        }
		
		this.changedLightboxShare( false, wp.Modula.Settings.get( 'lightbox_share' ) );
    },
	changedDropdownFilters: function( settings, value ) {
		let rows = this.get( 'rows' ),
			tabs = this.get( 'tabs' );

		if( 1 == value ) {
			rows.filter( '[data-container="filterClick"],[data-container="filterStyle"],[data-container="filterLinkColor"],[data-container="filterLinkHoverColor"],[data-container="filterTextAlignment"],[data-container="enableCollapsibleFilters"],[data-container="collapsibleActionText"],[data-container="enableMobileDropdownFilters"]' ).hide();
		}else {
			rows.filter( '[data-container="filterClick"],[data-container="filterStyle"],[data-container="filterLinkColor"],[data-container="filterLinkHoverColor"],[data-container="filterTextAlignment"],[data-container="enableCollapsibleFilters"],[data-container="collapsibleActionText"],[data-container="enableMobileDropdownFilters"]' ).show();
		}
	},
	changedMobileDropdownFilters: function( settings, value ) {
		let rows = this.get( 'rows' );

		if ( 1 == value ) {
			wp.Modula.Settings.set('enableCollapsibleFilters',0);
			rows.filter( '[data-container="enableCollapsibleFilters"]' ).find( 'input#enableCollapsibleFilters' ).attr( 'checked', false ).prop( 'checked', false );
			rows.filter( '[data-container="enableMobileDropdownFilters"]' ).find( 'input#enableMobileDropdownFilters' ).attr( 'checked', true ).prop( 'checked', true );
		}
	},
	changedCollapsileFilters    : function ( settings, value ) {
		let rows = this.get( 'rows' );

		if ( 1 == value ) {
			wp.Modula.Settings.set('enableMobileDropdownFilters',0);
			rows.filter( '[data-container="enableMobileDropdownFilters"]' ).find( 'input#enableMobileDropdownFilters' ).attr( 'checked', false ).prop( 'checked', false );
			rows.filter( '[data-container="enableCollapsibleFilters"]' ).find( 'input#enableCollapsibleFilters' ).attr( 'checked', true ).prop( 'checked', true );
		}
	},

	changedAnimation: function (settings, value) {
		var rows = this.get( 'rows' );

		if (false == value || 'false' == value) {
			
			rows.filter('[data-container="lightbox_animationDuration"]').setting_state( this, 'off');

		} else {
			
			rows.filter('[data-container="lightbox_animationDuration"]').setting_state( this, 'on');

		}
	},
	changedTransition: function (settings, value) {
		var rows = this.get('rows'),
			tabs = this.get('tabs');

		if (false == value || 'false' == value) {

			rows.filter('[data-container="lightbox_transitionDuration"]').setting_state( this, 'off');

		} else {

			rows.filter('[data-container="lightbox_transitionDuration"]').setting_state( this, 'on');

		}
	},
	changedLightboxTitle: function (settings, value) {

		var rows = this.get('rows');

		if ( '1' != value ) {
			rows.filter('[data-container="showTitleLightbox_position"]').hide();
		} else {
			rows.filter('[data-container="showTitleLightbox_position"]').show();
		}

	},
	changedLightboxCaption: function (settings, value) {

		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="showCaptionLightbox"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( '1' == value && 'fancybox' == wp.Modula.Settings.get('lightbox') || '1' == wp.Modula.Settings.get('showTitleLightbox') && 'fancybox' == wp.Modula.Settings.get('lightbox') ) {
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
 
            }else if( !('1' == value && 'fancybox' == wp.Modula.Settings.get('lightbox') || '1' == wp.Modula.Settings.get('showTitleLightbox') && 'fancybox' == wp.Modula.Settings.get('lightbox') ) && currentRow.hasClass( 'modula_accordion_open' ) ){
            	child.setting_state( this, 'off');
            }else{
				child.hide();
			}

        });

		if ( '1' == value && 'fancybox' == wp.Modula.Settings.get('lightbox') || '1' == wp.Modula.Settings.get('showTitleLightbox') && 'fancybox' == wp.Modula.Settings.get('lightbox') ) {
			currentRow.addClass( 'modula_accordion_open' );
		} 
	},

	changedLightboxShare: function (settings, value) {

		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="lightbox_share"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( '1' == value && 'fancybox' == wp.Modula.Settings.get('lightbox') && '1' == wp.Modula.Settings.get('lightbox_toolbar') ) {
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
 
            }else{
            	child.setting_state( this, 'off');
            }

        });


		if ( '1' == value && 'fancybox' == wp.Modula.Settings.get('lightbox') && '1' == wp.Modula.Settings.get('lightbox_toolbar')  ) {
			currentRow.addClass( 'modula_accordion_open' );
		}

		this.changedLightboxEmail( false, wp.Modula.Settings.get( 'lightbox_email' ) );
	},

	toggleLightboxShare: function(){

		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="lightbox_share"]'),
			parentRow = rows.filter('[data-container="lightbox_toolbar"]'),
			children  = currentRow.data( 'children' );

		jQuery.each(children, function(index, item) {

			var child = jQuery('[data-container="'+item+'"]');

			if ( parentRow.hasClass( 'modula_accordion_open' ) && 1 == wp.Modula.Settings.get( 'lightbox_share' ) && 1 == wp.Modula.Settings.get( 'lightbox_toolbar' )  ) {
				child.css('opacity', '1');
				child.find('input, textarea, select, button').removeAttr('disabled');
				child.show();
				
			}else if ( 1 == wp.Modula.Settings.get( 'lightbox_share' ) && parentRow.hasClass( 'modula_accordion_open' ) ) {
				child.setting_state( this, 'off');
				child.show();
			}else{
				child.hide();
			}

		});

	},

	changedLightboxEmail: function( settings, value ) {
		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="lightbox_email"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( '1' == value && '1' == wp.Modula.Settings.get( 'lightbox_share') && 'fancybox' == wp.Modula.Settings.get( 'lightbox') && '1' == wp.Modula.Settings.get( 'lightbox_toolbar')  ) {
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
 
            }else{
            	child.setting_state( this, 'off');
            }

        });

		if( '1' == value && '1' == wp.Modula.Settings.get( 'lightbox_share') && 'fancybox' == wp.Modula.Settings.get( 'lightbox') && '1' == wp.Modula.Settings.get( 'lightbox_toolbar') ) {
			currentRow.addClass( 'modula_accordion_open' );
		}
	},

	toggleLightboxEmail: function(){
		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="lightbox_email"]'),
			parentRow = rows.filter('[data-container="lightbox_share"]'),
			parentRowTwo = rows.filter('[data-container="lightbox_toolbar"]'),
			children  = currentRow.data( 'children' );

		jQuery.each(children, function(index, item) {

			var child = jQuery('[data-container="'+item+'"]');

			if ( parentRow.hasClass( 'modula_accordion_open' ) && parentRowTwo.hasClass( 'modula_accordion_open' ) && 1 == wp.Modula.Settings.get( 'lightbox_email' ) && 1 == wp.Modula.Settings.get( 'lightbox_share' ) && '1' == wp.Modula.Settings.get( 'lightbox_toolbar') ) {
				child.css('opacity', '1');
				child.find('input, textarea, select, button').removeAttr('disabled');
				child.show();
				
			}else if ( 1 == wp.Modula.Settings.get( 'lightbox_email' ) && parentRow.hasClass( 'modula_accordion_open' ) && parentRowTwo.hasClass( 'modula_accordion_open' ) ) {
				child.setting_state( this, 'off');
				child.show();
			}else{
				child.hide();
			}

		});

	},
	
	showGalleryTitle: function (settings, value) {

		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="show_gallery_title"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( 0 == value && currentRow.hasClass( 'modula_accordion_open' )) {
            	child.setting_state( this, 'off');
				child.show();
            }else if( 0 == value ){
				child.hide();
            }else{
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
			}
        });
		
		if( '1' == value ) {
			currentRow.addClass( 'modula_accordion_open' );
		}
	},
	
	showAllFilter: function (settings, value) {

		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="hideAllFilter"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( 1 == value && currentRow.hasClass( 'modula_accordion_open' )) {
            	child.setting_state( this, 'off');
				child.show();
            }else if( 1 == value ){
				child.hide();
            }else{
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
			}
        });
		
		if( 1 != value ) {
			currentRow.addClass( 'modula_accordion_open' );
		}
	},
	
	showCollapsibleFilters: function (settings, value) {

		var rows = this.get( 'rows' ),
			currentRow = rows.filter('[data-container="enableCollapsibleFilters"]'),
            children  = currentRow.data( 'children' );

        jQuery.each(children, function(index, item) {

            var child = jQuery('[data-container="'+item+'"]');

            if ( 0 == value && currentRow.hasClass( 'modula_accordion_open' )) {
            	child.setting_state( this, 'off');
				child.show();
            }else if( 0 == value ){
				child.hide();
            }else{
				child.css('opacity', '1');
                child.find('input, textarea, select, button').removeAttr('disabled');
            	child.show();
			}
        });
		
		if( '1' == value ) {
			currentRow.addClass( 'modula_accordion_open' );
		}
	},

});