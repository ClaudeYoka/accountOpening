$('document').ready(function(){
	// Initialize data-table with error handling
	if ($('.data-table').length > 0) {
		try {
			$('.data-table').each(function(index) {
				// Check if table has proper structure
				var $table = $(this);
				var hasHead = $table.find('thead').length > 0;
				var hasBody = $table.find('tbody').length > 0;
				
				if (hasHead && hasBody) {
					$(this).DataTable({
						scrollCollapse: true,
						autoWidth: false,
						responsive: true,
						columnDefs: [{
							targets: "datatable-nosort",
							orderable: false,
						}],
						"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
						"language": {
							"info": "_START_-_END_ of _TOTAL_ entries",
							searchPlaceholder: "Search",
							paginate: {
								next: '<i class="ion-chevron-right"></i>',
								previous: '<i class="ion-chevron-left"></i>'  
							}
						},
					});
				} else {
					console.warn('Table #' + index + ' is missing thead or tbody element. Skipping DataTable initialization.');
				}
			});
		} catch(e) {
			console.error('DataTable initialization error:', e);
		}
	}

	// Initialize data-table-export with error handling
	if ($('.data-table-export').length > 0) {
		try {
			$('.data-table-export').DataTable({
				scrollCollapse: true,
				autoWidth: false,
				responsive: true,
				columnDefs: [{
					targets: "datatable-nosort",
					orderable: false,
				}],
				"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
				"language": {
					"info": "_START_-_END_ of _TOTAL_ entries",
					searchPlaceholder: "Search",
					paginate: {
						next: '<i class="ion-chevron-right"></i>',
						previous: '<i class="ion-chevron-left"></i>'  
					}
				},
				dom: 'Bfrtp',
				buttons: [
				'copy', 'csv', 'pdf', 'print'
				]
			});
		} catch(e) {
			console.error('DataTable-export initialization error:', e);
		}
	}

	// Initialize select-row table
	if ($('.select-row').length > 0) {
		try {
			var table = $('.select-row').DataTable();
			$('.select-row tbody').on('click', 'tr', function () {
				if ($(this).hasClass('selected')) {
					$(this).removeClass('selected');
				}
				else {
					table.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
				}
			});
		} catch(e) {
			console.error('Select-row table initialization error:', e);
		}
	}

	// Initialize multiple-select-row table
	if ($('.multiple-select-row').length > 0) {
		try {
			var multipletable = $('.multiple-select-row').DataTable();
			$('.multiple-select-row tbody').on('click', 'tr', function () {
				$(this).toggleClass('selected');
			});
		} catch(e) {
			console.error('Multiple-select-row table initialization error:', e);
		}
	}
	// Initialize checkbox-datatable
	if ($('.checkbox-datatable').length > 0) {
		try {
			var table = $('.checkbox-datatable').DataTable({
				'scrollCollapse': true,
				'autoWidth': false,
				'responsive': true,
				"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
				"language": {
					"info": "_START_-_END_ of _TOTAL_ entries",
					searchPlaceholder: "Search",
					paginate: {
						next: '<i class="ion-chevron-right"></i>',
						previous: '<i class="ion-chevron-left"></i>'  
					}
				},
				'columnDefs': [{
					'targets': 0,
					'searchable': false,
					'orderable': false,
					'className': 'dt-body-center',
					'render': function (data, type, full, meta){
						return '<div class="dt-checkbox"><input type="checkbox" name="id[]" value="' + $('<div/>').text(data).html() + '"><span class="dt-checkbox-label"></span></div>';
					}
				}],
				'order': [[1, 'asc']]
			});

			$('#example-select-all').on('click', function(){
				var rows = table.rows({ 'search': 'applied' }).nodes();
				$('input[type="checkbox"]', rows).prop('checked', this.checked);
			});

			$('.checkbox-datatable tbody').on('change', 'input[type="checkbox"]', function(){
				if(!this.checked){
					var el = $('#example-select-all').get(0);
					if(el && el.checked && ('indeterminate' in el)){
						el.indeterminate = true;
					}
				}
			});
		} catch(e) {
			console.error('Checkbox-datatable initialization error:', e);
		}
	}
});