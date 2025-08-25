<?php
// checkbox.php - Chuyển đổi từ React sang PHP, giữ nguyên cấu trúc và UI
?>
<input type="checkbox" class="peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-checked:bg-primary data-checked:text-primary-foreground checkbox-default" />
<span class="flex items-center justify-center text-current checkbox-indicator" style="display:none;">✔️</span>
<script>
function setCheckboxState(checked) {
  var cb = document.querySelector('.checkbox-default');
  var indicator = document.querySelector('.checkbox-indicator');
  cb.checked = !!checked;
  indicator.style.display = checked ? '' : 'none';
}
</script>
