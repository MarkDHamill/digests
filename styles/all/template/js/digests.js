function check_word_size_min (field) 
{
	size = field.value;
	if (size == '')	{
		return;
	}
	if ((size < 0) || (isNaN(size))) {
		alert("{LA_DIGESTS_SIZE_ERROR_MIN}");
		field.focus();
	}
	else {
		field.value = parseInt(size);
	}
}

<!-- IF S_DIGESTS_FORUMS_SELECTION -->
function check_uncheck(checkbox_id, radio_button)
{
	var checkbox = document.getElementById(checkbox_id);
	if (radio_button.checked) {
		checkbox.disabled = (radio_button.id == 'pms1') ? false : true;
	}
	else {
		checkbox.disabled = (radio_button.id == 'pms1') ? true : false;
	}
}

function disable_forums(disabled)
{
	var element_name = new String();
	var digests_id = document.getElementById('digests');
	
	// Assume a HTML 5 compatible browser
	var x = document.getElementById('div_0').getElementsByTagName("input");
	for(i=0;i<x.length;i++) {
		thisobject = x[i];
		element_name = thisobject.id;
		if(element_name != null) {
			if(element_name.substr(0,4) == "elt_") {
				thisobject.disabled = disabled;
			}
		}
	}
	
	// Also, enable/disable the All Forums checkbox
	var all_forums = document.getElementById('all_forums');
	all_forums.disabled = disabled;
	
	return true;
}

function unCheckSubscribedForums (checkbox) {
	is_checked = checkbox.checked;
 
	var element_name = new String();
	
	// Assume a HTML 5 compatible browser
	var x = document.getElementById('div_0').getElementsByTagName("input");
	for(i=0;i<x.length;i++) {
		thisobject = x[i];
		element_name = thisobject.id;
		if(element_name != null) {
			if(element_name.substr(0,4) == "elt_") {
				thisobject.checked = is_checked;
			}
		}
	}
	return true;
}

function unCheckAllForums () {

	// Unchecks or checks the all forums checkbox
	var digests_id = document.getElementById('digests');
	any_unchecked = false;
	
	// Assume a HTML 5 compatible browser
	var x = document.getElementById('div_0');
	var y = x.getElementsByTagName("input");
	for(i=0;((i<y.length) && (any_unchecked == false));i++) {
		thisobject = y[i];
		element_name = thisobject.name;
		if(element_name != null) {
			if(element_name.substr(0,4) == "elt_") {
				if (thisobject.checked == false) {
					digests_id.all_forums.checked = false;
					any_unchecked = true;
				}
			}
		}
	}
	if (any_unchecked == false)	{
		digests_id.all_forums.checked = true;
	}

	return;
}
