<?php
// Serve a default book cover SVG as PNG-compatible placeholder
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg width="200" height="280" viewBox="0 0 200 280" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#1a1233"/>
      <stop offset="100%" stop-color="#0f0a1e"/>
    </linearGradient>
  </defs>
  <rect width="200" height="280" fill="url(#bg)" rx="8"/>
  <rect x="0" y="0" width="12" height="280" fill="#d4af37" rx="2"/>
  <text x="106" y="130" font-family="Arial,sans-serif" font-size="52" fill="#d4af37" text-anchor="middle">📚</text>
  <text x="106" y="165" font-family="Arial,sans-serif" font-size="13" fill="rgba(255,255,255,0.5)" text-anchor="middle">No Image</text>
  <text x="106" y="185" font-family="Arial,sans-serif" font-size="11" fill="rgba(255,255,255,0.3)" text-anchor="middle">BookResale</text>
</svg>
