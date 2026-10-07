<?php
// fix_images.php - Run once to create proper default PNG images
// DELETE this file after running

$uploadDir = __DIR__ . '/assets/uploads/';

if (!extension_loaded('gd')) {
    die('GD extension not available. Please enable it in php.ini');
}

// ===== Create default_book.png (200x280) =====
$img = imagecreatetruecolor(200, 280);
$bg    = imagecolorallocate($img, 26, 18, 51);      // dark purple
$gold  = imagecolorallocate($img, 212, 175, 55);    // gold
$gray  = imagecolorallocate($img, 140, 140, 160);   // muted
$white = imagecolorallocate($img, 220, 220, 240);   // near-white

// Background
imagefill($img, 0, 0, $bg);

// Book spine
imagefilledrectangle($img, 0, 0, 12, 279, $gold);

// Border
imagerectangle($img, 0, 0, 199, 279, $gold);

// Book icon lines (simulate book pages)
imagefilledrectangle($img, 50, 80, 150, 85, $gray);
imagefilledrectangle($img, 50, 95, 150, 100, $gray);
imagefilledrectangle($img, 50, 110, 130, 115, $gray);
imagefilledrectangle($img, 50, 125, 140, 130, $gray);
imagefilledrectangle($img, 50, 140, 120, 145, $gray);

// Text
imagestring($img, 4, 45, 165, 'No Image', $gold);
imagestring($img, 2, 38, 185, 'BookResale.com', $gray);

imagepng($img, $uploadDir . 'default_book.png');
imagedestroy($img);
echo "default_book.png created (" . filesize($uploadDir . 'default_book.png') . " bytes)\n";

// ===== Create default_user.png (120x120) =====
$img2  = imagecreatetruecolor(120, 120);
$bg2   = imagecolorallocate($img2, 26, 18, 51);
$gold2 = imagecolorallocate($img2, 212, 175, 55);
$gray2 = imagecolorallocate($img2, 140, 140, 160);

imagefill($img2, 0, 0, $bg2);

// Outer circle border
imageellipse($img2, 60, 60, 118, 118, $gold2);

// Head circle
imagefilledellipse($img2, 60, 42, 36, 36, $gray2);

// Body arc (shoulders)
imagefilledarc($img2, 60, 105, 72, 60, 180, 360, $gray2, IMG_ARC_PIE);

imagepng($img2, $uploadDir . 'default_user.png');
imagedestroy($img2);
echo "default_user.png created (" . filesize($uploadDir . 'default_user.png') . " bytes)\n";

echo "\nAll default images created successfully!\n";
echo "Delete this file (fix_images.php) after running.\n";
