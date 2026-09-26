<?php
// LibreForum — https://github.com/fbastin/LibreForum
// Copyright 2026 Fabian Bastin and the LibreForum contributors
// SPDX-License-Identifier: Apache-2.0
//
// Content of the formatting guide (addon.php?module=markdown&action=guide).
// Every example goes through the forum's formatting chain when the page is
// displayed: the "Result" column shows what the forum really produces. An
// example marked 'show' => FALSE is not rendered (video or external image:
// the guide page loads nothing from elsewhere).

return array(
    'title'   => 'Formatting guide',
    'intro'   => 'Messages are written in <strong>Markdown</strong>: a few simple signs, typed in the text, to format it. The buttons of the editor toolbar insert these same signs. Every example below is formatted by the forum itself: the result shown is exactly what you get in a message.',
    'contents' => 'Contents',
    'syntax'  => 'You type',
    'result'  => 'Result',
    'not_rendered' => 'Not shown here',
    'back'    => 'Close this window',

    'sections' => array(
        array('text', 'Text', '', array(
            array('Bold', '**bold text**'),
            array('Italic', '*italic text*'),
            array('Bold and italic', '***both***'),
            array('Strikethrough', '~~struck text~~'),
            array('Underline', '<u>underlined text</u>'),
            array('Superscript and subscript', 'E = mc<sup>2</sup>, H<sub>2</sub>O'),
            array('Small text', '<small>smaller text</small>'),
            array('Colour', '<span style="color: #c0392b">red text</span>'),
            array('Size', '<span style="font-size: large">larger text</span>'),
        )),
        array('paragraphs', 'Paragraphs and lines', 'A line break goes to the next line; an empty line starts a new paragraph.', array(
            array('Lines and paragraphs', "First line\nSecond line\n\nNew paragraph"),
            array('Centre', '<center>centred text</center>'),
            array('Horizontal rule', "Above\n\n---\n\nBelow"),
        )),
        array('headings', 'Headings', 'One to six hash signs at the start of a line, followed by a space.', array(
            array('Large heading', '# Large heading'),
            array('Medium heading', '## Medium heading'),
            array('Small heading', '### Small heading'),
        )),
        array('quotes', 'Quotes', 'A ">" at the start of a line. The "Quote" button of a message does it for you. The quote ends at the first line that does not start with ">": your reply can follow directly. To go back from a nested quote to the level above, leave a line containing only ">".', array(
            array('Quote', "> Quoted text\nYour reply"),
            array('Quote within a quote', "> > Original message\n>\n> First reply\n\nYour reply"),
        )),
        array('lists', 'Lists', 'A dash, an asterisk or a number followed by a period, at the start of a line.', array(
            array('Bulleted list', "- first item\n- second item\n  - sub-item (two spaces before)"),
            array('Numbered list', "1. first\n2. second\n3. third"),
        )),
        array('links', 'Links', 'A web address written as is becomes clickable. Addresses that would run a script (javascript:) are neutralised.', array(
            array('Address alone', 'https://example.org'),
            array('Link with a text', '[the link text](https://example.org)'),
            array('E-mail address', '[write](mailto:name@example.org)'),
        )),
        array('images', 'Images and videos', 'The image must be online, at a public address. For a video, the address alone on its line is enough; the Video button of the editor inserts it too.', array(
            array('Image', '![description of the image](https://example.org/photo.jpg)', 'show' => FALSE),
            array('Clickable image', '[![description](https://example.org/thumbnail.jpg)](https://example.org/photo.jpg)', 'show' => FALSE),
            array('YouTube or Vimeo video', "The address of the video, alone on its line:\n\nhttps://www.youtube.com/watch?v=XXXXXXXXXXX", 'show' => FALSE),
        )),
        array('code', 'Code', 'The text of a code block is shown as is, without formatting.', array(
            array('Code in a sentence', 'The command `ls -l` lists the files.'),
            array('Code block', "```\nif (a < b && b > 0) {\n    **no bold here**\n}\n```"),
        )),
        array('tables', 'Tables', 'Columns are separated by vertical bars; the second line separates the header. A colon on the right aligns the column to the right.', array(
            array('Table', "| Name | Value |\n| --- | ---: |\n| first | 12 |\n| second | 3.5 |"),
        )),
        array('escaping', 'Writing a sign without its effect', 'A backslash before the sign shows it as is.', array(
            array('Visible asterisks', '\\*not italic\\*'),
            array('">" at the start of a line', '\\> not a quote'),
        )),
    ),

    'emoji'         => 'Emojis',
    'emoji_intro'   => 'Unicode emojis (😀 👍 🎯) are accepted in messages and subjects. To open the emoji picker:',
    'emoji_windows' => 'Windows: Windows key + <code>.</code> (period)',
    'emoji_mac'     => 'Mac: <code>Ctrl</code> + <code>Cmd</code> + <code>Space</code>',
    'emoji_phone'   => 'Phone and tablet: the 😀 key on the keyboard',
);
