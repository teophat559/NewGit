<?php
// badge.php - Chuyển đổi từ React sang PHP, giữ nguyên cấu trúc và UI
?>
<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors badge-default"></span>
<script>
function setBadgeText(text, variant) {
  var badge = document.querySelector('.badge-default');
  badge.textContent = text;
  badge.className = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors badge-default';
  if (variant === 'secondary') badge.classList.add('bg-secondary','text-secondary-foreground');
  else if (variant === 'destructive') badge.classList.add('bg-destructive','text-destructive-foreground');
  else if (variant === 'outline') badge.classList.add('text-foreground');
  else badge.classList.add('bg-primary','text-primary-foreground');
}
</script>
