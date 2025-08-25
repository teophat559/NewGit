<?php
// USClock.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="flex flex-col items-center justify-center h-full text-white">
  <div id="us-clock-time" class="font-mono text-5xl tracking-widest" style="text-shadow: 0 0 5px #10b981, 0 0 10px #10b981, 0 0 15px #10b981;"></div>
  <div id="us-clock-date" class="text-sm text-slate-400 mt-1"></div>
</div>
<script>
function updateUSClock() {
  var now = new Date();
  var timeOptions = { timeZone: 'America/New_York', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
  var dateOptions = { timeZone: 'America/New_York', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  document.getElementById('us-clock-time').textContent = new Intl.DateTimeFormat('en-US', timeOptions).format(now);
  document.getElementById('us-clock-date').textContent = new Intl.DateTimeFormat('vi-VN', dateOptions).format(now) + ' (UTC-4)';
}
setInterval(updateUSClock, 1000);
document.addEventListener('DOMContentLoaded', updateUSClock);
</script>
