<?php
// alert-dialog.php - Chuyển đổi từ React sang PHP, giữ nguyên cấu trúc và UI
?>
<div id="alert-dialog-overlay" class="fixed inset-0 z-50 bg-black/80" style="display:none;"></div>
<div id="alert-dialog-content" class="fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-background p-6 shadow-lg sm:rounded-lg" style="display:none;">
  <div id="alert-dialog-header" class="flex flex-col space-y-2 text-center sm:text-left">
    <div id="alert-dialog-title" class="text-lg font-semibold">Tiêu đề</div>
    <div id="alert-dialog-description" class="text-sm text-muted-foreground">Mô tả</div>
  </div>
  <div id="alert-dialog-footer" class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
    <button id="alert-dialog-cancel" class="mt-2 sm:mt-0">Hủy</button>
    <button id="alert-dialog-action">Đồng ý</button>
  </div>
</div>
<script>
function showAlertDialog(title, description, onAction, onCancel) {
  document.getElementById('alert-dialog-title').textContent = title;
  document.getElementById('alert-dialog-description').textContent = description;
  document.getElementById('alert-dialog-overlay').style.display = '';
  document.getElementById('alert-dialog-content').style.display = '';
  document.getElementById('alert-dialog-action').onclick = function(){
    if (onAction) onAction();
    closeAlertDialog();
  };
  document.getElementById('alert-dialog-cancel').onclick = function(){
    if (onCancel) onCancel();
    closeAlertDialog();
  };
}
function closeAlertDialog() {
  document.getElementById('alert-dialog-overlay').style.display = 'none';
  document.getElementById('alert-dialog-content').style.display = 'none';
}
</script>
