<?php

// Create high-res 128x128 image with dark background and anti-aliasing
$width = 128;
$height = 128;
$im = imagecreatetruecolor($width, $height);
imagealphablending($im, true);
imagesavealpha($im, true);

// Transparent background
$trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefill($im, 0, 0, $trans);

// Colors
$darkBg = imagecolorallocate($im, 12, 19, 21); // #0C1315
$brandGreen = imagecolorallocate($im, 0, 200, 150); // #00C896
$white = imagecolorallocate($im, 255, 255, 255); // #FFFFFF
$sage = imagecolorallocate($im, 208, 219, 213); // #D0DBD5

// Draw rounded background squircle
function drawFilledRoundedRect($im, $x1, $y1, $x2, $y2, $radius, $color) {
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

// Background
drawFilledRoundedRect($im, 0, 0, 127, 127, 28, $darkBg);

// Top-Left Dot
drawFilledRoundedRect($im, 23, 23, 34, 34, 4, $brandGreen);

// Bottom-Right Dot
drawFilledRoundedRect($im, 93, 93, 104, 104, 4, $brandGreen);

// Top S section (White)
drawFilledRoundedRect($im, 35, 30, 93, 45, 7, $white);
drawFilledRoundedRect($im, 35, 30, 48, 68, 6, $white);

// Bottom S section (Sage)
drawFilledRoundedRect($im, 35, 83, 93, 98, 7, $sage);
drawFilledRoundedRect($im, 80, 60, 93, 98, 6, $sage);

// Middle Pill (#00C896)
drawFilledRoundedRect($im, 41, 55, 87, 73, 9, $brandGreen);

// Save PNG
imagepng($im, __DIR__ . '/../public/favicon.png');
imagepng($im, __DIR__ . '/../public/favicon.ico');

imagedestroy($im);
echo "Favicon generated successfully!\n";
