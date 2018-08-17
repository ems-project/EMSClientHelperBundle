/**
 * Show admin edit button in FE
 */
export default function showAdminMenu() { 
	var targets = $('[data-ems-key]');
	var ouuid, type, url, admWrapper, admCont, admUl, admLiRev, admLiEdit, admBtn;

	if (typeof targets !== 'undefined' && targets.length > 0) {

		targets.each( function(index){

			ouuid 	= $(this).data('emsKey');
			type 	= $(this).data('emsType');
			url 	= $(this).data('emsUrl');
			admWrapper = $('<div>',  {class: 'admin-menu-wrapper hide'});
			admBtn = $('<a>', 
				{
					id: 	'admin-menu-' + index,
					class: 	'btn btn-primary btn-xs emsch',
					type: 	'button',
					html: 	'Back to ems',
					href: 	url +'/data/revisions/'+ type +':'+ ouuid,
					target: '_blank'
				});
			
			$(this).append(admWrapper
						.append(admBtn));
		});
	}

	$('.admin-menu-wrapper').parent().hover(function() {
		$(this).find('.admin-menu-wrapper').toggleClass('hide');
	});
}