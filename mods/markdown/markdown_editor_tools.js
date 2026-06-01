///////////////////////////////////////////////////////////////////////////////
//                                                                           //
// Copyright (C) 2010  Phorum Development Team                               //
// http://www.phorum.org                                                     //
//                                                                           //
// This program is free software. You can redistribute it and/or modify      //
// it under the terms of either the current Phorum License (viewable at      //
// phorum.org) or the Phorum License that was distributed with this file     //
//                                                                           //
// This program is distributed in the hope that it will be useful,           //
// but WITHOUT ANY WARRANTY, without even the implied warranty of            //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      //
//                                                                           //
// You should have received a copy of the Phorum License                     //
// along with this program.                                                  //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////

// JavaScript code for Markdown support in the Phorum editor_tools module.
// This script overrides the default BBCode behavior.

var editor_tools_size_picker_obj = null;
var editor_tools_list_picker_obj = null;

var editor_tools_size_picker_sizes = new Array(
    'x-large',
    'large',
    'medium',
    'small',
    'x-small'
);

var editor_tools_list_picker_types = new Array(
    'b', // bullets
    '1'  // numbers
);

function editor_tools_handle_hr() {
    editor_tools_add_tags('\n---\n', '');
    editor_tools_focus_textarea();
}

function editor_tools_handle_b() {
    editor_tools_add_tags('**', '**');
    editor_tools_focus_textarea();
}

function editor_tools_handle_s() {
    editor_tools_add_tags('~~', '~~');
    editor_tools_focus_textarea();
}

function editor_tools_handle_u() {
    editor_tools_add_tags('<u>', '</u>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_i() {
    editor_tools_add_tags('*', '*');
    editor_tools_focus_textarea();
}

function editor_tools_handle_center() {
    editor_tools_add_tags('<center>', '</center>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_sub() {
    editor_tools_add_tags('<sub>', '</sub>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_sup() {
    editor_tools_add_tags('<sup>', '</sup>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_small() {
    editor_tools_add_tags('<small>', '</small>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_large() {
    editor_tools_add_tags('<span style="font-size: large">', '</span>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_code() {
    editor_tools_add_tags('```\n', '\n```\n');
    editor_tools_focus_textarea();
}

function editor_tools_handle_email()
{
    var email = prompt(editor_tools_translate("enter email"), '');
    if (email == null) return;
    email = editor_tools_strip_whitespace(email);

    if (email == '') {
        editor_tools_add_tags('<', '>');
    } else {
        editor_tools_add_tags('[' + email + '](mailto:' + email + ')', '');
    }

    editor_tools_focus_textarea();
}

function editor_tools_handle_url()
{
    var url = 'http://';

    for (;;)
    {
        url = prompt(editor_tools_translate("enter url"), url);
        if (url == '' || url == null) return;
        url = editor_tools_strip_whitespace(url);

        copy = url.toLowerCase();
        if (copy == 'http://' || (
            copy.substring(0,7) != 'http://' &&
            copy.substring(0,8) != 'https://' &&
            copy.substring(0,6) != 'ftp://' &&
            copy.substring(0,7) != 'mailto:')) {
            alert(editor_tools_translate("invalid url"));
            continue;
        }

        break;
    }

    editor_tools_add_tags('[', '](' + url + ')', null, editor_tools_translate("enter url description"));
    editor_tools_focus_textarea();
}

function editor_tools_handle_color()
{
    editor_tools_store_range();
    var img_obj = document.getElementById('editor-tools-img-color');
    showColorPicker(img_obj);
    return;
}

function editor_tools_handle_color_select(color)
{
    editor_tools_restore_range();
    editor_tools_add_tags('<span style="color:' + color + '">', '</span>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_size()
{
    editor_tools_store_range();
    if (!editor_tools_size_picker_obj)
    {
        var popup = editor_tools_construct_popup('editor-tools-size-picker','l');
        editor_tools_size_picker_obj = popup[0];
        var content_obj = popup[1];
        for (var i = 0; i < editor_tools_size_picker_sizes.length; i++)
        {
            var size = editor_tools_size_picker_sizes[i];
            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_size_select("' + size + '")';
            a_obj.style.fontSize = size;
            a_obj.innerHTML = editor_tools_translate(size);
            content_obj.appendChild(a_obj);
            var br_obj = document.createElement('br');
            content_obj.appendChild(br_obj);
        }
        editor_tools_register_popup_object(editor_tools_size_picker_obj);
    }
    var button_obj = document.getElementById('editor-tools-img-size');
    editor_tools_toggle_popup(editor_tools_size_picker_obj, button_obj);
}

function editor_tools_handle_size_select(size)
{
    editor_tools_hide_all_popups();
    editor_tools_restore_range();
    size = editor_tools_strip_whitespace(size);
    editor_tools_add_tags('<span style="font-size:' + size + '">', '</span>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_img()
{
    var url = 'http://';
    for (;;)
    {
        url = prompt(editor_tools_translate("enter image url"), url);
        if (url == '' || url == null) return;
        url = editor_tools_strip_whitespace(url);
        var copy = url.toLowerCase();
        if (copy == 'http://' || (
            copy.substring(0,7) != 'http://' &&
            copy.substring(0,8) != 'https://' &&
            copy.substring(0,6) != 'ftp://')) {
            alert(editor_tools_translate("invalid image url"));
            continue;
        }
        break;
    }
    editor_tools_add_tags('![](' + url + ')', '');
    editor_tools_focus_textarea();
}

function editor_tools_handle_quote()
{
    var who = prompt(editor_tools_translate("enter who you quote"), '');
    if (who == null) return;
    who = editor_tools_strip_whitespace(who);
    if (who == '') {
        editor_tools_add_tags('> ', '');
    }
    else
    {
        editor_tools_add_tags('> **' + who + "** wrote:\n> ", "");
    }
    editor_tools_focus_textarea();
}

function editor_tools_handle_left() {
    editor_tools_add_tags('<div align="left">\n', '\n</div>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_right() {
    editor_tools_add_tags('<div align="right">\n', '\n</div>');
    editor_tools_focus_textarea();
}

function editor_tools_handle_list()
{
    if (!editor_tools_list_picker_obj)
    {
        var popup = editor_tools_construct_popup('editor-tools-list-picker', 'l');
        editor_tools_list_picker_obj = popup[0];
        var content_obj = popup[1];
        var wrapper = document.createElement('div');
        wrapper.style.marginLeft = '1em';
        for (var i = 0; i < editor_tools_list_picker_types.length; i++)
        {
            var type = editor_tools_list_picker_types[i];
            var list = (type == 'b') ? document.createElement('ul') : document.createElement('ol');
            list.style.padding = 0;
            list.style.margin = 0;
            var item = document.createElement('li');
            var a_obj = document.createElement('a');
            a_obj.href = 'javascript:editor_tools_handle_list_select("' + type + '")';
            a_obj.innerHTML = editor_tools_translate('list type ' + type);
            item.appendChild(a_obj);
            list.appendChild(item);
            wrapper.appendChild(list);
        }
        content_obj.appendChild(wrapper);
        editor_tools_register_popup_object(editor_tools_list_picker_obj);
    }
    var button_obj = document.getElementById('editor-tools-img-list');
    editor_tools_toggle_popup(editor_tools_list_picker_obj, button_obj);
}

function editor_tools_handle_list_select(type)
{
    editor_tools_hide_all_popups();
    var items = new Array();
    var idx = 0;
    for (;;)
    {
        var item = prompt(editor_tools_translate('enter new list item'), '');
        if (item == null) return;
        item = editor_tools_strip_whitespace(item);
        if (item == '') break;
        items[idx++] = item;
    }
    if (items.length == 0) items = new Array('...', '...');
    var itemlist = '';
    for (var i = 0; i < items.length; i++) {
        var prefix = (type == 'b') ? '* ' : (i+1) + '. ';
        itemlist += prefix + items[i] + "\n";
    }
    editor_tools_add_tags("\n"+itemlist+"\n", '');
}
