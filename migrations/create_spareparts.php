<?php
require_once __DIR__ . '/../config/database.php';

// Inisialisasi koneksi database
$db = new Database();
$database = $db->getDatabase();

try {
    // Buat koleksi sparepart jika belum ada
    $collections = iterator_to_array($database->listCollections());
    $collectionNames = array_map(function($collection) {
        return $collection->getName();
    }, $collections);
    
    if (!in_array('sparepart', $collectionNames)) {
        $database->createCollection('sparepart');
    }

    if (!in_array('users', $collectionNames)) {
        $database->createCollection('users');
        
        // Tambahkan admin default
        $users = $database->users;
        $admin = [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'nama' => 'Administrator',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        $users->insertOne($admin);
        echo "Admin default telah dibuat.\n";
    }

    if (!in_array('orders', $collectionNames)) {
        $database->createCollection('orders');
        echo "Koleksi orders telah dibuat.\n";
    }
    
    // Data awal sparepart
    $spareparts = [
        // Kategori: Mesin
        [
            'nama' => 'Piston Kit Yamaha NMAX',
            'harga' => 850000,
            'stok' => 25,
            'kategori' => 'Mesin',
            'deskripsi' => 'Piston Kit original untuk Yamaha NMAX. Terbuat dari material berkualitas tinggi dengan presisi sempurna.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Piston+Kit+Yamaha+NMAX'
        ],
        [
            'nama' => 'Karburator PE28 Racing',
            'harga' => 750000,
            'stok' => 15,
            'kategori' => 'Mesin',
            'deskripsi' => 'Karburator PE28 untuk performa maksimal. Cocok untuk motor racing dan harian.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Karburator+PE28+Racing'
        ],
        // Kategori: Ban
        [
            'nama' => 'Ban Michelin Pilot Street',
            'harga' => 650000,
            'stok' => 30,
            'kategori' => 'Ban',
            'deskripsi' => 'Ban premium Michelin Pilot Street ukuran 120/70-17. Grip maksimal untuk berkendara harian.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Ban+Michelin+Pilot+Street'
        ],
        [
            'nama' => 'Ban Dunlop Sportmax',
            'harga' => 850000,
            'stok' => 20,
            'kategori' => 'Ban',
            'deskripsi' => 'Ban Dunlop Sportmax ukuran 160/60-17. Performa tinggi untuk motor sport.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Ban+Dunlop+Sportmax'
        ],
        // Kategori: Oli
        [
            'nama' => 'Shell Advance Ultra 10W-40',
            'harga' => 95000,
            'stok' => 100,
            'kategori' => 'Oli',
            'deskripsi' => 'Oli mesin Shell Advance Ultra dengan teknologi R.C.E. Cocok untuk semua motor modern.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Shell+Advance+Ultra'
        ],
        [
            'nama' => 'Motul 7100 4T 10W-40',
            'harga' => 185000,
            'stok' => 75,
            'kategori' => 'Oli',
            'deskripsi' => 'Oli mesin full synthetic Motul 7100. Performa maksimal untuk motor sport.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Motul+7100+4T'
        ],
        // Kategori: Aksesoris
        [
            'nama' => 'Knalpot Yoshimura R77',
            'harga' => 3500000,
            'stok' => 10,
            'kategori' => 'Aksesoris',
            'deskripsi' => 'Knalpot Yoshimura R77 dengan suara bass yang khas. Material stainless steel berkualitas tinggi.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Knalpot+Yoshimura+R77'
        ],
        [
            'nama' => 'Lampu LED Projector',
            'harga' => 450000,
            'stok' => 35,
            'kategori' => 'Aksesoris',
            'deskripsi' => 'Lampu LED Projector dengan cahaya terang dan fokus. Hemat energi dan tahan lama.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Lampu+LED+Projector'
        ],
        // Kategori: Mesin
        [
            'nama' => 'CDI Racing Unlimited',
            'harga' => 350000,
            'stok' => 25,
            'kategori' => 'Mesin',
            'deskripsi' => 'CDI Racing programmable untuk performa maksimal. Dilengkapi dengan 3 mapping berbeda.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=CDI+Racing+Unlimited'
        ],
        // Kategori: Aksesoris
        [
            'nama' => 'Handle RCB Racing',
            'harga' => 750000,
            'stok' => 20,
            'kategori' => 'Aksesoris',
            'deskripsi' => 'Handle RCB Racing dengan desain ergonomis. Material CNC Aluminum premium.',
            'gambar' => 'https://dummyimage.com/600x400/000/fff&text=Handle+RCB+Racing'
        ]
    ];
    
    // Masukkan data ke koleksi
    $collection = $database->sparepart;
    
    // Hapus data lama jika ada
    $collection->deleteMany([]);
    
    // Masukkan data baru
    $result = $collection->insertMany($spareparts);
    
    echo "Migrasi berhasil! " . $result->getInsertedCount() . " data sparepart telah ditambahkan.\n";
    
} catch (Exception $e) {
    die("Error saat migrasi: " . $e->getMessage() . "\n");
}
?> 