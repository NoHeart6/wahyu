<?php

function checkImageExists($url) {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200') !== false;
}

$images = [
    'Piston Kit Yamaha NMAX' => 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Karburator PE28 Racing' => 'https://images.unsplash.com/photo-1589739900266-43b2843f4c12?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Ban Michelin Pilot Street' => 'https://images.unsplash.com/photo-1580974928064-f0aeef70895a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Ban Dunlop Sportmax' => 'https://images.unsplash.com/photo-1582641547274-166ff3d66c26?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Shell Advance Ultra 10W-40' => 'https://images.unsplash.com/photo-1635796332582-03b56393a0cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Motul 7100 4T 10W-40' => 'https://images.unsplash.com/photo-1635796332882-328d20582462?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Knalpot Yoshimura R77' => 'https://images.unsplash.com/photo-1614026480418-bd11fdb9fa06?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Lampu LED Projector' => 'https://images.unsplash.com/photo-1611967164521-abae8fba4668?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'CDI Racing Unlimited' => 'https://images.unsplash.com/photo-1617469165786-8007eda3caa7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Handle RCB Racing' => 'https://images.unsplash.com/photo-1607860108855-64acf2078ed9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
];

echo "Memeriksa ketersediaan gambar...\n\n";

$validImages = [];
$invalidImages = [];

foreach ($images as $name => $url) {
    if (checkImageExists($url)) {
        $validImages[$name] = $url;
        echo "✅ $name: Gambar tersedia\n";
    } else {
        $invalidImages[$name] = $url;
        echo "❌ $name: Gambar tidak tersedia\n";
    }
}

echo "\nRingkasan:\n";
echo "Total gambar: " . count($images) . "\n";
echo "Gambar tersedia: " . count($validImages) . "\n";
echo "Gambar tidak tersedia: " . count($invalidImages) . "\n";

if (count($invalidImages) > 0) {
    echo "\nGambar yang tidak tersedia:\n";
    foreach ($invalidImages as $name => $url) {
        echo "$name: $url\n";
    }
}
?> 