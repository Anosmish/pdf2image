<?php
header("Access-Control-Allow-Origin: https://pdf2picture.netlify.app");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function respondError($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

// Enable PHP error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fontconfig
putenv('FONTCONFIG_PATH=' . sys_get_temp_dir());
putenv('HOME=' . sys_get_temp_dir());

if (!extension_loaded('imagick')) {
    respondError('Imagick not installed', 500);
}

$imCheck = new Imagick();
if (!in_array('PDF', $imCheck->queryFormats())) {
    respondError('Imagick does not support PDF. Ghostscript may be missing.', 500);
}

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    respondError('No PDF file uploaded or upload error', 400);
}

$uploadedPath = $_FILES['pdf']['tmp_name'];
if (!file_exists($uploadedPath)) {
    respondError("Uploaded PDF not found at $uploadedPath", 400);
}
error_log("Uploaded file path: $uploadedPath");

$scale = isset($_POST['scale']) ? intval($_POST['scale']) : 100;
$format = (isset($_POST['format']) && strtolower($_POST['format']) === 'png') ? 'png' : 'jpg';
$quality = isset($_POST['quality']) ? intval($_POST['quality']) : 90;

$workDir = sys_get_temp_dir() . '/pdfconvert_' . uniqid();
if (!mkdir($workDir, 0777, true) && !is_dir($workDir)) {
    respondError('Failed to create temporary directory: '.$workDir, 500);
}
chmod($workDir, 0777);

try {
    $im = new Imagick();
    $density = max(72, intval(72 * ($scale / 100)));
    $im->setResolution($density, $density);
    $im->readImage($uploadedPath);
    $pageCount = $im->getNumberImages();
    error_log("Number of pages detected: $pageCount");

    if ($pageCount === 0) {
        respondError('PDF has no pages or cannot be read.', 500);
    }

    $images = [];
    foreach (new ImagickIterator($im) as $i => $page) {
        $page->setImageColorspace(Imagick::COLORSPACE_RGB);
        $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        if ($format === 'jpg') {
            $page->setImageFormat('jpeg');
            $page->setImageCompression(Imagick::COMPRESSION_JPEG);
            $page->setImageCompressionQuality($quality);
        } else {
            $page->setImageFormat('png');
        }

        $outName = sprintf('%s/page-%03d.%s', $workDir, $i + 1, $format === 'jpg' ? 'jpg' : 'png');

        if (!$page->writeImage($outName)) {
            error_log("Failed to write image: $outName");
        } else {
            $images[] = $outName;
            error_log("Written image: $outName");
        }

        $page->clear();
        $page->destroy();
    }

    if (empty($images)) {
        respondError('No images generated. Check PDF and Ghostscript.', 500);
    }

    $zipPath = $workDir . '/images.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        respondError('Failed to create ZIP archive', 500);
    }
    foreach ($images as $img) {
        $zip->addFile($img, basename($img));
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="pdf-images.zip"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);

} catch (Exception $e) {
    respondError('Processing error: ' . $e->getMessage(), 500);
} finally {
    foreach (glob("$workDir/*") as $file) {
        @unlink($file);
    }
    @rmdir($workDir);
}
