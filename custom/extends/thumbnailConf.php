<?php
/**
 * Thumbnail Configuration
 * Settings for thumbnail generation
 */

// Thumbnail options
$THUMBNAIL_OPTIONS = array(
    // File path for thumbnails (relative to project root)
    'filePath' => 'file/thumbs/',

    // Whether to use absolute paths for thumbnail URLs
    'useAbsolutePath' => false,

    // Default quality for JPEG thumbnails (1-100)
    'jpegQuality' => 90,

    // Default quality for PNG thumbnails (0-9)
    'pngQuality' => 9,

    // Maximum source image size to process (in pixels)
    'maxSourceWidth' => 4000,
    'maxSourceHeight' => 4000,

    // Cache duration in seconds
    'cacheDuration' => 86400, // 24 hours
);

// Make it globally accessible
global $THUMBNAIL_OPTIONS;
?>
