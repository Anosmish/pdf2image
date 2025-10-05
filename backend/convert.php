<?php
header("Access-Control-Allow-Origin: https://pdf2picture.netlify.app");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if(!extension_loaded('imagick')){
respondError('Server misconfiguration: imagick not installed', 500);
}


$im = new Imagick();
// set resolution higher for better quality if scaling up
$density = max(72, intval(72 * ($scale / 100)));
$im->setResolution($density, $density);
$im->readImage($uploadedPath);


$pageCount = $im->getNumberImages();
$images = [];


foreach(new ImagickIterator($im) as $i=>$page){
$page->setImageColorspace(Imagick::COLORSPACE_RGB);
$page->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
$page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);


// set format
if($format === 'jpg'){
$page->setImageFormat('jpeg');
$page->setImageCompression(Imagick::COMPRESSION_JPEG);
$page->setImageCompressionQuality($quality);
} else {
$page->setImageFormat('png');
}


// write to file
$outName = sprintf('%s/page-%03d.%s', $workDir, $i+1, $format === 'jpg' ? 'jpg' : 'png');
if(!$page->writeImage($outName)){
// ignore write failures for single page but log in real app
} else {
$images[] = $outName;
}
$page->clear();
$page->destroy();
}


// create zip
$zipPath = $workDir . '/images.zip';
$zip = new ZipArchive();
if($zip->open($zipPath, ZipArchive::CREATE)!==TRUE){
respondError('Failed to create ZIP archive', 500);
}
foreach($images as $img){
$zip->addFile($img, basename($img));
}
$zip->close();


// Send ZIP to client
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="pdf-images.zip"');
header('Content-Length: '.filesize($zipPath));


// flush output
readfile($zipPath);


} catch(Exception $e){
respondError('Processing error: '.$e->getMessage(), 500);
} finally {
// cleanup — remove working directory recursively
