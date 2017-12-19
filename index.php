<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set("memory_limit", "-1");
set_time_limit(0);
include_once('crawler.class.php');
$crawler = new Crawler();
$crawler->fetch_links(1);
$crawler->fetch_details();

print_r($crawler->data);
?>
