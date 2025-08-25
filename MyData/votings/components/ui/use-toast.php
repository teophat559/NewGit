<?php
// use-toast.php - Chuyển đổi từ React sang PHP, cung cấp hàm toast cho các component khác
?>
<?php
function useToast() {
  echo '<script>
    window.showToast = function(message, duration) {
      var toast = document.getElementById("toast");
      toast.textContent = message;
      toast.classList.remove("hidden");
      setTimeout(function(){ toast.classList.add("hidden"); }, duration || 2000);
    }
  </script>';
}
?>
