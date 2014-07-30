<?php

require "../liftoff.php";

use app\workers\Messages;

Messages::push("alert", "You have another alertacious problem!");
Messages::push("info", "This is some very good information.");

Messages::push("alert", "Uh oh! There is things wrong!");

echo "<pre>";
print_r(Messages::pull());
echo "</pre>";

