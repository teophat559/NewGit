<?php
// UserAppearancePage.php - Trang tùy chọn giao diện người dùng hoàn chỉnh
$pageTitle = 'Giao diện người dùng';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Giao diện người dùng</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="content-header">
    <h1><i class="fas fa-palette"></i> Giao diện người dùng</h1>
    <p>Tùy chỉnh giao diện và trải nghiệm người dùng trong hệ thống</p>
</div>

<!-- Theme Customization -->
<div class="theme-customization">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tùy chỉnh giao diện</h3>
            <div class="card-actions">
                <button class="btn btn-primary" onclick="saveAppearanceSettings()">
                    <i class="fas fa-save"></i> Lưu cài đặt
                </button>
                <button class="btn btn-secondary" onclick="resetToDefault()">
                    <i class="fas fa-undo"></i> Khôi phục mặc định
                </button>
                <button class="btn btn-info" onclick="previewAppearance()">
                    <i class="fas fa-eye"></i> Xem trước
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <div class="appearance-sections">
                <!-- Color Scheme -->
                <div class="appearance-section">
                    <h4><i class="fas fa-palette"></i> Bảng màu</h4>
                    <div class="color-scheme-options">
                        <div class="color-option">
                            <label class="color-radio">
                                <input type="radio" name="colorScheme" value="default" checked>
                                <span class="color-preview default-theme"></span>
                                <span class="color-name">Mặc định</span>
                            </label>
                        </div>
                        
                        <div class="color-option">
                            <label class="color-radio">
                                <input type="radio" name="colorScheme" value="dark">
                                <span class="color-preview dark-theme"></span>
                                <span class="color-name">Tối</span>
                            </label>
                        </div>
                        
                        <div class="color-option">
                            <label class="color-radio">
                                <input type="radio" name="colorScheme" value="light">
                                <span class="color-preview light-theme"></span>
                                <span class="color-name">Sáng</span>
                            </label>
                        </div>
                        
                        <div class="color-option">
                            <label class="color-radio">
                                <input type="radio" name="colorScheme" value="custom">
                                <span class="color-preview custom-theme"></span>
                                <span class="color-name">Tùy chỉnh</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Custom Color Picker -->
                    <div class="custom-colors" id="customColors" style="display: none;">
                        <div class="color-picker-group">
                            <label>Màu chính:</label>
                            <input type="color" id="primaryColor" value="#3b82f6" onchange="updateCustomTheme()">
                        </div>
                        
                        <div class="color-picker-group">
                            <label>Màu phụ:</label>
                            <input type="color" id="secondaryColor" value="#6b7280" onchange="updateCustomTheme()">
                        </div>
                        
                        <div class="color-picker-group">
                            <label>Màu nền:</label>
                            <input type="color" id="backgroundColor" value="#ffffff" onchange="updateCustomTheme()">
                        </div>
                        
                        <div class="color-picker-group">
                            <label>Màu văn bản:</label>
                            <input type="color" id="textColor" value="#1f2937" onchange="updateCustomTheme()">
                        </div>
                    </div>
                </div>
                
                <!-- Layout Options -->
                <div class="appearance-section">
                    <h4><i class="fas fa-th-large"></i> Bố cục</h4>
                    <div class="layout-options">
                        <div class="layout-option">
                            <label class="layout-radio">
                                <input type="radio" name="layout" value="default" checked>
                                <span class="layout-preview default-layout"></span>
                                <span class="layout-name">Mặc định</span>
                            </label>
                        </div>
                        
                        <div class="layout-option">
                            <label class="layout-radio">
                                <input type="radio" name="layout" value="compact">
                                <span class="layout-preview compact-layout"></span>
                                <span class="layout-name">Gọn gàng</span>
                            </label>
                        </div>
                        
                        <div class="layout-option">
                            <label class="layout-radio">
                                <input type="radio" name="layout" value="wide">
                                <span class="layout-preview wide-layout"></span>
                                <span class="layout-name">Rộng rãi</span>
                            </label>
                        </div>
                        
                        <div class="layout-option">
                            <label class="layout-radio">
                                <input type="radio" name="layout" value="sidebar">
                                <span class="layout-preview sidebar-layout"></span>
                                <span class="layout-name">Thanh bên</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Typography -->
                <div class="appearance-section">
                    <h4><i class="fas fa-font"></i> Kiểu chữ</h4>
                    <div class="typography-options">
                        <div class="form-group">
                            <label for="fontFamily">Font chữ:</label>
                            <select id="fontFamily" class="form-select" onchange="updateTypography()">
                                <option value="default">Mặc định (System)</option>
                                <option value="roboto">Roboto</option>
                                <option value="open-sans">Open Sans</option>
                                <option value="lato">Lato</option>
                                <option value="poppins">Poppins</option>
                                <option value="inter">Inter</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fontSize">Kích thước chữ:</label>
                            <select id="fontSize" class="form-select" onchange="updateTypography()">
                                <option value="small">Nhỏ</option>
                                <option value="medium" selected>Vừa</option>
                                <option value="large">Lớn</option>
                                <option value="extra-large">Rất lớn</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="lineHeight">Khoảng cách dòng:</label>
                            <select id="lineHeight" class="form-select" onchange="updateTypography()">
                                <option value="tight">Chặt</option>
                                <option value="normal" selected>Bình thường</option>
                                <option value="relaxed">Thoải mái</option>
                                <option value="loose">Rộng rãi</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Component Styling -->
                <div class="appearance-section">
                    <h4><i class="fas fa-puzzle-piece"></i> Kiểu dáng thành phần</h4>
                    <div class="component-options">
                        <div class="form-group">
                            <label for="buttonStyle">Kiểu nút:</label>
                            <select id="buttonStyle" class="form-select" onchange="updateComponentStyle()">
                                <option value="default">Mặc định</option>
                                <option value="rounded">Bo tròn</option>
                                <option value="sharp">Sắc cạnh</option>
                                <option value="outlined">Viền</option>
                                <option value="gradient">Gradient</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cardStyle">Kiểu thẻ:</label>
                            <select id="cardStyle" class="form-select" onchange="updateComponentStyle()">
                                <option value="default">Mặc định</option>
                                <option value="elevated">Nổi</option>
                                <option value="bordered">Viền</option>
                                <option value="shadowed">Bóng đổ</option>
                                <option value="glassmorphism">Kính mờ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="inputStyle">Kiểu input:</label>
                            <select id="inputStyle" class="form-select" onchange="updateComponentStyle()">
                                <option value="default">Mặc định</option>
                                <option value="minimal">Tối giản</option>
                                <option value="bordered">Viền</option>
                                <option value="filled">Điền</option>
                                <option value="floating">Nổi</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Animation & Effects -->
                <div class="appearance-section">
                    <h4><i class="fas fa-magic"></i> Hiệu ứng & Hoạt ảnh</h4>
                    <div class="animation-options">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enableAnimations" checked>
                                Bật hoạt ảnh
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="animationSpeed">Tốc độ hoạt ảnh:</label>
                            <select id="animationSpeed" class="form-select" onchange="updateAnimationSettings()">
                                <option value="slow">Chậm</option>
                                <option value="normal" selected>Bình thường</option>
                                <option value="fast">Nhanh</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enableHoverEffects" checked>
                                Hiệu ứng hover
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enableTransitions" checked>
                                Hiệu ứng chuyển đổi
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enableParallax" checked>
                                Hiệu ứng parallax
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Accessibility -->
                <div class="appearance-section">
                    <h4><i class="fas fa-universal-access"></i> Khả năng tiếp cận</h4>
                    <div class="accessibility-options">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="highContrast">
                                Độ tương phản cao
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="largeText">
                                Văn bản lớn
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="reduceMotion">
                                Giảm chuyển động
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="focusIndicators">
                                Chỉ báo focus
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="colorBlindness">Hỗ trợ mù màu:</label>
                            <select id="colorBlindness" class="form-select" onchange="updateAccessibility()">
                                <option value="none">Không</option>
                                <option value="protanopia">Protanopia (Đỏ-xanh lá)</option>
                                <option value="deuteranopia">Deuteranopia (Đỏ-xanh lá)</option>
                                <option value="tritanopia">Tritanopia (Xanh dương-vàng)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Panel -->
<div class="preview-panel" id="previewPanel" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Xem trước giao diện</h3>
            <span class="close" onclick="closePreview()">&times;</span>
        </div>
        
        <div class="card-body">
            <div class="preview-container">
                <div class="preview-header">
                    <h4>Tiêu đề trang</h4>
                    <p>Đây là mô tả trang để xem trước kiểu dáng</p>
                </div>
                
                <div class="preview-content">
                    <div class="preview-card">
                        <h5>Thẻ mẫu</h5>
                        <p>Nội dung thẻ để xem trước kiểu dáng</p>
                        <button class="btn btn-primary">Nút mẫu</button>
                    </div>
                    
                    <div class="preview-form">
                        <div class="form-group">
                            <label>Input mẫu:</label>
                            <input type="text" class="form-input" placeholder="Nhập văn bản...">
                        </div>
                        
                        <div class="form-group">
                            <label>Select mẫu:</label>
                            <select class="form-select">
                                <option>Tùy chọn 1</option>
                                <option>Tùy chọn 2</option>
                                <option>Tùy chọn 3</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Saved Themes -->
<div class="saved-themes">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Giao diện đã lưu</h3>
            <div class="card-actions">
                <button class="btn btn-primary" onclick="saveCurrentTheme()">
                    <i class="fas fa-save"></i> Lưu giao diện hiện tại
                </button>
                <button class="btn btn-info" onclick="importTheme()">
                    <i class="fas fa-upload"></i> Nhập giao diện
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <div class="themes-grid" id="themesGrid">
                <!-- Saved themes will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
let currentTheme = {
    colorScheme: 'default',
    layout: 'default',
    fontFamily: 'default',
    fontSize: 'medium',
    lineHeight: 'normal',
    buttonStyle: 'default',
    cardStyle: 'default',
    inputStyle: 'default',
    animations: true,
    animationSpeed: 'normal',
    hoverEffects: true,
    transitions: true,
    parallax: true,
    highContrast: false,
    largeText: false,
    reduceMotion: false,
    focusIndicators: true,
    colorBlindness: 'none'
};

let savedThemes = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCurrentTheme();
    loadSavedThemes();
    setupEventListeners();
    applyTheme();
});

function setupEventListeners() {
    // Color scheme change
    document.querySelectorAll('input[name="colorScheme"]').forEach(radio => {
        radio.addEventListener('change', function() {
            currentTheme.colorScheme = this.value;
            toggleCustomColors();
            applyTheme();
        });
    });
    
    // Layout change
    document.querySelectorAll('input[name="layout"]').forEach(radio => {
        radio.addEventListener('change', function() {
            currentTheme.layout = this.value;
            applyTheme();
        });
    });
}

function loadCurrentTheme() {
    const saved = localStorage.getItem('userAppearanceTheme');
    if (saved) {
        currentTheme = { ...currentTheme, ...JSON.parse(saved) };
        
        // Update form values
        document.querySelector(`input[name="colorScheme"][value="${currentTheme.colorScheme}"]`).checked = true;
        document.querySelector(`input[name="layout"][value="${currentTheme.layout}"]`).checked = true;
        document.getElementById('fontFamily').value = currentTheme.fontFamily;
        document.getElementById('fontSize').value = currentTheme.fontSize;
        document.getElementById('lineHeight').value = currentTheme.lineHeight;
        document.getElementById('buttonStyle').value = currentTheme.buttonStyle;
        document.getElementById('cardStyle').value = currentTheme.cardStyle;
        document.getElementById('inputStyle').value = currentTheme.inputStyle;
        document.getElementById('enableAnimations').checked = currentTheme.animations;
        document.getElementById('animationSpeed').value = currentTheme.animationSpeed;
        document.getElementById('enableHoverEffects').checked = currentTheme.hoverEffects;
        document.getElementById('enableTransitions').checked = currentTheme.transitions;
        document.getElementById('enableParallax').checked = currentTheme.parallax;
        document.getElementById('highContrast').checked = currentTheme.highContrast;
        document.getElementById('largeText').checked = currentTheme.largeText;
        document.getElementById('reduceMotion').checked = currentTheme.reduceMotion;
        document.getElementById('focusIndicators').checked = currentTheme.focusIndicators;
        document.getElementById('colorBlindness').value = currentTheme.colorBlindness;
        
        toggleCustomColors();
    }
}

function toggleCustomColors() {
    const customColors = document.getElementById('customColors');
    if (currentTheme.colorScheme === 'custom') {
        customColors.style.display = 'block';
    } else {
        customColors.style.display = 'none';
    }
}

function updateCustomTheme() {
    if (currentTheme.colorScheme === 'custom') {
        const primaryColor = document.getElementById('primaryColor').value;
        const secondaryColor = document.getElementById('secondaryColor').value;
        const backgroundColor = document.getElementById('backgroundColor').value;
        const textColor = document.getElementById('textColor').value;
        
        // Apply custom colors
        document.documentElement.style.setProperty('--primary-color', primaryColor);
        document.documentElement.style.setProperty('--secondary-color', secondaryColor);
        document.documentElement.style.setProperty('--background-color', backgroundColor);
        document.documentElement.style.setProperty('--text-color', textColor);
    }
}

function updateTypography() {
    const fontFamily = document.getElementById('fontFamily').value;
    const fontSize = document.getElementById('fontSize').value;
    const lineHeight = document.getElementById('lineHeight').value;
    
    currentTheme.fontFamily = fontFamily;
    currentTheme.fontSize = fontSize;
    currentTheme.lineHeight = lineHeight;
    
    applyTheme();
}

function updateComponentStyle() {
    const buttonStyle = document.getElementById('buttonStyle').value;
    const cardStyle = document.getElementById('cardStyle').value;
    const inputStyle = document.getElementById('inputStyle').value;
    
    currentTheme.buttonStyle = buttonStyle;
    currentTheme.cardStyle = cardStyle;
    currentTheme.inputStyle = inputStyle;
    
    applyTheme();
}

function updateAnimationSettings() {
    const animations = document.getElementById('enableAnimations').checked;
    const animationSpeed = document.getElementById('animationSpeed').value;
    const hoverEffects = document.getElementById('enableHoverEffects').checked;
    const transitions = document.getElementById('enableTransitions').checked;
    const parallax = document.getElementById('enableParallax').checked;
    
    currentTheme.animations = animations;
    currentTheme.animationSpeed = animationSpeed;
    currentTheme.hoverEffects = hoverEffects;
    currentTheme.transitions = transitions;
    currentTheme.parallax = parallax;
    
    applyTheme();
}

function updateAccessibility() {
    const highContrast = document.getElementById('highContrast').checked;
    const largeText = document.getElementById('largeText').checked;
    const reduceMotion = document.getElementById('reduceMotion').checked;
    const focusIndicators = document.getElementById('focusIndicators').checked;
    const colorBlindness = document.getElementById('colorBlindness').value;
    
    currentTheme.highContrast = highContrast;
    currentTheme.largeText = largeText;
    currentTheme.reduceMotion = reduceMotion;
    currentTheme.focusIndicators = focusIndicators;
    currentTheme.colorBlindness = colorBlindness;
    
    applyTheme();
}

function applyTheme() {
    const root = document.documentElement;
    
    // Apply color scheme
    if (currentTheme.colorScheme === 'custom') {
        updateCustomTheme();
    } else {
        // Reset custom colors
        root.style.removeProperty('--primary-color');
        root.style.removeProperty('--secondary-color');
        root.style.removeProperty('--background-color');
        root.style.removeProperty('--text-color');
    }
    
    // Apply layout
    document.body.className = `layout-${currentTheme.layout}`;
    
    // Apply typography
    if (currentTheme.fontFamily !== 'default') {
        root.style.setProperty('--font-family', getFontFamilyValue(currentTheme.fontFamily));
    } else {
        root.style.removeProperty('--font-family');
    }
    
    root.style.setProperty('--font-size', getFontSizeValue(currentTheme.fontSize));
    root.style.setProperty('--line-height', getLineHeightValue(currentTheme.lineHeight));
    
    // Apply component styles
    root.style.setProperty('--button-style', currentTheme.buttonStyle);
    root.style.setProperty('--card-style', currentTheme.cardStyle);
    root.style.setProperty('--input-style', currentTheme.inputStyle);
    
    // Apply animations
    if (!currentTheme.animations) {
        root.style.setProperty('--animation-duration', '0s');
    } else {
        root.style.setProperty('--animation-duration', getAnimationSpeedValue(currentTheme.animationSpeed));
    }
    
    if (!currentTheme.hoverEffects) {
        root.style.setProperty('--hover-effects', 'none');
    } else {
        root.style.setProperty('--hover-effects', 'auto');
    }
    
    if (!currentTheme.transitions) {
        root.style.setProperty('--transition-duration', '0s');
    } else {
        root.style.setProperty('--transition-duration', '0.3s');
    }
    
    // Apply accessibility
    if (currentTheme.highContrast) {
        root.style.setProperty('--contrast-ratio', '4.5:1');
    } else {
        root.style.removeProperty('--contrast-ratio');
    }
    
    if (currentTheme.largeText) {
        root.style.setProperty('--font-size-multiplier', '1.2');
    } else {
        root.style.removeProperty('--font-size-multiplier');
    }
    
    if (currentTheme.reduceMotion) {
        root.style.setProperty('--animation-duration', '0s');
        root.style.setProperty('--transition-duration', '0s');
    }
    
    if (currentTheme.focusIndicators) {
        root.style.setProperty('--focus-visible', 'auto');
    } else {
        root.style.setProperty('--focus-visible', 'none');
    }
    
    // Apply color blindness support
    if (currentTheme.colorBlindness !== 'none') {
        root.style.setProperty('--color-blindness', currentTheme.colorBlindness);
    } else {
        root.style.removeProperty('--color-blindness');
    }
    
    // Save to localStorage
    localStorage.setItem('userAppearanceTheme', JSON.stringify(currentTheme));
}

function getFontFamilyValue(fontFamily) {
    const fonts = {
        'roboto': '"Roboto", sans-serif',
        'open-sans': '"Open Sans", sans-serif',
        'lato': '"Lato", sans-serif',
        'poppins': '"Poppins", sans-serif',
        'inter': '"Inter", sans-serif'
    };
    return fonts[fontFamily] || 'inherit';
}

function getFontSizeValue(fontSize) {
    const sizes = {
        'small': '0.875rem',
        'medium': '1rem',
        'large': '1.125rem',
        'extra-large': '1.25rem'
    };
    return sizes[fontSize] || '1rem';
}

function getLineHeightValue(lineHeight) {
    const heights = {
        'tight': '1.25',
        'normal': '1.5',
        'relaxed': '1.75',
        'loose': '2'
    };
    return heights[lineHeight] || '1.5';
}

function getAnimationSpeedValue(speed) {
    const speeds = {
        'slow': '0.5s',
        'normal': '0.3s',
        'fast': '0.15s'
    };
    return speeds[speed] || '0.3s';
}

function saveAppearanceSettings() {
    applyTheme();
    showNotification('Đã lưu cài đặt giao diện', 'success');
}

function resetToDefault() {
    if (confirm('Bạn có chắc chắn muốn khôi phục về mặc định?')) {
        currentTheme = {
            colorScheme: 'default',
            layout: 'default',
            fontFamily: 'default',
            fontSize: 'medium',
            lineHeight: 'normal',
            buttonStyle: 'default',
            cardStyle: 'default',
            inputStyle: 'default',
            animations: true,
            animationSpeed: 'normal',
            hoverEffects: true,
            transitions: true,
            parallax: true,
            highContrast: false,
            largeText: false,
            reduceMotion: false,
            focusIndicators: true,
            colorBlindness: 'none'
        };
        
        loadCurrentTheme();
        applyTheme();
        showNotification('Đã khôi phục về mặc định', 'success');
    }
}

function previewAppearance() {
    document.getElementById('previewPanel').style.display = 'block';
    applyTheme();
}

function closePreview() {
    document.getElementById('previewPanel').style.display = 'none';
}

function saveCurrentTheme() {
    const themeName = prompt('Nhập tên cho giao diện này:');
    if (themeName && themeName.trim()) {
        const theme = {
            id: Date.now(),
            name: themeName.trim(),
            settings: { ...currentTheme },
            created_at: new Date().toISOString()
        };
        
        savedThemes.push(theme);
        localStorage.setItem('savedThemes', JSON.stringify(savedThemes));
        loadSavedThemes();
        showNotification('Đã lưu giao diện', 'success');
    }
}

function loadSavedThemes() {
    const saved = localStorage.getItem('savedThemes');
    if (saved) {
        savedThemes = JSON.parse(saved);
        renderSavedThemes();
    }
}

function renderSavedThemes() {
    const grid = document.getElementById('themesGrid');
    
    if (savedThemes.length === 0) {
        grid.innerHTML = `
            <div class="no-themes">
                <i class="fas fa-palette"></i>
                <p>Chưa có giao diện nào được lưu</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = savedThemes.map(theme => `
        <div class="theme-card">
            <div class="theme-header">
                <h5>${theme.name}</h5>
                <span class="theme-date">${formatDate(theme.created_at)}</span>
            </div>
            <div class="theme-preview">
                <div class="preview-color" style="background: var(--primary-color, #3b82f6)"></div>
                <div class="preview-color" style="background: var(--secondary-color, #6b7280)"></div>
                <div class="preview-color" style="background: var(--background-color, #ffffff)"></div>
            </div>
            <div class="theme-actions">
                <button class="btn btn-sm btn-outline" onclick="loadTheme(${theme.id})" title="Áp dụng">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-outline" onclick="exportTheme(${theme.id})" title="Xuất">
                    <i class="fas fa-download"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteTheme(${theme.id})" title="Xóa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function loadTheme(themeId) {
    const theme = savedThemes.find(t => t.id === themeId);
    if (theme) {
        currentTheme = { ...theme.settings };
        loadCurrentTheme();
        applyTheme();
        showNotification(`Đã áp dụng giao diện "${theme.name}"`, 'success');
    }
}

function exportTheme(themeId) {
    const theme = savedThemes.find(t => t.id === themeId);
    if (theme) {
        const dataStr = JSON.stringify(theme, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `${theme.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_theme.json`;
        link.click();
        
        URL.revokeObjectURL(url);
        showNotification('Đã xuất giao diện', 'success');
    }
}

function deleteTheme(themeId) {
    const theme = savedThemes.find(t => t.id === themeId);
    if (theme && confirm(`Bạn có chắc chắn muốn xóa giao diện "${theme.name}"?`)) {
        savedThemes = savedThemes.filter(t => t.id !== themeId);
        localStorage.setItem('savedThemes', JSON.stringify(savedThemes));
        loadSavedThemes();
        showNotification('Đã xóa giao diện', 'success');
    }
}

function importTheme() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const theme = JSON.parse(e.target.result);
                    if (theme.name && theme.settings) {
                        savedThemes.push({
                            ...theme,
                            id: Date.now(),
                            created_at: new Date().toISOString()
                        });
                        localStorage.setItem('savedThemes', JSON.stringify(savedThemes));
                        loadSavedThemes();
                        showNotification('Đã nhập giao diện thành công', 'success');
                    } else {
                        showNotification('File không hợp lệ', 'error');
                    }
                } catch (error) {
                    showNotification('Không thể đọc file', 'error');
                }
            };
            reader.readAsText(file);
        }
    };
    input.click();
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function showNotification(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}

// Close preview when clicking outside
window.onclick = function(event) {
    const previewPanel = document.getElementById('previewPanel');
    if (event.target === previewPanel) {
        previewPanel.style.display = 'none';
    }
}
</script>
