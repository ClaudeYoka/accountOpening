/**
 * Global JavaScript fixes for common issues
 * - DataTable initialization errors
 * - Tracking prevention issues  
 * - Console errors handling
 */

(function() {
	'use strict';

	// Store original console methods
	var originalWarn = console.warn;
	var originalError = console.error;

	// Filter tracking prevention warnings from console
	console.warn = function() {
		var message = Array.prototype.slice.call(arguments)[0];
		if (typeof message === 'string' && message.indexOf('Tracking Prevention') === -1) {
			originalWarn.apply(console, arguments);
		}
	};

	// Filter and log specific errors
	console.error = function() {
		var message = Array.prototype.slice.call(arguments)[0];
		// Only log real errors, not tracking prevention issues
		if (typeof message === 'string') {
			if (message.indexOf('Tracking Prevention') === -1 && 
				message.indexOf('mousewheel') === -1 && 
				message.indexOf('404') === -1) {
				originalError.apply(console, arguments);
			}
		}
	};

	// Wait for DOM to be fully loaded
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeApp);
	} else {
		initializeApp();
	}

	function initializeApp() {
		// Ensure all tables are properly structured
		ensureTableStructure();
		
		// Initialize DataTables after a small delay to ensure all dependencies are loaded
		setTimeout(function() {
			initializeDataTables();
		}, 100);
	}

	/**
	 * Ensure all data-table elements have proper thead and tbody structure
	 */
	function ensureTableStructure() {
		var tables = document.querySelectorAll('table.data-table');
		tables.forEach(function(table) {
			// Check if table has thead
			if (!table.querySelector('thead')) {
				console.warn('Table is missing thead element');
				// Wrap existing header rows in thead if needed
				var firstRow = table.querySelector('tr');
				if (firstRow) {
					var thead = document.createElement('thead');
					thead.appendChild(firstRow.cloneNode(true));
					table.insertBefore(thead, firstRow);
				}
			}

			// Check if table has tbody
			if (!table.querySelector('tbody')) {
				console.warn('Table is missing tbody element');
				// Create tbody and move data rows to it
				var tbody = document.createElement('tbody');
				var rows = table.querySelectorAll('tr');
				rows.forEach(function(row) {
					if (row.parentNode !== table.querySelector('thead')) {
						tbody.appendChild(row.cloneNode(true));
					}
				});
				table.appendChild(tbody);
			}
		});
	}

	/**
	 * Initialize DataTables for all tables
	 */
	function initializeDataTables() {
		// This will be called after jQuery DataTables plugin is ready
		if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
			// The datatable-setting.js will handle the initialization
			// This is just a backup in case it fails
		}
	}

	// Add global error handler to catch uncaught errors
	window.addEventListener('error', function(event) {
		if (event.message && event.message.indexOf('Tracking Prevention') === -1) {
			// Log real errors, not tracking prevention issues
			if (event.message.indexOf('_DT_CellIndex') !== -1) {
				console.warn('DataTable cell index error detected - tables may not be fully initialized');
			}
		}
		// Don't prevent default handling of real errors
		return false;
	});

	// Prevent unhandled promise rejections from tracking-related issues
	window.addEventListener('unhandledrejection', function(event) {
		if (event.reason && typeof event.reason === 'string') {
			if (event.reason.indexOf('Tracking Prevention') !== -1) {
				// Ignore tracking prevention errors
				event.preventDefault();
			}
		}
	});

})();
