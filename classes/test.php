<?php

use MdEditApi\Directory;
use MdEditApi\File;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS enable
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
// header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

require_once 'directory.class.php';
require_once 'file.class.php';

// Create directories
$dir = new Directory();
$r = $dir->createDirectory('files');
echo $r['success'] . '-' . $r['message'] . PHP_EOL;
$r = $dir->createDirectory('files/files1');
echo $r['success'] . '-' . $r['message'] . PHP_EOL;
$r = $dir->createDirectory('files/files2');
echo $r['success'] . '-' . $r['message'] . PHP_EOL;
// Rename directory
$r = $dir->moveDirectory('files2', 'files3', 'files/');
echo $r['success'] . '-' . $r['message'] . PHP_EOL;
// Remove directory
$r = $dir->removeDirectory('files/files3');
echo $r['success'] . '-' . $r['message'] . PHP_EOL;


// Create files
$file = new File();
$r = $file->saveFile('Content test 1', 'files/file1.txt');
print_r($r);
$r = $file->saveFile('Content test 2', 'files/file2.txt');
print_r($r);
$r = $file->saveFile('Content test 3', 'files/files1/file3.txt');
print_r($r);
$r = $file->saveFile('Content test 4', 'files/files4/file4.txt');
print_r($r);
// Copy file
$r = $file->copyFile('files/file2.txt', 'files/file2b.txt');
print_r($r);
// Move file
$r = $file->moveFile('file1.txt', 'file1b.txt', 'files/');
print_r($r);
// Delete file
$r = $file->deleteFile('files/file2.txt');
print_r($r);


// List files
$r = $dir->getFiles('files');
var_dump($r);
