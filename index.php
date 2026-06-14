<?php
require_once __DIR__ . '/back/conn.php';
require_once __DIR__ . '/back/navbar.php';

try {
    $flips_query = 'SELECT * FROM flips ORDER BY created_at DESC LIMIT 10';
    $stmt = $pdo->query($flips_query);
    $flips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $verses_query = 'SELECT v.*, u.username, b.title as book_title, b.id as book_id 
                 FROM verses v 
                 JOIN users u ON v.user_id = u.id 
                 JOIN books b ON v.book_id = b.id 
                 ORDER BY v.created_at DESC LIMIT 10';
    $stmt = $pdo->query($verses_query);
    $verses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $trending_query = 'SELECT * FROM books ORDER BY views DESC LIMIT 5';
    $stmt = $pdo->query($trending_query);
    $trending_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $authors_query = 'SELECT u.id, u.username, u.avatar, COUNT(b.id) as book_count 
                  FROM users u 
                  LEFT JOIN books b ON u.id = b.author_id 
                  GROUP BY u.id 
                  ORDER BY book_count DESC 
                  LIMIT 5';
    $stmt = $pdo->query($authors_query);
    $top_authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Query error: ' . $e->getMessage());
    $flips = [];
    $verses = [];
    $trending_books = [];
    $top_authors = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookify - Digital Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
        }
        body {
            background-color: #f4f1ea;
        }
        .book-page {
            background-color: #fdfbf7;
            background-image: 
                repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 2px,
                    rgba(26, 42, 108, 0.02) 2px,
                    rgba(26, 42, 108, 0.02) 4px
                );
        }
        .flip-scroll {
            overflow-x: auto;
            scroll-behavior: smooth;
        }
        .flip-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .flip-scroll::-webkit-scrollbar-track {
            background: rgba(26, 42, 108, 0.1);
            border-radius: 10px;
        }
        .flip-scroll::-webkit-scrollbar-thumb {
            background: #d4af37;
            border-radius: 10px;
        }
        .flip-item {
            flex-shrink: 0;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #d4af37;
            background-size: cover;
            background-position: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .flip-item:hover {
            transform: scale(1.1);
        }
        .action-btn {
            background-color: #1a2a6c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            background-color: #0f1a42;
            box-shadow: 0 4px 6px rgba(26, 42, 108, 0.3);
        }
        .action-btn-secondary {
            border: 2px solid #1a2a6c;
            color: #1a2a6c;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .action-btn-secondary:hover {
            background-color: #1a2a6c;
            color: white;
        }
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            background-color: #d4af37;
            color: #1a2a6c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
            transition: all 0.3s ease;
            z-index: 40;
        }
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.6);
        }
        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }
            .main-content {
                max-width: 100%;
            }
        }
    </style>
</head>
<body class="bg-creamy-beige">
    <!-- Navigation Bar -->
    <nav class="bg-white border-b-2 border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-3xl font-bold text-blue-900" style="color: #1a2a6c;">📚 Bookify</h1>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <input type="text" placeholder="Search books..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" style="border-color: #1a2a6c; color: #1a2a6c;">
                    <button class="action-btn">Login</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Top Stories Bar (Flips) -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-4" style="color: #1a2a6c;">📽️ Quick Flips</h2>
            <div class="flip-scroll flex gap-6 pb-4">
                <?php foreach ($flips as $flip): ?>
                    <div class="flip-item" style="background-image: url('<?php echo htmlspecialchars($flip['thumbnail']); ?>');" title="<?php echo htmlspecialchars($flip['title']); ?>"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Feed and Sidebar Container -->
        <div class="flex gap-8">
            <!-- Main Feed (Verses) -->
            <div class="main-content flex-1">
                <h2 class="text-2xl font-bold mb-6" style="color: #1a2a6c;">📖 Latest Verses</h2>
                
                <div class="space-y-6">
                    <?php foreach ($verses as $verse): ?>
                        <div class="book-page rounded-lg shadow-lg p-8 border-l-4" style="border-left-color: #d4af37;">
                            <!-- Author and Book Info -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-sm font-semibold" style="color: #1a2a6c;">By <?php echo htmlspecialchars($verse['username']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($verse['created_at'])); ?></p>
                                </div>
                                <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background-color: #e8dcc4; color: #1a2a6c;">
                                    <?php echo htmlspecialchars($verse['book_title']); ?>
                                </span>
                            </div>

                            <!-- Verse Content -->
                            <div class="mb-6">
                                <p class="text-lg leading-relaxed" style="color: #1a2a6c;">
                                    <?php echo htmlspecialchars(substr($verse['content'], 0, 200)); ?>...
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between border-t pt-4" style="border-color: #e8dcc4;">
                                <div class="flex gap-4">
                                    <button class="action-btn-secondary">❤️ Like</button>
                                    <button class="action-btn-secondary">💬 Comment</button>
                                </div>
                                <a href="read_book.php?id=<?php echo htmlspecialchars($verse['book_id']); ?>" class="action-btn">Read This Book →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar (Discovery) - Hidden on Mobile -->
            <aside class="sidebar w-80 lg:block hidden">
                <!-- Trending Books -->
                <div class="book-page rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4" style="color: #1a2a6c;">🔥 Trending Books</h3>
                    <div class="space-y-4">
                        <?php foreach ($trending_books as $book): ?>
                            <div class="pb-4 border-b" style="border-color: #e8dcc4;">
                                <p class="font-semibold" style="color: #1a2a6c;"><?php echo htmlspecialchars($book['title']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($book['description'], 0, 50)); ?>...</p>
                                <p class="text-xs text-gray-500 mt-2">👁️ <?php echo number_format($book['views']); ?> views</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top Authors -->
                <div class="book-page rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-bold mb-4" style="color: #1a2a6c;">✨ Top Authors</h3>
                    <div class="space-y-4">
                        <?php foreach ($top_authors as $author): ?>
                            <div class="flex items-center justify-between pb-4 border-b" style="border-color: #e8dcc4;">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($author['avatar'] ?? 'https://via.placeholder.com/40'); ?>" alt="<?php echo htmlspecialchars($author['username']); ?>" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-semibold text-sm" style="color: #1a2a6c;"><?php echo htmlspecialchars($author['username']); ?></p>
                                        <p class="text-xs text-gray-600"><?php echo $author['book_count']; ?> books</p>
                                    </div>
                                </div>
                                <button class="action-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Follow</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Floating Action Button (New Post) -->
    <div class="fab" onclick="alert('New post feature coming soon!');">
        ✍️
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t-2 border-gray-200 md:hidden z-40">
        <div class="flex justify-around items-center h-16">
            <button class="flex flex-col items-center justify-center w-full h-full hover:bg-gray-100" style="color: #1a2a6c;">
                <span class="text-xl">🏠</span>
                <span class="text-xs font-semibold">Home</span>
            </button>
            <button class="flex flex-col items-center justify-center w-full h-full hover:bg-gray-100" style="color: #1a2a6c;">
                <span class="text-xl">🔍</span>
                <span class="text-xs font-semibold">Search</span>
            </button>
            <button class="flex flex-col items-center justify-center w-full h-full hover:bg-gray-100" style="color: #1a2a6c;">
                <span class="text-xl">📚</span>
                <span class="text-xs font-semibold">Library</span>
            </button>
            <button class="flex flex-col items-center justify-center w-full h-full hover:bg-gray-100" style="color: #1a2a6c;">
                <span class="text-xl">👤</span>
                <span class="text-xs font-semibold">Profile</span>
            </button>
        </div>
    </nav>

    <!-- Extra padding for mobile bottom nav -->
    <div class="h-16 md:hidden"></div>
</body>
</html>