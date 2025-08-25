<?php
// StatsWidget.php - Chuyển đổi từ React sang PHP, giữ nguyên logic và UI
?>
<div class="cyber-card-bg h-full flex flex-col">
  <div class="pb-2">
    <div class="text-lg font-bold text-white flex items-center">
      <span class="mr-2" style="color:#06b6d4;">📊</span>
      Thống Kê
    </div>
  </div>
  <div class="flex-grow flex flex-col justify-center space-y-3" id="stats-list">
    <!-- Thống kê sẽ được render bằng JS -->
  </div>
</div>
<script>
function renderStatsWidget(historyData) {
  var stats = {
    success: historyData.filter(function(item){return item.status.startsWith('✅');}).length,
    approval: historyData.filter(function(item){return item.status.startsWith('🟡 Chờ phê duyệt');}).length,
    otp: historyData.filter(function(item){return item.status.startsWith('🟡 Chờ OTP');}).length,
    failed: historyData.filter(function(item){return item.status.startsWith('❌');}).length
  };
  var list = document.getElementById('stats-list');
  list.innerHTML = '';
  var items = [
    {icon:'✔️',label:'Thành công',value:stats.success,color:'green'},
    {icon:'⏰',label:'Chờ phê duyệt',value:stats.approval,color:'yellow'},
    {icon:'🛡️',label:'Chờ OTP',value:stats.otp,color:'blue'},
    {icon:'❌',label:'Sai mật khẩu',value:stats.failed,color:'red'}
  ];
  items.forEach(function(i){
    var div = document.createElement('div');
    div.className = 'flex items-center justify-between text-sm';
    div.innerHTML = `<div class="flex items-center"><span class="mr-2">${i.icon}</span><span class="text-gray-300">${i.label}</span></div><span class="font-bold text-white">${i.value}</span>`;
    list.appendChild(div);
  });
}
</script>
