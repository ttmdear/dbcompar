<?php
$srcRoot = __DIR__."/..";
$buildRoot = __DIR__."/../bin";

$phar = new Phar($buildRoot . "/dbcompar.phar", 0, "dbcompar.phar");
$files = $phar->buildFromDirectory("$srcRoot",'/.php$/');

$phar->setStub($phar->createDefaultStub('/src/bootloader.php'));
