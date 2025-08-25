<?php
// avatar.php - Chuyển đổi từ React sang PHP, giữ nguyên cấu trúc và UI
?>
<div class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full avatar-root">
  <img src="" alt="avatar" class="aspect-square h-full w-full avatar-image" />
  <div class="flex h-full w-full items-center justify-center rounded-full bg-muted avatar-fallback" style="display:none;">A</div>
</div>
<script>
function setAvatarImage(src) {
  var img = document.querySelector('.avatar-image');
  if (img) img.src = src;
}
function showAvatarFallback(show, text) {
  var fallback = document.querySelector('.avatar-fallback');
  if (fallback) {
    fallback.style.display = show ? '' : 'none';
    fallback.textContent = text || 'A';
  }
}
</script>
