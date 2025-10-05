<?php
// -------------------- CORS --------------------
header("Access-Control-Allow-Origin: https://pdf2picture.netlify.app");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------- Error Handling --------------------
function respondError($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message, 'debug' => [
        'files_received' => isset($_FILES) ? array_keys($_FILES) : 'none',
        'post_received' => isset($_POST) ? array_keys($_POST) : 'none',
        'file_errors' => isset($_FILES['pdf']) ? $_FILES['pdf']['error'] : 'no file'
    ]]);
    exit;
}

// -------------------- Enable error logging --------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// -------------------- Check Required Extensions --------------------
if (!extension_loaded('imagick')) {
    respondError('Imagick extension not loaded', 500);
}

if (!extension_loaded('zip')) {
    respondError('Zip extension not loaded. Please install php-zip extension.', 500);
}

try {
    $imCheck = new Imagick();
    $formats = $imCheck->queryFormats();
    if (!in_array('PDF', $formats)) {
        respondError('PDF format not supported by Imagick. Ghostscript may be missing.', 500);
    }
} catch (Exception $e) {
    respondError('Imagick test failed: ' . $e->getMessage(), 500);
}

// Set temporary directory for fontconfig
$tempDir = sys_get_temp_dir() . '/pdf_convert_' . uniqid();
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true)) {
    respondError('Failed to create temp directory', 500);
}

putenv('FONTCONFIG_PATH=' . $tempDir);
putenv('HOME=' . $tempDir);

// -------------------- Validate uploaded PDF --------------------
if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File too large (php.ini limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
        UPLOAD_ERR_PARTIAL => 'File upload was partial',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
        UPLOAD_ERR_EXTENSION => 'PHP extension stopped upload'
    ];
    
    $errorCode = $_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMessage = $errorMessages[$errorCode] ?? "Unknown upload error ($errorCode)";
    respondError("Upload failed: $errorMessage", 400);
}

$uploadedPath = $_FILES['pdf']['tmp_name'];
$uploadedName = $_FILES['pdf']['name'];
$uploadedSize = $_FILES['pdf']['size'];

error_log("File upload details - Name: $uploadedName, Size: $uploadedSize, Temp path: $uploadedPath");

if (!file_exists($uploadedPath)) {
    respondError("Uploaded file not found at temporary path", 400);
}

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $uploadedPath);
finfo_close($finfo);

if ($mimeType !== 'application/pdf' && $mimeType !== 'application/octet-stream') {
    respondError("Invalid file type: $mimeType. Only PDF files are allowed.", 400);
}

// Validate file size (max 10MB)
if ($uploadedSize > 10 * 1024 * 1024) {
    respondError("File too large. Maximum size is 10MB.", 400);
}

// -------------------- POST parameters --------------------
$scale = isset($_POST['scale']) ? intval($_POST['scale']) : 100;
$format = (isset($_POST['format']) && strtolower($_POST['format']) === 'png') ? 'png' : 'jpg';
$quality = isset($_POST['quality']) ? intval($_POST['quality']) : 90;

// Validate parameters
$scale = max(10, min(200, $scale)); // Clamp between 10% and 200%
$quality = max(10, min(100, $quality)); // Clamp between 10 and 100

// -------------------- Temporary working directory --------------------
$workDir = sys_get_temp_dir() . '/pdfconvert_' . uniqid();
if (!mkdir($workDir, 0777, true)) {
    respondError('Failed to create working directory', 500);
}
chmod($workDir, 0777);

// -------------------- PDF to images --------------------
try {
    $im = new Imagick();
    
    // Set resolution based on scale
    $density = max(72, intval(72 * ($scale / 100)));
    $im->setResolution($density, $density);
    
    // Read PDF with error handling
    try {
        $im->readImage($uploadedPath);
    } catch (Exception $e) {
        respondError('Failed to read PDF: ' . $e->getMessage(), 400);
    }
    
    $pageCount = $im->getNumberImages();
    error_log("Number of pages detected: $pageCount");
    
    if ($pageCount === 0) {
        respondError('PDF appears to be empty or corrupted', 400);
    }

    $images = [];
    
    // Process each page
    for ($i = 0; $i < $pageCount; $i++) {
        $im->setIteratorIndex($i);
        $page = $im->getImage();
        
        // Convert to RGB and remove alpha channel
        $page->setImageColorspace(Imagick::COLORSPACE_SRGB);
        if ($page->getImageAlphaChannel()) {
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }
        
        // Set image format and quality
        if ($format === 'jpg') {
            $page->setImageFormat('jpeg');
            $page->setImageCompression(Imagick::COMPRESSION_JPEG);
            $page->setImageCompressionQuality($quality);
            $page->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        } else {
            $page->setImageFormat('png');
            $page->setCompression(Imagick::COMPRESSION_ZIP);
        }
        
        $outName = sprintf('%s/page-%03d.%s', $workDir, $i + 1, $format);
        error_log("Writing image: $outName");
        
        if (!$page->writeImage($outName)) {
            throw new Exception("Failed to write image: $outName");
        }
        
        $images[] = $outName;
        $page->clear();
        
        error_log("Successfully created: $outName");
    }
    
    $im->clear();
    $im->destroy();

    if (empty($images)) {
        respondError('No images were generated from the PDF', 500);
    }

    // -------------------- ZIP creation --------------------
    $zipPath = $workDir . '/images.zip';
    
    // Check if ZipArchive is available
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive class not found. PHP Zip extension is missing.');
    }
    
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Cannot create ZIP file');
    }
    
    foreach ($images as $img) {
        if (file_exists($img)) {
            $zip->addFile($img, basename($img));
        }
    }
    
    $zip->close();
    
    if (!file_exists($zipPath)) {
        throw new Exception('ZIP file was not created');
    }
    
    error_log("ZIP created successfully: $zipPath, Size: " . filesize($zipPath));

    // -------------------- Send ZIP to client --------------------
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="converted-images.zip"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zipPath);
    flush();
    
} catch (Exception $e) {
    respondError('Processing error: ' . $e->getMessage(), 500);
} finally {
    // Cleanup
    if (isset($images)) {
        foreach ($images as $img) {
            if (file_exists($img)) @unlink($img);
        }
    }
    if (isset($zipPath) && file_exists($zipPath)) @unlink($zipPath);
    if (is_dir($workDir)) {
        foreach (glob("$workDir/*") as $file) @unlink($file);
        @rmdir($workDir);
    }
    if (is_dir($tempDir)) {
        foreach (glob("$tempDir/*") as $file) @unlink($file);
        @rmdir($tempDir);
    }
}
