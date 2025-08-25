<?php
// card.php - Chuyển đổi từ React sang PHP, giữ nguyên cấu trúc và UI
?>
<div class="rounded-lg border bg-card text-card-foreground shadow-sm card-root">
  <div class="flex flex-col space-y-1.5 p-6 card-header"></div>
  <h3 class="text-2xl font-semibold leading-none tracking-tight card-title"></h3>
  <p class="text-sm text-muted-foreground card-description"></p>
  <div class="p-6 pt-0 card-content"></div>
  <div class="flex items-center p-6 pt-0 card-footer"></div>
</div>
<script>
function setCardHeader(text) {
  document.querySelector('.card-header').textContent = text;
}
function setCardTitle(text) {
  document.querySelector('.card-title').textContent = text;
}
function setCardDescription(text) {
  document.querySelector('.card-description').textContent = text;
}
function setCardContent(html) {
  document.querySelector('.card-content').innerHTML = html;
}
function setCardFooter(html) {
  document.querySelector('.card-footer').innerHTML = html;
}
</script>
