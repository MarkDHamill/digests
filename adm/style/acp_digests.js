$(document).ready(function() {

	"use strict";
	// If the edit subscribers table row's expand/collapse icon is clicked on, or the associated link next to it is clicked on,
	// toggle the display of the associated detail row between not visible and visible.
	$("a[id^=link-], a[id^=imglink-]").click(function() {
		// Infer the ids that will be needed by parsing for the id of the table row with focus
		var id = $(this).attr('id');
		var start = id.indexOf('-');
		var rowId = id.substring(start + 1);
		var displayStyle = $('#user-' + rowId + '-detail').css('display');
		if (displayStyle === 'none') {
			$('#user-' + rowId + '-detail').css('display', 'table-row');
			$('#plusminus-' + rowId).attr('src', collapseImageIdSrc);
			$('#plusminus-' + rowId).attr('alt', collapseImageIdAlt);
			$('#plusminus-' + rowId).attr('title', collapseImageIdTitle);
		} else {
			$('#user-' + rowId + '-detail').css('display', 'none');
			$('#plusminus-' + rowId).attr('src', expandImageIdSrc);
			$('#plusminus-' + rowId).attr('alt', expandImageIdAlt);
			$('#plusminus-' + rowId).attr('title', expandImageIdTitle);
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

	function getRowId(id) {
		var end = id.indexOf('-', 5);
		var rowId = id.substring(5, end);
		return rowId;
	}

	function beforeSubmit() {
		// Disables a form field if it was not changed. Disabled fields are not submitted to the web server.
		var allInputs = document.getElementsByTagName("input");
		var allSelects = document.getElementsByTagName("select");
		for (var k = 0; k < allInputs.length; k++) {
			var name = allInputs[k].name;
			if (!inStack(name)) {
				allInputs[k].disabled = true;
			}
		}

		for(var k = 0; k < allSelects.length; k++) {
			var name = allSelects[k].name;
			if (!inStack(name)) {
				allSelects[k].disabled = true;
			}
		}
		return true;
	}

});
