wp.Modula = 'undefined' === typeof( wp.Modula ) ? {} : wp.Modula;

jQuery( document ).ready( function( $ ){

	// Initialize Filters
	wp.Modula.Filters = new wp.Modula.models['filters']({
		'filters' : wp.Modula.Settings.get( 'filters' ),
    });
    
    // Initialize Gallery Conditions 

    wp.Modula.Conditions = new modulaProGalleryConditions();

	// Bulk Edit
	if('undefined' != typeof wp.Modula.bulkedit){
		wp.Modula.BulkEdit = new wp.Modula.bulkedit['model'];
	}


	$('#modula-bulk-edit').click(function( evt ){
		evt.preventDefault();
		wp.Modula.BulkEdit.open();
	});


	if('undefined' != typeof  wp.Modula.Items ){
		$(document).on( 'modula:sortChanged', wp.Modula.Items.changeOrder );

		// Initiate the sorting
		wp.Modula.sorting.init();
	}


    // Unbind previous set click
	$('#test-scaling-preview').unbind('click');

	$('#modula-image-loaded-effects ').on('click','#test-scaling-preview',function (e) {

		e.preventDefault();
		var scale   = $('input[data-setting="loadedScale"]').val();
		var rotate  = $('input[data-setting="loadedRotate"]').val();
		var h_slide = $('input[data-setting="loadedHSlide"]').val();
		var v_slide = $('input[data-setting="loadedVSlide"]').val();

		var targets = $('#modula-image-loaded-effects .modula-item');

		targets.css('transform', 'scale(' + scale/100+') translate(' + h_slide + 'px,' + v_slide + 'px) rotate(' + rotate + 'deg)');

		setTimeout(function () {
			targets.removeAttr('style')
		}, 600)
	});

});