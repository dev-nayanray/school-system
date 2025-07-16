<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

$role = $_SESSION['role'];

// Fetch announcements targeted to user's role or to all
$stmt = $pdo->prepare("SELECT a.*, u.name as posted_by_name FROM announcements a JOIN users u ON a.posted_by = u.id WHERE a.role_target IN ('all', ?) ORDER BY a.created_at DESC");
$stmt->execute([$role]);
$announcements = $stmt->fetchAll();

// Get announcement count by type for the filter badges
$type_counts = [
    'all' => 0,
    'important' => 0,
    'update' => 0,
    'event' => 0
];

foreach ($announcements as $announcement) {
    $type_counts['all']++;
    if (stripos($announcement['title'], 'important') !== false) {
        $type_counts['important']++;
    } elseif (stripos($announcement['title'], 'update') !== false) {
        $type_counts['update']++;
    } elseif (stripos($announcement['title'], 'event') !== false) {
        $type_counts['event']++;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header with search and filter -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Announcements</h1>
                    <p class="mt-2 text-gray-600">Latest updates and important information</p>
                </div>
                
                <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-3">
                    <div class="relative">
                        <input type="text" id="searchAnnouncements" placeholder="Search announcements..." class="w-full md:w-64 bg-white border border-gray-300 rounded-lg px-4 py-2.5 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="relative">
                        <select id="filterDate" class="appearance-none w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all">All Dates</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Type Filter Badges -->
            <div class="flex flex-wrap gap-3 mb-8">
                <button data-type="all" class="announcement-filter flex items-center px-4 py-2 rounded-full bg-blue-100 text-blue-800 font-medium hover:bg-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                    All Announcements
                    <span class="ml-2 bg-blue-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"><?php echo $type_counts['all']; ?></span>
                </button>
                <button data-type="important" class="announcement-filter flex items-center px-4 py-2 rounded-full bg-red-100 text-red-800 font-medium hover:bg-red-200 transition-colors focus:outline-none focus:ring-2 focus:ring-red-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Important
                    <span class="ml-2 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"><?php echo $type_counts['important']; ?></span>
                </button>
                <button data-type="update" class="announcement-filter flex items-center px-4 py-2 rounded-full bg-green-100 text-green-800 font-medium hover:bg-green-200 transition-colors focus:outline-none focus:ring-2 focus:ring-green-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Updates
                    <span class="ml-2 bg-green-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"><?php echo $type_counts['update']; ?></span>
                </button>
                <button data-type="event" class="announcement-filter flex items-center px-4 py-2 rounded-full bg-purple-100 text-purple-800 font-medium hover:bg-purple-200 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Events
                    <span class="ml-2 bg-purple-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"><?php echo $type_counts['event']; ?></span>
                </button>
            </div>
            
            <!-- Announcements Grid -->
            <?php if (count($announcements) === 0): ?>
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">No announcements available</h3>
                    <p class="mt-2 text-gray-500">Check back later for updates</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="announcementsContainer">
                    <?php foreach ($announcements as $announcement): 
                        $type = 'update';
                        $color = 'blue';
                        $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>';
                        
                        if (stripos($announcement['title'], 'important') !== false) {
                            $type = 'important';
                            $color = 'red';
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>';
                        } elseif (stripos($announcement['title'], 'event') !== false) {
                            $type = 'event';
                            $color = 'purple';
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>';
                        }
                        
                        $date = new DateTime($announcement['created_at']);
                        $now = new DateTime();
                        $interval = $date->diff($now);
                        
                        $timeAgo = '';
                        if ($interval->y > 0) {
                            $timeAgo = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                        } elseif ($interval->m > 0) {
                            $timeAgo = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                        } elseif ($interval->d > 0) {
                            $timeAgo = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                        } elseif ($interval->h > 0) {
                            $timeAgo = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                        } elseif ($interval->i > 0) {
                            $timeAgo = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                        } else {
                            $timeAgo = 'Just now';
                        }
                    ?>
                    <div class="announcement-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow overflow-hidden" data-type="<?php echo $type; ?>" data-date="<?php echo $announcement['created_at']; ?>">
                        <div class="p-5">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                        <?php echo $icon; ?>
                                        <span class="ml-1.5"><?php echo ucfirst($type); ?></span>
                                    </div>
                                    <h3 class="mt-3 text-lg font-bold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                </div>
                                <button class="announcement-bookmark text-gray-300 hover:text-yellow-400 transition-colors" data-id="<?php echo $announcement['id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                </button>
                            </div>
                            
                            <p class="mt-4 text-gray-600 line-clamp-3"><?php echo htmlspecialchars($announcement['content']); ?></p>
                            
                            <div class="mt-6 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10"></div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($announcement['posted_by_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $timeAgo; ?></p>
                                    </div>
                                </div>
                                
                                <button class="text-sm font-medium text-blue-600 hover:text-blue-800 announcement-read-more" data-id="<?php echo $announcement['id']; ?>">
                                    Read more
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Announcement Detail Modal -->
            <div id="announcementModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-xl leading-6 font-bold text-gray-900" id="modalTitle"></h3>
                                        <button id="closeModal" class="text-gray-400 hover:text-gray-500">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <div class="flex items-center text-sm text-gray-500 mb-4">
                                            <span id="modalAuthor"></span>
                                            <span class="mx-2">•</span>
                                            <span id="modalDate"></span>
                                        </div>
                                        
                                        <p class="text-gray-700 whitespace-pre-line" id="modalContent"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" id="closeModalBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .announcement-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .announcement-card[data-type="important"] {
        border-left-color: #EF4444;
    }
    .announcement-card[data-type="update"] {
        border-left-color: #3B82F6;
    }
    .announcement-card[data-type="event"] {
        border-left-color: #8B5CF6;
    }
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchAnnouncements');
    const announcementCards = document.querySelectorAll('.announcement-card');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        announcementCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const content = card.querySelector('p').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || content.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Filter by type
    const filterButtons = document.querySelectorAll('.announcement-filter');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.dataset.type;
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-blue-100', 'text-blue-800');
            });
            
            this.classList.remove('bg-blue-100', 'text-blue-800');
            this.classList.add('bg-blue-600', 'text-white');
            
            // Filter cards
            announcementCards.forEach(card => {
                if (filterType === 'all' || card.dataset.type === filterType) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Filter by date
    const dateFilter = document.getElementById('filterDate');
    
    dateFilter.addEventListener('change', function() {
        const filterValue = this.value;
        const today = new Date();
        
        announcementCards.forEach(card => {
            const announcementDate = new Date(card.dataset.date);
            const diffTime = Math.abs(today - announcementDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let showCard = true;
            
            switch(filterValue) {
                case 'today':
                    showCard = diffDays === 0;
                    break;
                case 'week':
                    showCard = diffDays <= 7;
                    break;
                case 'month':
                    showCard = diffDays <= 30;
                    break;
                case 'all':
                default:
                    showCard = true;
            }
            
            card.style.display = showCard ? 'block' : 'none';
        });
    });
    
    // Modal functionality
    const modal = document.getElementById('announcementModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalAuthor = document.getElementById('modalAuthor');
    const modalDate = document.getElementById('modalDate');
    const modalContent = document.getElementById('modalContent');
    const closeModal = document.getElementById('closeModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    
    document.querySelectorAll('.announcement-read-more').forEach(button => {
        button.addEventListener('click', function() {
            const card = this.closest('.announcement-card');
            const title = card.querySelector('h3').textContent;
            const author = card.querySelector('p.text-gray-900').textContent;
            const date = card.querySelector('p.text-gray-500').textContent;
            const content = card.querySelector('p.text-gray-600').textContent;
            
            modalTitle.textContent = title;
            modalAuthor.textContent = 'Posted by ' + author;
            modalDate.textContent = date;
            modalContent.textContent = content;
            
            modal.classList.remove('hidden');
        });
    });
    
    [closeModal, closeModalBtn].forEach(btn => {
        btn.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
    });
    
    // Bookmark functionality
    document.querySelectorAll('.announcement-bookmark').forEach(button => {
        button.addEventListener('click', function() {
            this.classList.toggle('text-yellow-400');
            this.classList.toggle('text-gray-300');
            
            // In a real app, you would send an AJAX request to save the bookmark
            const announcementId = this.dataset.id;
            console.log('Bookmark toggled for announcement:', announcementId);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>