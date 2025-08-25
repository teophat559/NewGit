<?php
// WaitingScreen.php - Chuyển đổi từ WaitingScreen.jsx
?>
<div class="waiting-screen">
  <p>Vui lòng chờ...</p>
  <div class="spinner"></div>
</div>
<style>
/* Fixed: removed stray HTML that caused CSS syntax errors */
.waiting-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 200px;
  font-family: sans-serif;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;          /* light gray */
  border-top-color: #2563eb;           /* blue segment */
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* If you want the more detailed waiting card UI from the broken snippet,
   move that HTML OUTSIDE this style tag (do not place HTML inside CSS). */
</style>
  100% { transform: rotate(360deg); }
}
</style>
