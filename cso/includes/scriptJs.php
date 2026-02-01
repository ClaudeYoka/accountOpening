<!-- js -->

	<script src="../vendors/scripts/core.js"></script>
	<script src="../vendors/scripts/script.min.js"></script>
	<script src="../vendors/scripts/process.js"></script>
	<script src="../vendors/scripts/layout-settings.js"></script>
	<script src="../src/plugins/apexcharts/apexcharts.min.js"></script>
	<script src="../src/plugins/datatables/js/jquery.dataTables.min.js"></script>
	<script src="../src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
	<script src="../src/plugins/datatables/js/dataTables.responsive.min.js"></script>
	<script src="../src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>

	<!-- buttons for Export datatable -->
	<script src="../src/plugins/datatables/js/dataTables.buttons.min.js"></script>
	<script src="../src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
	<script src="../src/plugins/datatables/js/buttons.print.min.js"></script>
	<script src="../src/plugins/datatables/js/buttons.html5.min.js"></script>
	<script src="../src/plugins/datatables/js/buttons.flash.min.js"></script>
	<script src="../src/plugins/datatables/js/vfs_fonts.js"></script>
	
	<script src="../vendors/scripts/datatable-setting.js"></script>
	<script>
		$(document).ready(function() {
			// Ensure modal is appended to body to avoid stacking context issues
			var modal = $('#documentsModal');
			if (modal.length) {
				modal.appendTo('body');

				// Ensure high z-index so modal is clickable above other fixed elements
				var setHighZIndex = function() {
					modal.css('z-index', 200000);
					$('.modal-backdrop').css('z-index', 150000);
				};
				// When the modal is shown, set z-index values
				modal.on('shown.bs.modal', setHighZIndex);
				modal.on('show.bs.modal', setHighZIndex);
			}
		});
	</script>
</body>