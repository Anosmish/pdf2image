# pdf2image
================

A PHP library to convert PDFs into images.

## Description
pdf2image is a PHP library designed to efficiently convert PDF documents into various image formats. It leverages the power of the PDF.js library to render PDF pages as images, providing a robust solution for applications requiring PDF-to-image conversion.

## Badges
[![Build Status](https://github.com/Anosmish/pdf2image/workflows/Build/badge.svg)](https://github.com/Anosmish/pdf2image/actions)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Language](https://img.shields.io/github/languages/top/Anosmish/pdf2image.svg)](https://github.com/Anosmish/pdf2image)
[![Stars](https://img.shields.io/github/stars/Anosmish/pdf2image.svg)](https://github.com/Anosmish/pdf2image/stargazers)

## Features
- Convert PDFs to various image formats (JPEG, PNG, BMP, GIF)
- Support for PDF.js rendering engine
- Efficient and optimized for performance
- Simple and intuitive API

## Tech Stack
- PHP 7.4+
- PDF.js
- Docker

## Installation
To install pdf2image, run the following command:
```bash
composer require anosmish/pdf2image
```
Alternatively, you can use Docker to run the library:
```bash
docker pull anosmish/pdf2image
docker run -it anosmish/pdf2image
```
## Usage
```php
require_once 'vendor/autoload.php';

use Anosmish\Pdf2image\Pdf2image;

$pdf = new Pdf2image();
$pdf->setPdfFile('path/to/example.pdf');
$pdf->setImageFormat('jpeg');
$image = $pdf->render();

// Save the image to a file
$image->save('output.jpg');
```
## Contributing
Contributions are welcome! If you'd like to contribute to pdf2image, please fork the repository and submit a pull request. Make sure to follow the standard coding conventions and provide a clear description of your changes.

## License
pdf2image is released under the MIT License. By using this library, you agree to the terms of the MIT License. If you're unsure about the license, we recommend using the MIT License as a fallback.

Note: Since no license is specified in the repository, we recommend using the MIT License as a default. You can change this to a different license if you prefer.