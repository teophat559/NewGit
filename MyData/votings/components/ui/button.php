<?php
// button.php - Chuyển đổi từ React sang PHP, giữ nguyên cấu trúc và UI
?>
<button class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 button-default"></button>
<script>
function setButtonProps(text, variant, size, disabled) {
  var btn = document.querySelector('.button-default');
  btn.textContent = text;
  btn.disabled = !!disabled;
  btn.className = 'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 button-default';
  if (variant === 'destructive') btn.classList.add('bg-destructive','text-destructive-foreground','hover:bg-destructive/90');
  else if (variant === 'outline') btn.classList.add('border','border-input','bg-background','hover:bg-accent','hover:text-accent-foreground');
  else if (variant === 'secondary') btn.classList.add('bg-secondary','text-secondary-foreground','hover:bg-secondary/80');
  else if (variant === 'ghost') btn.classList.add('hover:bg-accent','hover:text-accent-foreground');
  else if (variant === 'link') btn.classList.add('text-primary','underline-offset-4','hover:underline');
  else btn.classList.add('bg-primary','text-primary-foreground','hover:bg-primary/90');
  if (size === 'sm') btn.classList.add('h-9','rounded-md','px-3');
  else if (size === 'lg') btn.classList.add('h-11','rounded-md','px-8');
  else if (size === 'icon') btn.classList.add('h-10','w-10');
  else if (size === 'icon_sm') btn.classList.add('h-8','w-8');
  else btn.classList.add('h-10','px-4','py-2');
}
</script>
