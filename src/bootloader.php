<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Dbcompar\Command\Diff;
use Dbcompar\Command\Sync;

$application = new Application();
$application->setName("Dbcompar");
// $application->setName("
//     _______   ______
//    //      \ //     \
//   //       ///      /
//  //       ///------/
// //_______///
//
//
// ");
$application->add(new Diff());
$application->add(new Sync());
$application->run();
