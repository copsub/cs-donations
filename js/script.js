// Copyright 2012 Designgeneers! Web Design (email: info@designgeneers.com)
// 

function DgxDonateSubscribeFormEvents()
{
	jQuery('#dgx-donate-designated').click(function() {
		DgxDonateUpdateDesignatedDiv();
	});
	
	jQuery('#dgx-donate-tribute').click(function() {
		DgxDonateUpdateTributeDiv();
	});

	jQuery( '#dgx-donate-employer' ).click( function() {
		DgxDonateUpdateEmployerDiv();
	} );
}

function DgxDonateUpdateDesignatedDiv()
{
	if ( jQuery('#dgx-donate-designated:checked').length > 0 )
	{
       	jQuery(".dgx-donate-form-designated-box").show('fast');
   	}
   	else
   	{
       	jQuery(".dgx-donate-form-designated-box").hide('fast');
   	}
}

function DgxDonateUpdateTributeDiv()
{
	if ( jQuery('#dgx-donate-tribute:checked').length > 0 )
	{
       	jQuery(".dgx-donate-form-tribute-box").show('fast');
   	}
   	else
   	{
       	jQuery(".dgx-donate-form-tribute-box").hide('fast');
   	}
}

function DgxDonateUpdateEmployerDiv() {
	if ( jQuery( '#dgx-donate-employer:checked' ).length > 0 ) {
		jQuery( ".dgx-donate-form-employer-box" ).show( 'fast' );
	} else {
		jQuery( ".dgx-donate-form-employer-box" ).hide( 'fast' );
	}
}

function DgxDonateAddOnClickOther()
{
	jQuery('#dgx-donate-other-input').focus(function() {
		jQuery('input[id=dgx-donate-other-radio]').attr('checked', 'checked');
	});
}




/* --------------------------------------------------------------------------------------------- */

/* JR: select signup type */
function DgxDonationTypeSelector(){
	 
	// added by KB 
	//if ( window.location.hash.substring(1) == 'support' ) {
	//	var timer = setInterval( function() {
	//		
	//		if ( jQuery('#dgx-donate-container #dgx-donate-repeating').length > 0 ) {
	//			clearInterval( timer );
	//			jQuery('#dgx-donate-container #dgx-donate-repeating').attr('disabled','disabled');
	//		    jQuery('#dgx-donate-container #dgx-donate-repeating').val('');
	//		    jQuery('.dgx-type-onetime').addClass('active');
	//		}
	//		
	//	}, 60 );
	//
	//}

    // added by KB
	jQuery('.dgx-donate-form-section p').addClass('clr');
	var copyPriceSection = jQuery('.dgx-donate-form-section:eq(1)').clone();

/* single donation --------------------------------------------------------------------------------------------- */


	jQuery('#dgx-donate-container .dgx-type-onetime').on('click', function(){

		// added by KB
		jQuery('input[type="text"]').removeClass('dgx-donate-invalid-input');
   
		jQuery('#dgx-donate-container #dgx-donate-repeating').attr('disabled','disabled');
		jQuery('#dgx-donate-container #dgx-donate-repeating').val('');

        // added by KB
		jQuery('#dgx-donate-container .dgx-type-membership').removeClass('active');
		jQuery(this).removeClass('active').addClass('active');

		jQuery('.dgx-type-membership-section').hide();
		jQuery('.dgx-type-onetime-section').show();
		jQuery('#dgx-donate-container #dgx-type-membership-checked').prop('checked',false);
		jQuery('#dgx-donate-container #dgx-type-onetime-checked').prop('checked',true);
		
		return false;

	});
	
/* Membership --------------------------------------------------------------------------------------------- */

	jQuery('#dgx-donate-container .dgx-type-membership').on( 'click', function() {

		// added by KB
		jQuery('input[type="text"]').removeClass('dgx-donate-invalid-input');

		jQuery('#dgx-donate-container #dgx-donate-repeating').removeAttr('disabled');
		jQuery('#dgx-donate-container #dgx-donate-repeating').val('1');

        // added by KB
		jQuery('#dgx-donate-container .dgx-type-onetime').removeClass('active');
		jQuery(this).removeClass('active').addClass('active');

        // added by KB
		jQuery('.dgx-type-onetime-section').hide();
		jQuery('.dgx-type-membership-section').show();
		jQuery('#dgx-donate-container #dgx-type-onetime-checked').prop('checked',false);
		jQuery('#dgx-donate-container #dgx-type-membership-checked').prop('checked',true);


		return false;

	});


/* --------------------------------------------------------------------------------------------- */
}






jQuery(document).ready(function() {	

	// Hook up listener for checkboxes on expanders
	DgxDonateSubscribeFormEvents();
	
	// Make sure form divs like tribute box match their checkbox state
	DgxDonateUpdateDesignatedDiv();
	DgxDonateUpdateTributeDiv();
	DgxDonateUpdateEmployerDiv();
	
	// Hook up special handling for the OTHER donation amount box
	DgxDonateAddOnClickOther();
	
	//JR: Hook up type selector
	DgxDonationTypeSelector()

});

