<?php
// ContestsPage.php - Trang quản lý cuộc thi
$pageTitle = 'Quản lý Cuộc thi';
?>

<div class="content-header">
    <h1><i class="fas fa-trophy"></i> Quản lý Cuộc thi</h1>
    <p>Quản lý danh sách cuộc thi, thêm mới, chỉnh sửa và xóa cuộc thi</p>
</div>

<!-- Action Buttons -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thao tác</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="showCreateContestModal()">
                <i class="fas fa-plus"></i> Thêm cuộc thi mới
            </button>
            <button class="btn btn-secondary" onclick="refreshContests()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
        </div>
    </div>
</div>

<!-- Contests Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách cuộc thi</h3>
        <div class="card-actions">
            <input type="text" id="searchContest" class="form-input" placeholder="Tìm kiếm cuộc thi..." style="width: 250px;">
        </div>
    </div>

    <div class="table-container">
        <table class="table" id="contestsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Trạng thái</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th>Số thí sinh</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="contestsTableBody">
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Contest Modal -->
<div id="contestModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Thêm cuộc thi mới</h3>
            <span class="close" onclick="closeContestModal()">&times;</span>
        </div>

        <form id="contestForm" class="contest-form">
            <div class="modal-body">
                <input type="hidden" id="contestId" name="id">

                <div class="form-group">
                    <label for="contestTitle" class="form-label">Tiêu đề cuộc thi *</label>
                    <input type="text" id="contestTitle" name="title" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="contestDescription" class="form-label">Mô tả</label>
                    <textarea id="contestDescription" name="description" class="form-input form-textarea" rows="4"></textarea>
                </div>

                <div class="row" style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="startDate" class="form-label">Ngày bắt đầu *</label>
                        <input type="datetime-local" id="startDate" name="start_date" class="form-input" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="endDate" class="form-label">Ngày kết thúc *</label>
                        <input type="datetime-local" id="endDate" name="end_date" class="form-input" required>
                    </div>
                </div>

                <div class="row" style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="maxContestants" class="form-label">Số thí sinh tối đa</label>
                        <input type="number" id="maxContestants" name="max_contestants" class="form-input" min="1" value="100">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="contestStatus" class="form-label">Trạng thái</label>
                        <select id="contestStatus" name="status" class="form-input form-select">
                            <option value="draft">Nháp</option>
                            <option value="active">Hoạt động</option>
                            <option value="voting">Đang bình chọn</option>
                            <option value="ended">Kết thúc</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="votingRules" class="form-label">Quy tắc bình chọn</label>
                    <textarea id="votingRules" name="voting_rules" class="form-input form-textarea" rows="3" placeholder="Nhập quy tắc bình chọn..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeContestModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Styles -->
<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: modalSlideIn 0.3s ease;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

<script>
let contests = [];
let currentContest = null;

// Load contests on page load
document.addEventListener('DOMContentLoaded', function() {
    loadContests();
    setupEventListeners();
});

function setupEventListeners() {
    // Search functionality
    document.getElementById('searchContest').addEventListener('input', function(e) {
        filterContests(e.target.value);
    });

    // Form submission
    document.getElementById('contestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveContest();
    });
}

async function loadContests() {
    try {
        const response = await window.app.apiCall('contests/list', 'GET');
        if (response.success) {
            contests = response.contests || [];
            renderContestsTable();
        } else {
            showNotification('Không thể tải danh sách cuộc thi', 'error');
        }
    } catch (error) {
        console.error('Failed to load contests:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderContestsTable() {
    const tbody = document.getElementById('contestsTableBody');

    if (contests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> Không có cuộc thi nào
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = contests.map(contest => `
        <tr>
            <td>${contest.id}</td>
            <td>
                <strong>${contest.title}</strong>
                ${contest.description ? `<br><small class="text-muted">${contest.description.substring(0, 100)}${contest.description.length > 100 ? '...' : ''}</small>` : ''}
            </td>
            <td>
                <span class="badge badge-${getStatusBadgeClass(contest.status)}">
                    ${getStatusText(contest.status)}
                </span>
            </td>
            <td>${formatDate(contest.start_date)}</td>
            <td>${formatDate(contest.end_date)}</td>
            <td>
                <span class="badge badge-info">${contest.contestant_count || 0}</span>
            </td>
            <td>
                <div class="btn-group" style="display: flex; gap: 5px;">
                    <button class="btn btn-sm btn-outline" onclick="editContest(${contest.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="viewContest(${contest.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteContest(${contest.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterContests(searchTerm) {
    const filtered = contests.filter(contest =>
        contest.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        contest.description?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        contest.status.toLowerCase().includes(searchTerm.toLowerCase())
    );

    renderFilteredContests(filtered);
}

function renderFilteredContests(filteredContests) {
    const tbody = document.getElementById('contestsTableBody');

    if (filteredContests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-search"></i> Không tìm thấy cuộc thi nào
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredContests.map(contest => `
        <tr>
            <td>${contest.id}</td>
            <td>
                <strong>${contest.title}</strong>
                ${contest.description ? `<br><small class="text-muted">${contest.description.substring(0, 100)}${contest.description.length > 100 ? '...' : ''}</small>` : ''}
            </td>
            <td>
                <span class="badge badge-${getStatusBadgeClass(contest.status)}">
                    ${getStatusText(contest.status)}
                </span>
            </td>
            <td>${formatDate(contest.start_date)}</td>
            <td>${formatDate(contest.end_date)}</td>
            <td>
                <span class="badge badge-info">${contest.contestant_count || 0}</span>
            </td>
            <td>
                <div class="btn-group" style="display: flex; gap: 5px;">
                    <button class="btn btn-sm btn-outline" onclick="editContest(${contest.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="viewContest(${contest.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteContest(${contest.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showCreateContestModal() {
    currentContest = null;
    document.getElementById('modalTitle').textContent = 'Thêm cuộc thi mới';
    document.getElementById('contestForm').reset();
    document.getElementById('contestId').value = '';

    // Set default dates
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    const nextWeek = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);

    document.getElementById('startDate').value = tomorrow.toISOString().slice(0, 16);
    document.getElementById('endDate').value = nextWeek.toISOString().slice(0, 16);

    document.getElementById('contestModal').style.display = 'block';
}

function editContest(contestId) {
    const contest = contests.find(c => c.id === contestId);
    if (!contest) return;

    currentContest = contest;
    document.getElementById('modalTitle').textContent = 'Chỉnh sửa cuộc thi';

    // Fill form with contest data
    document.getElementById('contestId').value = contest.id;
    document.getElementById('contestTitle').value = contest.title;
    document.getElementById('contestDescription').value = contest.description || '';
    document.getElementById('startDate').value = contest.start_date.slice(0, 16);
    document.getElementById('endDate').value = contest.end_date.slice(0, 16);
    document.getElementById('maxContestants').value = contest.max_contestants || 100;
    document.getElementById('contestStatus').value = contest.status;
    document.getElementById('votingRules').value = contest.voting_rules || '';

    document.getElementById('contestModal').style.display = 'block';
}

function closeContestModal() {
    document.getElementById('contestModal').style.display = 'none';
    currentContest = null;
}

async function saveContest() {
    const form = document.getElementById('contestForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Đang lưu...';

    try {
        const data = {
            title: formData.get('title'),
            description: formData.get('description'),
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            max_contestants: parseInt(formData.get('max_contestants')),
            status: formData.get('status'),
            voting_rules: formData.get('voting_rules')
        };

        const endpoint = currentContest ? `contests/update/${currentContest.id}` : 'contests/create';
        const method = currentContest ? 'PUT' : 'POST';

        const response = await window.app.apiCall(endpoint, method, data);

        if (response.success) {
            showNotification(
                currentContest ? 'Cập nhật cuộc thi thành công' : 'Tạo cuộc thi thành công',
                'success'
            );
            closeContestModal();
            loadContests();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to save contest:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        // Hide loading
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

async function deleteContest(contestId) {
    if (!confirm('Bạn có chắc chắn muốn xóa cuộc thi này?')) return;

    try {
        const response = await window.app.apiCall(`contests/delete/${contestId}`, 'DELETE');

        if (response.success) {
            showNotification('Xóa cuộc thi thành công', 'success');
            loadContests();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete contest:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function viewContest(contestId) {
    // Redirect to contest detail page
    window.location.href = `?page=ContestDetailPage&id=${contestId}`;
}

function refreshContests() {
    loadContests();
    document.getElementById('searchContest').value = '';
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'active': return 'success';
        case 'draft': return 'secondary';
        case 'voting': return 'warning';
        case 'ended': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'active': return 'Hoạt động';
        case 'draft': return 'Nháp';
        case 'voting': return 'Đang bình chọn';
        case 'ended': return 'Kết thúc';
        default: return status;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return window.formatDate ? window.formatDate(dateString) : new Date(dateString).toLocaleDateString('vi-VN');
}

function showNotification(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('contestModal');
    if (event.target === modal) {
        closeContestModal();
    }
}
</script>
