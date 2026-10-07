<?php
$uploadDir   = 'c:/xampp/htdocs/online-book-resale/assets/uploads/';
$profilesDir = $uploadDir . 'profiles/';
$booksDir    = $uploadDir . 'books/';

if (!is_dir($uploadDir))   mkdir($uploadDir,   0755, true);
if (!is_dir($profilesDir)) mkdir($profilesDir, 0755, true);
if (!is_dir($booksDir))    mkdir($booksDir,    0755, true);

if (function_exists('imagecreatetruecolor')) {
    // Default book cover
    $img  = imagecreatetruecolor(200, 280);
    $bg   = imagecolorallocate($img, 15, 10, 30);
    $gold = imagecolorallocate($img, 212, 175, 55);
    $gray = imagecolorallocate($img, 120, 120, 140);
    imagefill($img, 0, 0, $bg);
    imagefilledrectangle($img, 0, 0, 12, 280, $gold);
    imagestring($img, 5, 50, 120, 'No Image', $gray);
    imagestring($img, 3, 40, 150, 'BookResale', $gold);
    imagepng($img, $uploadDir . 'default_book.png');
    imagedestroy($img);

    // Default user avatar
    $img2  = imagecreatetruecolor(150, 150);
    $bg2   = imagecolorallocate($img2, 26, 18, 51);
    $gray2 = imagecolorallocate($img2, 100, 100, 120);
    imagefill($img2, 0, 0, $bg2);
    imagefilledellipse($img2, 75, 75, 140, 140, $gray2);
    imagefilledellipse($img2, 75, 55, 60, 60, $bg2);
    imagefilledellipse($img2, 75, 130, 100, 80, $bg2);
    imagepng($img2, $uploadDir . 'default_user.png');
    imagedestroy($img2);
    echo "GD: Created default_book.png and default_user.png\n";
} else {
    echo "GD not available - creating blank PNG\n";
    // Minimal 1x1 transparent PNG
    $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    file_put_contents($uploadDir . 'default_book.png', $data);
    file_put_contents($uploadDir . 'default_user.png', $data);
}

echo "uploads dir:      " . (is_dir($uploadDir)   ? 'OK' : 'MISSING') . "\n";
echo "profiles dir:     " . (is_dir($profilesDir) ? 'OK' : 'MISSING') . "\n";
echo "books dir:        " . (is_dir($booksDir)    ? 'OK' : 'MISSING') . "\n";
echo "default_book.png: " . (file_exists($uploadDir . 'default_book.png') ? 'EXISTS (' . filesize($uploadDir . 'default_book.png') . ' bytes)' : 'MISSING') . "\n";
echo "default_user.png: " . (file_exists($uploadDir . 'default_user.png') ? 'EXISTS (' . filesize($uploadDir . 'default_user.png') . ' bytes)' : 'MISSING') . "\n";
