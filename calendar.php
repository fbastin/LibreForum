<?php
    define('phorum_page','calendar');

    include_once('./common.php');
    include_once("./include/email_functions.php");
    include_once("./include/format_functions.php");

    // set all our URL's
    phorum_build_common_urls();

    include phorum_get_template('header');
    phorum_hook('after_header');
?>
<iframe src="https://www.google.com/calendar/embed?title=LibreForum&amp;height=600&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src=1b6pkrg92qnbd4bmgu8j7fc3lg%40group.calendar.google.com&amp;color=%235229A3&amp;src=pvqknhcjt80g6dl0h74l4g3mg8%40group.calendar.google.com&amp;color=%230D7813&amp;src=example.com%40gmail.com&amp;color=%232952A3&amp;ctz=Europe%2FBrussels" style=" border-width:0 " width="800" height="600" frameborder="0" scrolling="no">
<?php
    phorum_hook('before_footer');
    include phorum_get_template('footer');
?>
