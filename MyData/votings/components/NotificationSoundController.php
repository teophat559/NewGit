
<?php
// NotificationSoundController.php - Chuyển đổi từ NotificationSoundController.jsx
?>
<div class="notification-sound-controller">
  <audio id="notification-audio" src="" style="display:none;"></audio>
</div>
<script>
// Danh sách âm thanh
const soundOptions = {
  'default': '/sounds/notification.mp3',
  'user_login': '/sounds/user_login.mp3',
  'admin_login': '/sounds/admin_login.mp3'
};

// Cài đặt mặc định
let soundSettings = {
  adminSoundEnabled: true,
  userSoundEnabled: true,
  adminSound: soundOptions['admin_login'],
  userSound: soundOptions['user_login']
};

// Tải cài đặt từ localStorage nếu có
try {
  const saved = localStorage.getItem('notificationSoundSettings');
  if (saved) {
    soundSettings = Object.assign(soundSettings, JSON.parse(saved));
  }
} catch(e) {}

let lastNotificationId = null;

function playNotificationSound(event, id) {
  let soundEnabled = false;
  let soundSrc = '';
  if (event === 'user_visit' || event === 'user_login') {
    soundEnabled = soundSettings.userSoundEnabled;
    soundSrc = soundSettings.userSound;
  } else if (event === 'admin_login') {
    soundEnabled = soundSettings.adminSoundEnabled;
    soundSrc = soundSettings.adminSound;
  }
  if (soundEnabled && soundSrc) {
    const audio = document.getElementById('notification-audio');
    audio.src = soundSrc;
    audio.play().catch(()=>{});
    lastNotificationId = id;
  }
}

function checkNotification() {
  try {
    const notificationsRaw = localStorage.getItem('systemNotifications');
    if (!notificationsRaw) return;
    const notifications = JSON.parse(notificationsRaw);
    if (notifications.length > 0) {
      const latest = notifications[0];
      if (latest.id !== lastNotificationId) {
        playNotificationSound(latest.event, latest.id);
      }
    }
  } catch(e) {}
}

window.addEventListener('storage', checkNotification);
setInterval(checkNotification, 1000);
</script>
<script>
function playNotificationSound() {
  var audio = new Audio('../assets/notification.mp3');
  audio.play();
}
</script>
