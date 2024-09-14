<?php
// includes/functions.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function get_all_images() {
    $args = [
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'post_status'    => 'inherit',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($args);
    $images = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $image_id = get_the_ID();
            $image_path = wp_get_attachment_url($image_id);
            $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            $images[] = (object) [
                'image_id' => $image_id,
                'image_path' => $image_path,
                'alt_text' => $alt_text,
            ];
        }
        wp_reset_postdata();
    }

    return $images;
}
