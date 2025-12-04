<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    public function generate()
    {
        $code = strtoupper(Str::random(5));
        session(['captcha_code' => $code]);

        $width = 120;
        $height = 40;
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $bg = imagecolorallocate($image, 240, 240, 240);
        $text_color = imagecolorallocate($image, 50, 50, 50);
        $line_color = imagecolorallocate($image, 200, 200, 200);

        imagefill($image, 0, 0, $bg);

        // Add some noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, 0, rand() % $height, $width, rand() % $height, $line_color);
        }

        // Add dots
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($image, rand() % $width, rand() % $height, $line_color);
        }

        // Add text
        imagestring($image, 5, 35, 12, $code, $text_color);

        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
        exit;
    }
}
