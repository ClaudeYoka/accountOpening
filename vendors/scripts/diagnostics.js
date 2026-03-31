/**
 * Diagnostic Script for Checking Console Issues
 * Run this in browser console to identify and resolve issues
 */

console.log('=== Application Diagnostics ===');
console.log('');

// Check jQuery version
if (typeof jQuery !== 'undefined') {
	console.log('✓ jQuery is loaded');
	console.log('  Version:', jQuery.fn.jquery);
} else {
	console.warn('✗ jQuery is NOT loaded - DataTables will not work!');
}

// Check DataTables
if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
	console.log('✓ DataTables is loaded');
} else {
	console.warn('✗ DataTables is NOT loaded');
}

// Check all tables on page
var tables = document.querySelectorAll('table');
console.log('');
console.log('Found ' + tables.length + ' table(s) on page');

tables.forEach(function(table, index) {
	console.log('');
	console.log('Table #' + (index + 1) + ':');
	console.log('  Classes:', table.className);
	console.log('  Has thead:', table.querySelector('thead') !== null);
	console.log('  Has tbody:', table.querySelector('tbody') !== null);
	console.log('  Header columns:', table.querySelectorAll('thead th, thead td').length);
	console.log('  Data rows:', table.querySelectorAll('tbody tr').length);
	
	// Check for structure issues
	if (!table.querySelector('thead')) {
		console.warn('    ⚠ MISSING: <thead> element');
	}
	if (!table.querySelector('tbody')) {
		console.warn('    ⚠ MISSING: <tbody> element');
	}
	
	// Check column consistency
	var headerCols = table.querySelectorAll('thead th, thead td').length;
	if (headerCols > 0) {
		var dataRows = table.querySelectorAll('tbody tr');
		dataRows.forEach(function(row, rowIndex) {
			var dataCols = row.querySelectorAll('td').length;
			if (dataCols !== headerCols && dataCols > 0) {
				console.warn('    ⚠ Row #' + (rowIndex + 1) + ' has ' + dataCols + ' columns, expected ' + headerCols);
			}
		});
	}
});

console.log('');
console.log('=== Resource Loading ===');

// Check loaded resources
var scripts = document.querySelectorAll('script[src]');
console.log('Loaded Scripts: ' + scripts.length);
scripts.forEach(function(script) {
	var src = script.getAttribute('src');
	if (src) {
		console.log('  - ' + src);
	}
});

var stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
console.log('');
console.log('Loaded Stylesheets: ' + stylesheets.length);
stylesheets.forEach(function(link) {
	var href = link.getAttribute('href');
	if (href) {
		console.log('  - ' + href);
	}
});

console.log('');
console.log('=== Browser Info ===');
console.log('User Agent:', navigator.userAgent);
console.log('');
console.log('=== Console Messages Notes ===');
console.log('✓ Items with checkmark = OK');
console.log('⚠ Items with warning = May cause issues');
console.log('✗ Items with X = Critical issue');
