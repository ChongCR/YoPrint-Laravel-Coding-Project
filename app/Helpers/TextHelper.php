<?php

if (!function_exists('clean_text')) {
    function clean_text(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove non-UTF-8 characters
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Trim whitespace
        return trim($text);
    }
}