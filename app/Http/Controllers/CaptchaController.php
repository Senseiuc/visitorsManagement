<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    public function generate()
    {
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $result = $num1 + $num2;
        
        session(['captcha_code' => (string) $result]);

        return response()->json([
            'question' => "$num1 + $num2 = ?"
        ]);
    }
}
