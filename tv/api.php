<?php
require_once '../templates/autoload.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'data' => [],
    'ofertas' => [] 
];

try {
    $db = Database::getConnection();

    // 1. Get Global Settings
    $stmt = $db->query("SELECT * FROM tv_settings");
    $settings = [];
    while($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Check for initial video in current directory
    $initialVideo = file_exists('1.mp4') ? '1.mp4' : null;

    // Audio Path Logic
    $bgAudio = '';
    if (!empty($settings['background_audio'])) {
        // If stored path already contains 'uploads/', just prepend '../' if needed, or use as is
        if (strpos($settings['background_audio'], 'uploads/') !== false) {
             $bgAudio = '../' . $settings['background_audio'];
        } else {
             // If just filename
             $bgAudio = '../uploads/tv/audio/' . $settings['background_audio'];
        }
    }

    $response['data'] = [
        'logo_url' => '../assets/img/logo.png',
        'background_audio' => $bgAudio,
        'initial_video' => $initialVideo,
        'default_duration_ms' => ($settings['default_duration'] ?? 10) * 1000,
        'global_suggestion_prob' => floatval($settings['global_suggestion_probability'] ?? 0.4),
        'chef_suggestions_pool' => json_decode($settings['chef_suggestions'] ?? '[]')
    ];

    // 2. Get Playlist
    // Join with products table to get fallbacks
    $sql = "SELECT t.*, p.name as p_name, p.description as p_desc, p.price_usd as p_price, p.image_url as p_image 
            FROM tv_playlist_items t 
            LEFT JOIN products p ON t.product_id = p.id 
            WHERE t.is_active = 1 
            ORDER BY t.sort_order ASC";
    
    $stmt = $db->query($sql);
    $items = $stmt->fetchAll();

    $ofertas = [];

    foreach ($items as $item) {
        // Decide logic: Custom > Product > Fallback
        
        $title = $item['custom_title'] ?: $item['p_name'] ?: 'Sin Título';
        $desc = $item['custom_description'] ?: $item['p_desc'] ?: '';
        
        // Price formatting
        if ($item['custom_price']) {
            $price = $item['custom_price'];
        } elseif ($item['p_price']) {
             $price = '$' . number_format($item['p_price'], 2);
        } else {
             $price = '';
        }

        // Image Logic
        $imgRaw = $item['custom_image_url'] ?: $item['p_image'];
        
        // Fix path. If starts with 'uploads/', prepend '../'
        // If it's a full URL, leave it.
        $imgUrl = '';
        $isVideo = false;

        if ($imgRaw && $imgRaw != 'default.jpg') {
             if (strpos($imgRaw, 'http') === 0) {
                 $imgUrl = $imgRaw;
             } else {
                 $imgUrl = '../' . $imgRaw;
                 $ext = strtolower(pathinfo($imgRaw, PATHINFO_EXTENSION));
                 if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                     $isVideo = true;
                 }
             }
        } else {
             // Fallback to default image if none provided
             $imgUrl = 'default.png'; 
        }

        $ofertas[] = [
            'id' => $item['id'],
            'titulo' => $title,
            'titulo_size' => strlen($title) > 20 ? 3 : 5, // Auto-size logic
            'descripcion' => $desc,
            'precio' => $price,
            'imagen_producto' => $imgUrl,
            'es_video' => $isVideo,
            'imagen_fondo' => 'default_bg.jpg', // Could be customizable per slide in future
            'duration_ms' => ($item['duration_seconds'] ?: 10) * 1000,
            'show_suggestion' => (bool)$item['show_suggestion'],
            'suggestion_text' => $item['suggestion_text']
        ];
    }

    // Default Fallback if empty playlist
    if (empty($ofertas)) {
         $ofertas[] = [
            'titulo' => 'Menú Digital',
            'titulo_size' => 5,
            'descripcion' => 'Configure su playlist en el panel administrativo.',
            'precio' => '',
            'imagen_producto' => 'default.png', 
            'es_video' => false,
            'imagen_fondo' => 'default_bg.jpg',
            'duration_ms' => 10000,
            'show_suggestion' => false
        ];
    }

    $response['ofertas'] = $ofertas;
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
