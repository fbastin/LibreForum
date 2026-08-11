var closetimer=0;

function recipient_key_change(recipient_key_object) {
	document.getElementById("selected_users").style.display="none";
	document.getElementById("selected_groups").style.display="none";
	document.getElementById("conditional_users").style.display="none";
	if(recipient_key_object.selectedIndex == 1) {
		document.getElementById("selected_users").style.display="inline";
	} else if (recipient_key_object.selectedIndex == 3) {
		document.getElementById("selected_groups").style.display="inline";
	} else if (recipient_key_object.selectedIndex == 2) {
		document.getElementById("conditional_users").style.display="block";
	}
}

function sender_key_change(sender_key_object) {
	if (sender_key_object.selectedIndex != 1) {
		document.getElementById("custom_from").style.display="none";
	} else {
		document.getElementById("custom_from").style.display="block";
	}
}
	
function addAttachment(attachment_id) {
	document.getElementById("addattachment"+attachment_id).style.display="none";
	document.getElementById("attachment"+attachment_id).style.display="block";
}
	
function addCondition(condition_id) {
	document.getElementById("addcondition"+condition_id).style.display="none";
	document.getElementById("condition"+condition_id).style.display="block";
}
	
function set_closetimer(field) {
	if (field == "body") {
		closetimer = window.setTimeout('hide_user_fields("body"),5');
	} else if (field == "subject") {
		closetimer = window.setTimeout('hide_user_fields("subject"),5');
	} else if (field == "subject_conditional") {
		closetimer = window.setTimeout('hide_conditional_content("subject"),5');
	} else if (field == "body_conditional") {
		closetimer = window.setTimeout('hide_conditional_content("body"),5');
	}
}
	
function cancel_closetimer() { 
	if (closetimer) {
		window.clearTimeout(closetimer);
		closetimer = null;
	}
}
	
function add_user_field(field,selection) { 
	field_name = document.getElementById(field+"_user_field").options[selection.selectedIndex].text;
	editor_tools_add_tags("%"+field_name+"%",field+"_id");
	hide_user_fields(field);
	selection.selectedIndex=-1;
	selection.scrollTop=0;
}
	
function show_user_fields(field) { 
	hide_conditional_content(field);
	cancel_closetimer();
	document.getElementById(field+"_user_fields_div").style.visibility="visible";
}
	
function hide_user_fields(field) {
	document.getElementById(field+"_user_fields_div").style.visibility="hidden";
}

function show_conditional_content(field) {
	hide_user_fields(field);
	cancel_closetimer();
	document.getElementById(field+"_conditional_content_div").style.visibility="visible";
}
	
function hide_conditional_content(field) {
	document.getElementById(field+"_conditional_content_div").style.visibility="hidden";
}
	
function conditional_reset(field,iteration, clear_content, cancel) {
	if (cancel) {
		hide_conditional_content(field);
	}
	document.getElementById(field+"_conditional_user_field_id_"+iteration).selectedIndex=-1;
	document.getElementById(field+"_conditional_operator_id_"+iteration).selectedIndex=-1;
	document.getElementById(field+"_conditional_needle_id_"+iteration).value="";
	document.getElementById(field+"_conditional_not_id_"+iteration).checked=false;
	if (clear_content) {
		document.getElementById(field+"_conditional_content_id").value="";
		remove_conditional_andor(field,3);
		remove_conditional_andor(field,2);
		remove_conditional_elseif(field,2);
		remove_conditional_elseif(field,1);
		remove_conditional_else(field);
	}
}
	
function conditional_andor(field,operator,iteration) {
	document.getElementById(field+"_conditional_add_andor_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_andor_enabled_id_"+iteration).value = operator;
	if (iteration == 2) document.getElementById(field+"_conditional_add_andor_3").style.display="block";
	if (iteration == 3) document.getElementById("remove_"+field+"_conditional_andor_id").style.display="none";
	document.getElementById(field+"_conditional_andor1_"+iteration).style.display="block";
	document.getElementById(field+"_conditional_"+operator+"1_"+iteration).style.display="inline";
	document.getElementById(field+"_conditional_andor2_"+iteration).style.display="block";
	document.getElementById(field+"_conditional_andor3_"+iteration).style.display="block";
}

function remove_conditional_andor(field,iteration) {
	document.getElementById(field+"_conditional_add_andor_"+iteration).style.display="block";
	document.getElementById(field+"_conditional_andor_enabled_id_"+iteration).value = 0;
	if (iteration == 2) document.getElementById(field+"_conditional_add_andor_3").style.display="none";
	if (iteration == 3) document.getElementById("remove_"+field+"_conditional_andor_id").style.display="inline";
	document.getElementById(field+"_conditional_andor1_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_AND1_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_OR1_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_andor2_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_andor3_"+iteration).style.display="none";
	conditional_reset(field,iteration);
}

function conditional_else(field) {
	document.getElementById(field+"_conditional_add_else").style.display="none";
	document.getElementById(field+"_conditional_else_div").style.display="block";
	document.getElementById(field+"_conditional_else_enabled_id").value=1;
}

function remove_conditional_else(field) {
	document.getElementById(field+"_conditional_else_div").style.display="none";
	document.getElementById(field+"_conditional_else_content_id").value="";
	document.getElementById(field+"_conditional_add_else").style.display="inline";
	document.getElementById(field+"_conditional_else_enabled_id").value=0;
}

function conditional_elseif(field,iteration) {
	document.getElementById(field+"_conditional_content_div_elseif_"+iteration).style.display="block";
	document.getElementById(field+"_conditional_add_elseif_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_elseif_enabled_id_"+iteration).value=1;
	if (iteration == 1) {
		document.getElementById(field+"_conditional_add_elseif_2").style.display="inline";
		document.getElementById("remove_"+field+"_conditional_id_elseif_1").style.display="inline";
	}
	if (iteration == 2) document.getElementById("remove_"+field+"_conditional_id_elseif_1").style.display="none";
}

function remove_conditional_elseif(field,iteration) {
	document.getElementById(field+"_conditional_content_div_elseif_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_add_elseif_"+iteration).style.display="inline";
	document.getElementById(field+"_conditional_elseif_enabled_id_"+iteration).value=0;
	if (iteration == 1) document.getElementById(field+"_conditional_add_elseif_2").style.display="none";
	if (iteration == 2) document.getElementById("remove_"+field+"_conditional_id_elseif_1").style.display="inline";
	remove_conditional_andor_elseif(field,iteration,3);
	remove_conditional_andor_elseif(field,iteration,2);
	document.getElementById(field+"_conditional_user_field_id_elseif_"+iteration+"_1").selectedIndex=-1;
	document.getElementById(field+"_conditional_operator_id_elseif_"+iteration+"_1").selectedIndex=-1;
	document.getElementById(field+"_conditional_needle_id_elseif_"+iteration+"_1").value="";
	document.getElementById(field+"_conditional_not_id_elseif_"+iteration+"_1").checked=false;
	document.getElementById(field+"_conditional_content_id_elseif_"+iteration).value="";
}
function conditional_reset_elseif(field,iteration,subiteration) {
	document.getElementById(field+"_conditional_user_field_id_elseif_"+iteration+"_"+subiteration).selectedIndex=-1;
	document.getElementById(field+"_conditional_operator_id_elseif_"+iteration+"_"+subiteration).selectedIndex=-1;
	document.getElementById(field+"_conditional_needle_id_elseif_"+iteration+"_"+subiteration).value="";
	document.getElementById(field+"_conditional_not_id_elseif_"+iteration+"_"+subiteration).checked=false;
}

function conditional_andor_elseif(field,iteration,operator,subiteration) {
	document.getElementById(field+"_conditional_add_andor_elseif_"+iteration+"_"+subiteration).style.display="none";
	document.getElementById(field+"_conditional_andor_enabled_id_elseif_"+iteration+"_"+subiteration).value = operator;
	if (subiteration == 2) document.getElementById(field+"_conditional_add_andor_elseif_"+iteration+"_3").style.display="block";
	if (subiteration == 3) document.getElementById("remove_"+field+"_conditional_andor_id_elseif_"+iteration).style.display="none";
	document.getElementById(field+"_conditional_andor1_elseif_"+iteration+"_"+subiteration).style.display="block";
	document.getElementById(field+"_conditional_"+operator+"1_elseif_"+iteration+"_"+subiteration).style.display="inline";
	document.getElementById(field+"_conditional_andor2_elseif_"+iteration+"_"+subiteration).style.display="block";
	document.getElementById(field+"_conditional_andor3_elseif_"+iteration+"_"+subiteration).style.display="block";
}

function remove_conditional_andor_elseif(field,iteration,subiteration) {
	document.getElementById(field+"_conditional_add_andor_elseif_"+iteration+"_"+subiteration).style.display="block";
	document.getElementById(field+"_conditional_andor_enabled_id_elseif_"+iteration+"_"+subiteration).value = 0;
	if (subiteration == 2) document.getElementById(field+"_conditional_add_andor_elseif_"+iteration+"_3").style.display="none";
	if (subiteration == 3) document.getElementById("remove_"+field+"_conditional_andor_id_elseif_"+iteration).style.display="inline";
	document.getElementById(field+"_conditional_andor1_elseif_"+iteration+"_"+subiteration).style.display="none";
	document.getElementById(field+"_conditional_AND1_elseif_"+iteration+"_"+subiteration).style.display="none";
	document.getElementById(field+"_conditional_OR1_elseif_"+iteration+"_"+subiteration).style.display="none";
	document.getElementById(field+"_conditional_andor2_elseif_"+iteration+"_"+subiteration).style.display="none";
	document.getElementById(field+"_conditional_andor3_elseif_"+iteration+"_"+subiteration).style.display="none";
	conditional_reset_elseif(field,iteration,subiteration);
}

function check_conditional_content(field,iteration,subiteration) {
	full_iteration = "";
	if (iteration != 0) full_iteration = "elseif_" + iteration + "_";
	andor_operator = document.getElementById(field+"_conditional_andor_enabled_id_"+full_iteration+subiteration);
	if (andor_operator.value == 0) return "";
	condition = document.getElementById(field+"_conditional_needle_id_"+full_iteration+subiteration);
	if (!condition.value) {
		alert("Please add a condition");
		condition.focus();
		return "error";
	}
	condition_user_field = document.getElementById(field+"_conditional_user_field_id_"+full_iteration+subiteration);
	condition_not = document.getElementById(field+"_conditional_not_id_"+full_iteration+subiteration).checked;
	condition_operator = document.getElementById(field+"_conditional_operator_id_"+full_iteration+subiteration);
	full_condition = "{" + andor_operator.value + " %" + condition_user_field.options[condition_user_field.selectedIndex].text + "% ";
	if (condition_not) full_condition += "NOT ";
	if (document.getElementById(field+"_conditional_needle_isdate_id_"+full_iteration+subiteration).value == 1) {
		condition_isdate = " DATE";
	} else {
		condition_isdate = "";
	}
	full_condition += condition_operator.options[condition_operator.selectedIndex].text + condition_isdate + " \"" + condition.value.replace(/\"/g,"\\\"") + "\"}";
	return full_condition;
}
function submit_conditional_content(field) {
	condition_1 = document.getElementById(field+"_conditional_needle_id_1");
	if (!condition_1.value) {
		alert("Please add the first condition");
		condition_1.focus();
		return;
	}
	conditional_content = document.getElementById(field+"_conditional_content_id");
	if (!conditional_content.value) {
		conditional_check = confirm("The conditional content is empty, click cancel to edit this.");
		if (!conditional_check) {
			conditional_content.focus();
			return;
		}
	}
	condition_user_field_1 = document.getElementById(field+"_conditional_user_field_id_1");
	condition_not_1 = document.getElementById(field+"_conditional_not_id_1").checked;
	condition_operator_1 = document.getElementById(field+"_conditional_operator_id_1");
	full_condition_1 = "{IF %" + condition_user_field_1.options[condition_user_field_1.selectedIndex].text + "% ";
	if (condition_not_1) full_condition_1 += "NOT ";
	if (document.getElementById(field+"_conditional_needle_isdate_id_1").value == 1) {
		condition_isdate_1 = " DATE";
	} else {
		condition_isdate_1 = "";
	}
	full_condition_1 += condition_operator_1.options[condition_operator_1.selectedIndex].text + condition_isdate_1 + " \"" + condition_1.value.replace(/\"/g,"\\\"") + "\"}";
	conditional_output = full_condition_1;
//check for AndOrs
	full_condition_2 = check_conditional_content(field,0,2);
	if (full_condition_2 == "error") return;
	conditional_output += full_condition_2;
	full_condition_3 = check_conditional_content(field,0,3);
	if (full_condition_3 == "error") return;
	conditional_output += full_condition_3;
//add conditional content	
	conditional_output += "{THEN \"" + conditional_content.value.replace(/\"/g,"\\\"") + "\"}";
//add elseif conditional content
	if (document.getElementById(field+"_conditional_elseif_enabled_id_1").value == 1) {
		condition_elseif_1 = document.getElementById(field+"_conditional_needle_id_elseif_1_1");
		if (!condition_elseif_1.value) {
			alert("Please add the first else if condition");
			condition_elseif_1.focus();
			return;
		}
		conditional_content_elseif_1 = document.getElementById(field+"_conditional_content_id_elseif_1");
		if (!conditional_content_elseif_1.value) {
			conditional_check = confirm("The conditional content is empty, click cancel to edit this.");
			if (!condtional_check) {
				conditional_content_elseif_1.focus();
				return;
			}
		}
		condition_user_field_elseif_1 = document.getElementById(field+"_conditional_user_field_id_elseif_1_1");
		condition_not_elseif_1 = document.getElementById(field+"_conditional_not_id_elseif_1_1").checked;
		condition_operator_elseif_1 = document.getElementById(field+"_conditional_operator_id_elseif_1_1");
		full_condition_elseif_1 = "{ELSEIF %" + condition_user_field_elseif_1.options[condition_user_field_elseif_1.selectedIndex].text + "% ";
		if (condition_not_elseif_1) full_condition_elseif_1 += "NOT ";
		if (document.getElementById(field+"_conditional_needle_isdate_id_elseif_1_1").value == 1) {
			condition_elseif_isdate_1 = " DATE";
		} else {
			condition_elseif_isdate_1 = "";
		}
		full_condition_elseif_1 += condition_operator_elseif_1.options[condition_operator_elseif_1.selectedIndex].text + condition_elseif_isdate_1 + " \"" + condition_elseif_1.value.replace(/\"/g,"\\\"") + "\"}";
		conditional_output += full_condition_elseif_1;
//check elseif for AndOrs
		full_condition_elseif_1_2 = check_conditional_content(field,1,2);
		if (full_condition_elseif_1_2 == "error") return;
		conditional_output += full_condition_elseif_1_2;
		full_condition_elseif_1_3 = check_conditional_content(field,1,3);
		if (full_condition_elseif_1_3 == "error") return;
		conditional_output += full_condition_elseif_1_3;
//add elseif conditional content	
		conditional_output += "{THEN \"" + conditional_content_elseif_1.value.replace(/\"/g,"\\\"") + "\"}";
//add the second elseif conditional content
		if (document.getElementById(field+"_conditional_elseif_enabled_id_2").value == 1) {
			condition_elseif_2 = document.getElementById(field+"_conditional_needle_id_elseif_2_1");
			if (!condition_elseif_2.value) {
				alert("Please add the second else if condition");
				condition_elseif_2.focus();
				return;
			}
			conditional_content_elseif_2 = document.getElementById(field+"_conditional_content_id_elseif_2");
			if (!conditional_content_elseif_2.value) {
				conditional_check = confirm("The conditional content is empty, click cancel to edit this.");
				if (!condtional_check) {
					conditional_content_elseif_2.focus();
					return;
				}
			}
			condition_user_field_elseif_2 = document.getElementById(field+"_conditional_user_field_id_elseif_2_1");
			condition_not_elseif_2 = document.getElementById(field+"_conditional_not_id_elseif_2_1").checked;
			condition_operator_elseif_2 = document.getElementById(field+"_conditional_operator_id_elseif_2_1");
			full_condition_elseif_2 = "{ELSEIF %" + condition_user_field_elseif_2.options[condition_user_field_elseif_2.selectedIndex].text + "% ";
			if (condition_not_elseif_2) full_condition_elseif_2 += "NOT ";
			if (document.getElementById(field+"_conditional_needle_isdate_id_elseif_2_1").value == 1) {
				condition_elseif_isdate_2 = " DATE";
			} else {
				condition_elseif_isdate_2 = "";
			}
			full_condition_elseif_2 += condition_operator_elseif_2.options[condition_operator_elseif_2.selectedIndex].text + condition_elseif_isdate_2 +" \"" + condition_elseif_2.value.replace(/\"/g,"\\\"") + "\"}";
			conditional_output += full_condition_elseif_2;
	//check elseif for AndOrs
			full_condition_elseif_2_2 = check_conditional_content(field,2,2);
			if (full_condition_elseif_2_2 == "error") return;
			conditional_output += full_condition_elseif_2_2;
			full_condition_elseif_2_3 = check_conditional_content(field,2,3);
			if (full_condition_elseif_2_3 == "error") return;
			conditional_output += full_condition_elseif_2_3;
	//add elseif conditional content	
			conditional_output += "{THEN \"" + conditional_content_elseif_2.value.replace(/\"/g,"\\\"") + "\"}";		
		}
	}
//add else conditional content
	if (document.getElementById(field+"_conditional_else_enabled_id").value == 1) {
		conditional_content_else = document.getElementById(field+"_conditional_else_content_id");
		if (!conditional_content_else.value) {
			conditional_check = confirm("The else content is empty, click cancel to edit this.");
			if (!conditional_check) {
				conditional_content_else.focus();
				return;
			}
		}
		conditional_output += "{ELSE \"" + conditional_content_else.value.replace(/\"/g,"\\\"") + "\"}";
	}
//close conditions and print to subject
	conditional_output += "{/IF}";
	editor_tools_add_tags(conditional_output,field+"_id");
	conditional_reset(field,1,1,1);
}

//send preview to new page
function submit_email_form(send_type) {
	if (send_type == 2) {
		my_win = window.open('','myWin','');
		document.getElementById("ame_email_id").target = 'myWin';
	} else {
		document.getElementById("ame_email_id").target = '_self';
	}
}

//The following code was finely crafted by the LibreForum Development team before being hacked
//into the simply functional format found here.  My thanks go out to them for their
//hard work and willingness to share.

// Strip whitespace from the start and end of a string.
function editor_tools_strip_whitespace(str, return_stripped)
{
    var strip_pre = '';
    var strip_post = '';

    // Strip whitespace from end of string.
    for (;;) {
        var lastchar = str.substring(str.length-1, str.length);
        if (lastchar == ' '  || lastchar == '\r' ||
            lastchar == '\n' || lastchar == '\t') {
            strip_post = lastchar + strip_post;

            str = str.substring(0, str.length-1);
        } else {
            break;
        }
    }

    // Strip whitespace from start of string.
    for (;;) {
        var firstchar = str.substring(0,1);
        if (firstchar == ' '  || firstchar == '\r' ||
            firstchar == '\n' || firstchar == '\t') {
            strip_pre += firstchar;
            str = str.substring(1);
        } else {
            break;
        }
    }

    if (return_stripped) {
        return new Array(str, strip_pre, strip_post);
    } else {
        return str;
    }
} 

// ----------------------------------------------------------------------
// Textarea manipulation
// ----------------------------------------------------------------------

// Add tags to the textarea. If some text is selected, then place the
// tags around the selected text. If no text is selected and a prompt_str
// is provided, then prompt the user for the data to place inside
// the tags.
function editor_tools_add_tags(replace,target)
{
    var text;
    var pretext;
    var posttext;
    var range;
    var ta = document.getElementById(target);
    if (ta == null) return;

    // Store the current scroll offset, so we can restore it after
    // adding the tags to its contents.
    var offset = ta.scrollTop;

    if (ta.setSelectionRange)
    {
        // Get the currently selected text.
        pretext = ta.value.substring(0, ta.selectionStart);
        text = ta.value.substring(ta.selectionStart, ta.selectionEnd);
        posttext = ta.value.substring(ta.selectionEnd, ta.value.length);

        // Strip whitespace from text selection and move it to the
        // pre- and post.
        var res = editor_tools_strip_whitespace(text, true);
        text = res[0];
        var pre = res[1] + pre;
        var post = post + res[2];

        ta.value = pretext + replace + posttext;

        ta.focus();
    }
    else if (document.selection) /* MSIE support */
    {
        // Get the currently selected text.
        ta.focus();
        range = document.selection.createRange();

        // Fumbling to work around newline selections at the end of
        // the text selection. MSIE does not include them in the
        // range.text, but it does replace them when setting range.text
        // to a new value :-/
        var virtlen = range.text.length;
        if (virtlen > 0) {
            while (range.text.length == virtlen) {
                range.moveEnd('character', -1);
            }
            range.moveEnd('character', +1);
        }

        // Prompt for input if no text was selected and a prompt is set.
        text = range.text;
        // Strip whitespace from text selection and move it to the
        // pre- and post.
        var res = editor_tools_strip_whitespace(text, true);
        text = res[0];

        // Add pre and post to the text.
        range.text = replace;

    }
    else /* Support for really limited browsers, e.g. MSIE5 on MacOS */
    {
        ta.value = ta.value + replace;
    }

    ta.scrollTop = offset;
}
