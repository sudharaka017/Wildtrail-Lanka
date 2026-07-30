<?php
// details.php - Main details page for parks
// Assumes `db.php` has already been required and session started in the including file

// Try to fetch a park by `park_id` GET param, otherwise pick the first active park
$park = null;
try {
    if (!empty($_GET['park_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM parks WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$_GET['park_id']]);
        $park = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$park) {
        $stmt = $pdo->query("SELECT * FROM parks WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        $park = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $park = null;
}

?>

<!-- Hero / Details Section -->
<?php
// If a specific Yala background image exists (jpg or webp), use it in the hero section
$yalaCandidates = [
    'images/Yala-National-Park-Elephant-Tusker.jpg',
    'images/Yala-National-Park-Elephant-Tusker.webp',
    'images/Yala-National-Park-Elephant-Tusker.png'
];
$heroStyle = '';
$yalaBgFound = '';
foreach ($yalaCandidates as $c) {
    if (file_exists(__DIR__ . '/' . $c)) { $yalaBgFound = $c; break; }
}
if ($yalaBgFound) {
    $heroStyle = "background: linear-gradient(135deg, rgba(26,95,42,0.6) 0%, rgba(45,138,62,0.4) 100%), url('{$yalaBgFound}') center/cover no-repeat; color: white;";
}
?>

<div class="hero-section" style="<?php echo $heroStyle; ?>">
    <div class="container text-center">
        <h1 class="display-4 mb-3"><?php echo htmlspecialchars($park['park_name'] ?? 'Explore Our Parks'); ?></h1>
        <p class="welcome-text"><?php echo htmlspecialchars($park['short_description'] ?? 'Discover wildlife, trails, and booking information.'); ?></p>
    </div>
</div>

<div class="container mb-5">
    <?php if (!$park): ?>
        <div class="text-center py-5">
            <h4>No park details available</h4>
            <p class="text-muted">There are no active parks in the database.</p>
            <p><a href="parks.php" class="btn btn-success">View all parks</a></p>
        </div>
    <?php else: ?>
        <div class="row gy-4">
            <div class="col-md-6">
                <?php if (!empty($park['image'])): ?>
                    <img src="<?php echo htmlspecialchars($park['image']); ?>" alt="<?php echo htmlspecialchars($park['park_name']); ?>" class="img-fluid rounded shadow-sm">
                <?php else: ?>
                    <div class="bg-light rounded p-5 text-center" style="min-height: 300px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-tree" style="font-size:64px; color: #2d8a3e;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h3 style="color: var(--dark-green);"><?php echo htmlspecialchars($park['park_name']); ?></h3>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($park['description'] ?? 'No description available.')); ?></p>

                <ul class="list-unstyled">
                    <li><strong>Location:</strong> <?php echo htmlspecialchars($park['location'] ?? 'Unknown'); ?></li>
                    <li><strong>Best Time:</strong> <?php echo htmlspecialchars($park['best_time'] ?? 'Year-round'); ?></li>
                    <li><strong>Entry Fee:</strong> <?php echo isset($park['entry_fee']) ? 'Rs. '.number_format($park['entry_fee'],2) : 'Varies'; ?></li>
                </ul>

                <div class="mt-4">
                    <a href="book_ticket.php?park_id=<?php echo urlencode($park['id']); ?>" class="btn btn-success btn-lg me-2">Book a Safari</a>
                    <a href="parks.php" class="btn btn-outline-secondary btn-lg">Explore Parks</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Yala-specific extended content
if ($park && stripos($park['park_name'], 'yala') !== false):
    ?>
    <div class="container mb-5">
        <div class="row">
            <div class="col-12">
                <h2 style="color: var(--dark-green);">About Yala National Park</h2>
                <p>Yala National Park is one of Sri Lanka's most popular national parks, famed for its population of leopards, elephants, sloth bears, and diverse birdlife. It offers a variety of habitats including dry monsoon forests, wetlands, and coastal lagoons.</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <h4>Wildlife & Highlights</h4>
                <ul>
                    <li>Leopards: Yala is renowned for leopard sightings.</li>
                    <li>Elephants and sambar deer commonly seen.</li>
                    <li>Birdwatching: wetland and coastal species.</li>
                    <li>Scenic drives through scrub and grassland habitats.</li>
                </ul>

                <h4 class="mt-3">Best Time to Visit</h4>
                <p>The best time for wildlife viewing is from February to July when the dry season concentrates animals around water sources.</p>

                <h4 class="mt-3">Visitor Tips</h4>
                <ul>
                    <li>Bring binoculars and a telephoto lens for wildlife.</li>
                    <li>Book morning or late afternoon safaris for best sightings.</li>
                    <li>Respect park rules and keep distance from animals.</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h4>Gallery</h4>
                <div class="row g-2">
                    <?php
                    // Preferred gallery file locations (first existing files will be displayed)
                    $galleryPaths = [
                        'images/ec.jpg',
                        'images/yala1.jpg',
                        'images/yala2.jpg',
                        'images/yala3.jpg',
                        'assets/img/yala1.jpg',
                        'assets/img/yala2.jpg',
                    ];

                    $displayed = 0;
                    foreach ($galleryPaths as $gp) {
                        if ($displayed >= 4) break;
                        if (file_exists(__DIR__ . '/' . $gp)) {
                            echo '<div class="col-6';
                            if ($displayed >= 2) echo ' mt-2';
                            echo '">';
                            echo '<img src="' . htmlspecialchars($gp) . '" alt="Gallery image" class="img-fluid rounded shadow-sm">';
                            echo '</div>';
                            $displayed++;
                        }
                    }

                    // If none found, show a placeholder
                    if ($displayed === 0) {
                        echo '<div class="col-12 text-center text-muted">No gallery images available.</div>';
                    }
                    ?>
                </div>

                <h4 class="mt-3">Location</h4>
                <p>Yala National Park is located in the southeast of Sri Lanka, spanning across the districts of Hambantota and Monaragala.</p>
                <div class="ratio ratio-16x9">
                    <iframe src="https://www.google.com/maps?q=Yala+National+Park&output=embed" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
<?php
endif;
?>
