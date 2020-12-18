<?php
$content = $_GET['content'];
$lang = $_GET['lang'];

$content = trim($content);
$content = trim(preg_replace('/"/', "", $content));

$sql = new PDO("mysql://root:root@localhost/myhordes", "root", "root");

file_put_contents("/home/ludo/devs/quotes.$lang.txt", "$content\n", FILE_APPEND);