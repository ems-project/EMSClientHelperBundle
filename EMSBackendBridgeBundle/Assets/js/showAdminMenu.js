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
			admCont = $('<div>',  {class: 'admin-menu-content dropdown'});
			admBtn = $('<button>', 
				{
					id: 'admin-menu-' + index,
					class: 'btn btn-primary btn-xs',
					'data-toggle': 'dropdown',
					'aria-haspopup': true,
					'aria-expanded': false,
					type: 'button',
					html: 'Edit <span class="caret"></span>'
				});

			admUl = $('<ul>', {class:'dropdown-menu', 'aria-labelledby': 'admin-menu-' + index});
			admLiRev = $('<li>', {html: '<a href="'+ url +'/data/revisions/'+ type +':'+ ouuid +'" target="_blank">See this revision</a>'});
			admLiEdit = $('<li>', {html: '<a href="'+ url +'/data/new-draft/'+ type +'/'+ ouuid +'" target="_blank">Edit this revision</a>'});
			
			$(this).append(admWrapper
						.append(admCont
						.append(admBtn)
						.append(admUl
							.append(admLiRev)
							.append(admLiEdit))));
		});
	}

	$('.admin-menu-wrapper').parent().hover(function() {
		$(this).find('.admin-menu-wrapper').toggleClass('hide');
	});
}