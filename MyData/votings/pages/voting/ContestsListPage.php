<?php
/**
 * ContestsListPage - Trang danh sách cuộc thi công khai
 * Tương tự React ContestsListPage với giao diện đẹp và responsive
 */
require_once __DIR__ . '/../../components/PublicLayout.php';

class ContestsListPage extends Component {
    private $contests;
    private $featuredContest;

    public function __construct($props = []) {
        parent::__construct($props);
        $this->loadData();
    }

    private function loadData() {
        // Load cuộc thi nổi bật
        $this->featuredContest = [
            'id' => 1,
            'name' => 'Cuộc thi Hoa hậu Việt Nam 2024',
            'description' => 'Cuộc thi tìm kiếm người đẹp đại diện cho Việt Nam tham gia các cuộc thi sắc đẹp quốc tế.',
            'bannerUrl' => 'https://images.unsplash.com/photo-1658504140972-7af3e80d35f1?w=800&h=400&fit=crop',
            'startDate' => '2024-01-01',
            'endDate' => '2024-12-31',
            'contestantsCount' => 156,
            'totalVotes' => 2847,
            'status' => 'active'
        ];

        // Load danh sách cuộc thi
        $this->contests = [
            [
                'id' => 2,
                'name' => 'Cuộc thi Tài năng Âm nhạc 2024',
                'description' => 'Tìm kiếm những giọng ca xuất sắc trong lĩnh vực âm nhạc.',
                'bannerUrl' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800&h=400&fit=crop',
                'startDate' => '2024-02-01',
                'endDate' => '2024-08-31',
                'contestantsCount' => 89,
                'totalVotes' => 1247,
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => 'Cuộc thi Nhiếp ảnh Nghệ thuật',
                'description' => 'Khám phá tài năng nhiếp ảnh và sáng tạo nghệ thuật.',
                'bannerUrl' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=400&fit=crop',
                'startDate' => '2024-03-01',
                'endDate' => '2024-09-30',
                'contestantsCount' => 234,
                'totalVotes' => 1892,
                'status' => 'active'
            ],
            [
                'id' => 4,
                'name' => 'Cuộc thi Vẽ tranh Thiếu nhi',
                'description' => 'Phát hiện và nuôi dưỡng tài năng hội họa của trẻ em.',
                'bannerUrl' => 'https://images.unsplash.com/photo-1541961017774-22349e4a1262?w=800&h=400&fit=crop',
                'startDate' => '2024-04-01',
                'endDate' => '2024-10-31',
                'contestantsCount' => 156,
                'totalVotes' => 892,
                'status' => 'active'
            ],
            [
                'id' => 5,
                'name' => 'Cuộc thi Văn học Sáng tác',
                'description' => 'Tìm kiếm những tác phẩm văn học có giá trị nghệ thuật cao.',
                'bannerUrl' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&h=400&fit=crop',
                'startDate' => '2024-05-01',
                'endDate' => '2024-11-30',
                'contestantsCount' => 78,
                'totalVotes' => 456,
                'status' => 'active'
            ]
        ];
    }

    protected function renderContent() {
        $layout = new PublicLayout([
            'content' => $this->renderContestsList()
        ]);
        echo $layout->render();
    }

    private function renderContestsList() {
        ?>
        <div class="min-h-screen">
            <!-- Hero Section -->
            <section class="relative py-20 px-4">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-90"></div>
                <div class="relative z-10 max-w-4xl mx-auto text-center text-white">
                    <h1 class="text-5xl md:text-6xl font-bold mb-6">
                        Khám phá tài năng
                        <span class="block text-yellow-300">Không giới hạn</span>
                    </h1>
                    <p class="text-xl md:text-2xl mb-8 text-blue-100">
                        Tham gia các cuộc thi thú vị và bình chọn cho những tài năng xuất sắc nhất
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button onclick="openLoginModal()" class="bg-white text-blue-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập ngay
                        </button>
                        <button onclick="scrollToContests()" class="border-2 border-white text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-white hover:text-blue-600 transition-all duration-300">
                            <i class="fas fa-trophy mr-2"></i>Xem cuộc thi
                        </button>
                    </div>
                </div>
            </section>

            <!-- Featured Contest -->
            <section class="py-16 px-4 bg-gray-50">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Cuộc thi nổi bật</h2>
                    <?php $this->renderFeaturedContest(); ?>
                </div>
            </section>

            <!-- All Contests -->
            <section id="contests" class="py-16 px-4">
                <div class="max-w-6xl mx-auto">
                    <div class="flex items-center justify-between mb-12">
                        <h2 class="text-3xl font-bold text-gray-900">Tất cả cuộc thi</h2>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <input
                                    type="text"
                                    placeholder="Tìm kiếm cuộc thi..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <select class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Tất cả</option>
                                <option value="active">Đang diễn ra</option>
                                <option value="upcoming">Sắp diễn ra</option>
                                <option value="ended">Đã kết thúc</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($this->contests as $contest): ?>
                        <?php $this->renderContestCard($contest); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="py-20 px-4 bg-gradient-to-r from-purple-600 to-blue-600">
                <div class="max-w-4xl mx-auto text-center text-white">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">Bạn có muốn tổ chức cuộc thi?</h2>
                    <p class="text-xl mb-8 text-purple-100">
                        Liên hệ với chúng tôi để được tư vấn và hỗ trợ tổ chức cuộc thi chuyên nghiệp
                    </p>
                    <button class="bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-phone mr-2"></i>Liên hệ ngay
                    </button>
                </div>
            </section>
        </div>

        <script>
        function scrollToContests() {
            document.getElementById('contests').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function openLoginModal() {
            // Trigger login modal
            if (typeof window.openLoginModal === 'function') {
                window.openLoginModal();
            }
        }
        </script>
        <?php
    }

    private function renderFeaturedContest() {
        $contest = $this->featuredContest;
        ?>
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="relative">
                <img src="<?php echo $contest['bannerUrl']; ?>" alt="<?php echo $contest['name']; ?>" class="w-full h-64 md:h-80 object-cover">
                <div class="absolute top-4 right-4">
                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                        Đang diễn ra
                    </span>
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6">
                    <h3 class="text-2xl md:text-3xl font-bold text-white mb-2"><?php echo $contest['name']; ?></h3>
                    <p class="text-white/90 mb-4"><?php echo $contest['description']; ?></p>
                    <div class="flex items-center space-x-6 text-white/80 text-sm">
                        <span><i class="fas fa-calendar mr-2"></i><?php echo $this->formatDate($contest['startDate']); ?> - <?php echo $this->formatDate($contest['endDate']); ?></span>
                        <span><i class="fas fa-users mr-2"></i><?php echo number_format($contest['contestantsCount']); ?> thí sinh</span>
                        <span><i class="fas fa-heart mr-2"></i><?php echo number_format($contest['totalVotes']); ?> lượt bình chọn</span>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="openLoginModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>Tham gia ngay
                        </button>
                        <a href="/contests/<?php echo $contest['id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold">
                            <i class="fas fa-eye mr-2"></i>Xem chi tiết
                        </a>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900"><?php echo number_format($contest['contestantsCount']); ?></div>
                        <div class="text-sm text-gray-500">Thí sinh đã đăng ký</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderContestCard($contest) {
        ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="relative">
                <img src="<?php echo $contest['bannerUrl']; ?>" alt="<?php echo $contest['name']; ?>" class="w-full h-48 object-cover">
                <div class="absolute top-3 right-3">
                    <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        Đang diễn ra
                    </span>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2"><?php echo $contest['name']; ?></h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo $contest['description']; ?></p>

                <div class="space-y-3 mb-6">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-calendar mr-2 w-4"></i>
                        <span><?php echo $this->formatDate($contest['startDate']); ?> - <?php echo $this->formatDate($contest['endDate']); ?></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-users mr-2 w-4"></i>
                        <span><?php echo number_format($contest['contestantsCount']); ?> thí sinh</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-heart mr-2 w-4"></i>
                        <span><?php echo number_format($contest['totalVotes']); ?> lượt bình chọn</span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button onclick="openLoginModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>Tham gia
                    </button>
                    <a href="/contests/<?php echo $contest['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                        Xem chi tiết <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    private function formatDate($dateString) {
        $date = new DateTime($dateString);
        return $date->format('d/m/Y');
    }
}

// Render trang
$page = new ContestsListPage();
$page->render();
?>
