"use strict";
$(document).ready(function(){

	// Error handling popup settings
	$("#dialog").dialog({
		title: dialogError,
		autoOpen: false,
		modal: true,
		minHeight: 0,
		draggable: false,
		resizeable: false,
		closeOnEscape: true,
		buttons: [
			{
				text: ok,
				click: function() {
					$(this).dialog("close");
				}
			}
		]
	});

	// If private messages will be allowed in the digest, the mark all private message checkbox must not be disabled.
	$("#pms1").click(function() {
		if ($("#pms1").is(':checked')) {
			$("#mark_read").prop("disabled", false);
		}
	});

	// If private messages will not be allowed in the digest, the mark all private message checkbox must be disabled.
	$("#pms2").click(function() {
		if ($("#pms2").is(':checked')) {
			$("#mark_read").prop("disabled", true);
		}
	});

	// If the all forums checkbox is checked, all individual forums should be checked, and visa versa. Ignore excluded
	// and included forums as these should always retain their original disabled setting.
	$("#all_forums").click(function(){
		if ($("#all_forums").is(':checked')) {
			$("[id*=elt_]").each(function() {
				if (!exclude_forum($(this).attr('id'))) {
					$(this).prop("checked", true);
				}
			});
		}
		else {
			$("[id*=elt_]").each(function() {
				if (!exclude_forum($(this).attr('id'))) {
					$(this).prop("checked", false);
				}
			});
		}
	});

	// If "See popular topics" field is set to No, the field "Minimum value of popularity" needs to be disabled, and visa versa.
	$("#popular1").click(function() {
		$("#popularity_size").prop("disabled", false);
	});
	$("#popular2").click(function() {
		$("#popularity_size").prop("disabled", true);
	});

	// If any individual forum is unchecked, the all_forums checkbox should be unchecked. Exception: required or excluded forums.
	// If all individual forums are checked, the all_forums checkbox should be checked. Exception: required or excluded forums.
	$("[id*=elt_]").click(function() {
		var allChecked = true;	// Assume all forums are checked
		$("[id*=elt_]").each(function() {
			$("#all_forums").prop('checked', false);
			if ((!ignore_forum($(this).attr('id'))) && !$(this).is(':checked')) {
				allChecked = false;	// Flag if any forum is unchecked
			}
		});
		if (allChecked) {
			($("#all_forums").prop('checked', true));
		}
	});

	// If bookmarked topics only is selected, disable the forum controls, otherwise enable them. All forums checkbox also needs
	// to be enabled or disabled.
	$("#bookmarks, #all, #first").click(function() {
		var disabled = $("#bookmarks").is(':checked');
		$("[id*=elt_]").each(function() {
			if (!ignore_forum($(this).attr('id'))) {
				$(this).prop('disabled', disabled);
			}
		});
		$("#all_forums").prop('disabled', disabled);
	});

	// If all forums is unchecked and there are no checked forums, do not allow the form
	// to submit and display an error message. If bookmarked topics only is checked, then ignore.
	$("#phpbbservices_digests").submit(function(event) {
		if (!$("#all_forums").is(':checked') && !$("#bookmarks").is(':checked')) {
			var anyChecked = false;
			$("[id*=elt_]").each(function() {
				if ($(this).prop('checked')) {
					anyChecked = true;
				}
			});
			if (!anyChecked) {
				$("#dialog").text(noForumsChecked).dialog("open");
				event.preventDefault();
			}
		}
	});

	function exclude_forum(forumId) {
		// Returns true if the forum representing forumId should be excluded. Pattern is elt_1_2 where 1 is the forum_id
		// and 2 is the parent forum_id.
		var start = forumId.indexOf('_');
		var end = forumId.lastIndexOf('_');
		return excludedForumsArray.indexOf(forumId.substring(start+1,end)) !== -1;
	}

	function ignore_forum(forumId) {
		// Returns true the forum representing forumId should be ignored. Pattern is elt_1_2 where 1 is the forum_id
		// and 2 is the parent forum_id.
		var start =forumId.indexOf('_');
		var end = forumId.lastIndexOf('_');
		return ignoredForumsArray.indexOf(forumId.substring(start+1,end)) !== -1;
	}

});
