<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì hệ thống - BVOTE 2025</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);
            --dark-bg: #0f0f23;
            --card-bg: #1a1a3e;
        }

        body {
            background: var(--dark-bg);
            color: white;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .maintenance-container {
            max-width: 600px;
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.3);
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .maintenance-icon {
            font-size: 4rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }

        .maintenance-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .maintenance-text {
            font-size: 1.1rem;
            opacity: 0.8;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .progress-container {
            margin: 2rem 0;
        }

        .progress {
            height: 10px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            background: var(--primary-gradient);
            height: 100%;
            border-radius: 5px;
            animation: progress-animation 3s ease-in-out infinite;
        }

        @keyframes progress-animation {
            0% { width: 30%; }
            50% { width: 70%; }
            100% { width: 30%; }
        }

        .contact-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .contact-info h5 {
            color: #8b5cf6;
            margin-bottom: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.5rem 0;
            gap: 0.5rem;
        }

        .contact-item i {
            color: #8b5cf6;
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>

        <h1 class="maintenance-title">Bảo trì hệ thống</h1>

        <p class="maintenance-text">
            Chúng tôi đang nâng cấp hệ thống để mang đến trải nghiệm tốt hơn cho bạn.
            Vui lòng quay lại sau ít phút.
        </p>

        <div class="progress-container">
            <div class="progress">
                <div class="progress-bar"></div>
            </div>
        </div>

        <div class="contact-info">
            <h5><i class="fas fa-headset me-2"></i>Thông tin liên hệ</h5>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span>support@bvote.vn</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span>1900 1234</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <span>Dự kiến hoàn thành: 30 phút</span>
            </div>
        </div>

        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                BVOTE 2025 - Hệ thống bình chọn an toàn và minh bạch
            </small>
        </div>
    </div>

    <script>
        // Auto refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);

        // Show current time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('vi-VN');
            document.title = `Bảo trì hệ thống - ${timeStr} - BVOTE 2025`;
        }

        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>
