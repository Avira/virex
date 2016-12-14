<?php
if ($_POST["md5"] != "") {
    header("Location:sampleshare.php?action=getfile&user=" . $_POST["user"] . "&md5=" . $_POST["md5"]);
    die();
}
?>

<html>
    <head>
        <title>Norman SampleShare - Manual sample request</title>
    </head>
    <body>
        <form method=post action=manual.php>
            <table>
                <tr><td>Username:</td><td><input name=user type=text></td></tr>
                <tr><td>MD5:</td><td><input name=md5 type=text></td></tr>
            </table>

            <input type=submit value="Request sample">
        </form>

    </body>
</html>