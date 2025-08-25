<?php
// SendNotificationDialog.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div id="send-notification-dialog" style="display:none;">
  <div class="dialog-content bg-[#0f0c29] border-purple-800 text-slate-50" style="max-width:425px;">
    <div class="dialog-header">
      <div class="dialog-title text-white">Gửi Thông Báo Mới</div>
      <div class="dialog-description text-slate-400">Soạn và gửi một cảnh báo mới đến hệ thống.</div>
    </div>
    <div class="grid gap-4 py-4">
      <div class="space-y-2">
        <label for="template" class="text-slate-400">Chọn Mẫu (Tùy chọn)</label>
        <select id="template-select" class="bg-slate-800/50 border-slate-700"></select>
      </div>
      <div class="space-y-2">
        <label for="type" class="text-slate-400">Loại Thông Báo</label>
        <select id="type-select" class="bg-slate-800/50 border-slate-700">
          <option value="info">Info</option>
          <option value="success">Success</option>
          <option value="warning">Warning</option>
          <option value="error">Error</option>
        </select>
      </div>
      <div class="space-y-2">
        <label for="message" class="text-slate-400">Nội dung</label>
        <textarea id="message-area" class="bg-slate-800/50 border-slate-700"></textarea>
      </div>
    </div>
    <div class="dialog-footer">
      <button id="send-notification-btn" class="glowing-button-cyber">Gửi</button>
    </div>
  </div>
</div>
<script>
var notificationTemplates = [];
function openSendNotificationDialog() {
  document.getElementById('send-notification-dialog').style.display = '';
  // Load templates từ localStorage
  try {
    var savedTemplates = localStorage.getItem('notificationTemplatesList');
    if (savedTemplates) notificationTemplates = JSON.parse(savedTemplates);
  } catch (e) { notificationTemplates = []; }
  var select = document.getElementById('template-select');
  select.innerHTML = '<option value="">Chọn một mẫu có sẵn</option>';
  notificationTemplates.forEach(function(t){
    var opt = document.createElement('option');
    opt.value = t.id;
    opt.textContent = t.title;
    select.appendChild(opt);
  });
  document.getElementById('type-select').value = 'info';
  document.getElementById('message-area').value = '';
}
document.getElementById('template-select').onchange = function(){
  var id = this.value;
  var t = notificationTemplates.find(function(x){return x.id==id;});
  if (t) document.getElementById('message-area').value = t.message;
};
document.getElementById('send-notification-btn').onclick = function(){
  var type = document.getElementById('type-select').value;
  var message = document.getElementById('message-area').value;
  // ...logic gửi thông báo...
  alert('Đã gửi thông báo: '+type+' - '+message);
  document.getElementById('send-notification-dialog').style.display = 'none';
};
</script>
