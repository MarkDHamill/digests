$(document).ready(function() {

	"use strict";
	// If the edit subscribers table row's expand/collapse icon is clicked on, or the associated link next to it is clicked on,
	// toggle the display of the associated detail row between not visible and visible.
	$("a[id^=link-], a[id^=imglink-]").click(function() {
		// Infer the ids that will be needed by parsing for the id of the table row with focus
		var id = $(this).attr('id');
		var start = id.indexOf('-');
		var rowId = id.substring(start + 1);
		var selector = '#user-' + rowId + '-detail';
		var displayStyle = $(selector).css('display');
		if (displayStyle === 'none') {
			$(selector).css('display', 'table-row');
			$('#plusminus-' + rowId).attr({
				'src': collapseImageIdSrc,
				'alt': collapseImageIdAlt,
				'title': collapseImageIdTitle
			});
		} else {
			$(selector).css('display', 'none');
			$('#plusminus-' + rowId).attr({
				'src' : expandImageIdSrc,
				'alt' : expandImageIdAlt,
				'title' : expandImageIdTitle
			});
		}
	});

	// If any individual forum is unchecked, the all_forums checkbox should be unchecked.
	// If all individual forums are checked, the all_forums checkbox should be checked.
	$("[id*=elt_]").click(function() {
		var allChecked = true;	// Assume all forums for the block under focus are checked
		var id = ($(this).attr('id'));
		var rowId = getRowId(id);	// Returns the unique row id for the block being changed by parsing the id attribute
		$("[id*=elt_]").each(function() {
			var instanceId = $(this).attr('id');
			var instanceRowId = getRowId(instanceId);	// Returns the unique row id for the block being changed by parsing the id attribute
			if (instanceRowId === rowId && !$(this).is(':checked')) {
				$("#user-" + instanceRowId + "-all_forums").prop('checked', false);
				allChecked = false;	// Flag if any forum is unchecked
			}
		});
		if (allChecked) {
			($("#user-" + rowId + "-all_forums").prop('checked', true));
		}
	});

	// If the all forums checkbox is checked, all individual forums should be checked, and visa versa.
	$("[id*=all_forums]").click(function() {
		var id = ($(this).attr('id'));
		var rowId = getRowId(id);	// Returns the unique row id for the block being changed by parsing the id attribute
		if ($(this).is(':checked')) {
			$("[id*=elt_]").each(function() {
				var instanceId = $(this).attr('id');
				var instanceRowId = getRowId(instanceId);	// Returns the unique row id for the block being changed by parsing the id attribute
				if (instanceRowId === rowId) {
					$(this).prop("checked", true);
				}
			});
		}
		else {
			$("[id*=elt_]").each(function() {
				var instanceId = $(this).attr('id');
				var instanceRowId = getRowId(instanceId);	// Returns the unique row id for the block being changed by parsing the id attribute
				if (instanceRowId === rowId) {
					$(this).prop("checked", false);
				}
			});
		}
	});

	// If a field was not changed, disable it so it won't be sent to the web server. This helps get around PHP's
	// max_input_var resource limitation on the Edit subscribers screen.
	$('#acp_digests').submit(function() {
		if ($('#digests').length === 1) {
			// Logic only applies on edit subscribers screen because stack won't exist otherwise. #digests is an
			// ID only on the edit subscribers screen.
			$('input, select').each(function() { // jshint ignore:line
				if (!inStack($(this).attr('name'))) {
					$(this).prop('disabled', true);
				}
			});
		}
	});

	function getRowId(id) {
		var end = id.indexOf('-', 5);
		return id.substring(5, end);
	}

});
