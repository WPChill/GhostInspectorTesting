(function($){

	function ModulaLink( element ){
		var instance = this,
		    tapped = false,
		    tappedTimeout;

		this.$el = $( element );
		this.images = this.$el.data('images');
		this.config = this.$el.data('config');

		if ( false == this.config || !this.config ) {
			return;
		}

		var links = jQuery.map( this.images, function(o) {
			    return { 'src' : o.src, 'opts': { 'caption': o.opts.caption,'thumb':o.opts.thumb,'image_id' : o.opts.image_id } }
		    });

		// Callbacks
		this.config['lightboxOpts']['beforeLoad'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_before_load', [instance, this]);
		};
		this.config['lightboxOpts']['afterLoad'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_after_load', [instance, this]);
		};
		this.config['lightboxOpts']['beforeShow'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_before_show', [instance, this]);
		};
		this.config['lightboxOpts']['afterShow'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_after_show', [instance, this]);
		};
		this.config['lightboxOpts']['beforeClose'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_before_close', [instance, this]);
		};
		this.config['lightboxOpts']['afterClose'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_after_close', [instance, this]);
		};
		this.config['lightboxOpts']['onInit'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_on_init', [instance, this]);
		};
		this.config['lightboxOpts']['onActivate'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_on_activate', [instance, this]);
		};
		this.config['lightboxOpts']['onDeactivate'] = function () {
			jQuery(document).trigger('modula_link_fancybox_lightbox_on_deactivate', [instance, this]);
		};

		this.$el.click(function(evt){
			evt.preventDefault();

			if ( 'undefined' != typeof $.modulaFancybox ) {
				$.modulaFancybox.open( links, instance.config['lightboxOpts'] );
			}

		});

		// Trigger event before init
        $( document ).trigger('modula_link_api_after_init', [ instance ]  );

		// Copy caption on double tap
		if ( 'undefined' != typeof instance.config['lightboxOpts']['copyCaptionMobile'] && '1' == instance.config['lightboxOpts']['copyCaptionMobile'] ) {

			jQuery( document ).on( 'click', 'html body .modula-fancybox-container .modula-fancybox-caption__body', function ( e ) {

				if ( !tapped ) { //if tap is not set, set up single tap
					tapped = true;
					tappedTimeout = setTimeout( function () {
						tapped = false;
					}, 300 );
				} else {    //tapped within 300ms of last tap. double tap
					clearTimeout( tappedTimeout ); //stop single tap callback
					tapped = false;
					var $temp = $( "<input id='modula-temp-field'>" );
					$( "body" ).append( $temp );
					$temp.val( $( this ).text() ).select();
					document.execCommand( "copy" );
					$temp.remove();
				}
			} );
		}

	}

	ModulaLink.prototype.open = function ( index ) {
		var instance = this;
		if ( 'undefined' != typeof $.modulaFancybox ) {
			$.modulaFancybox.open( instance.images, instance.config['lightboxOpts'], index );
		}
	}

	jQuery( document ).ready( function($){
	    var modulaGalleries = $('.modula-link');
	    $.each( modulaGalleries, function(){
	        new ModulaLink( this );
	    });
	});

}(jQuery));