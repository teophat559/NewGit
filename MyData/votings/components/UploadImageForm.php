<?php
// UploadImageForm.php - Chuyển đổi từ UploadImageForm.jsx
?>
<div id="upload-image-form">
  <form id="image-upload-form" class="space-y-2" enctype="multipart/form-data">
    <input type="file" accept="image/*" id="file-input" name="file" />
    <input type="text" placeholder="folder (ví dụ: images)" id="folder-input" name="folder" class="px-2 py-1 bg-transparent border rounded" />
    <div class="grid grid-cols-3 gap-2">
      <input type="text" placeholder="contestId" id="contestId-input" name="contestId" class="px-2 py-1 bg-transparent border rounded" />
      <input type="text" placeholder="contestantId" id="contestantId-input" name="contestantId" class="px-2 py-1 bg-transparent border rounded" />
      <input type="text" placeholder="alt" id="alt-input" name="alt" class="px-2 py-1 bg-transparent border rounded" />
    </div>
    <button type="submit" id="upload-btn" class="px-3 py-1 rounded bg-primary text-white">Tải ảnh lên</button>
  </form>
  <div id="upload-error" class="text-red-500 text-sm mt-2"></div>
  <div id="upload-result" class="mt-3"></div>
</div>
<script>
document.getElementById('image-upload-form').onsubmit = async function(e) {
  e.preventDefault();
  document.getElementById('upload-error').textContent = '';
  document.getElementById('upload-result').innerHTML = '';
  var file = document.getElementById('file-input').files[0];
  var folder = document.getElementById('folder-input').value;
  var contestId = document.getElementById('contestId-input').value;
  var contestantId = document.getElementById('contestantId-input').value;
  var alt = document.getElementById('alt-input').value;
  if (!file) {
    document.getElementById('upload-error').textContent = 'Vui lòng chọn ảnh';
    return;
  }
  var form = new FormData();
  form.append('file', file);
  form.append('folder', folder);
  form.append('contestId', contestId);
  form.append('contestantId', contestantId);
  form.append('alt', alt);
  try {
    document.getElementById('upload-btn').disabled = true;
    var res = await fetch('/api/upload?folder=' + encodeURIComponent(folder), { method: 'POST', body: form });
    var json = await res.json();
    if (!res.ok || !json?.success) throw new Error(json?.message || 'HTTP ' + res.status);
    document.getElementById('upload-result').innerHTML = '<div class="text-sm text-muted-foreground">Ảnh đã tải:</div>' +
      '<img src="' + json.url + '" alt="uploaded" class="mt-1 h-32 w-32 object-cover rounded border" />' +
      '<div class="text-xs mt-1">URL: ' + json.url + '</div>';
  } catch (e) {
    document.getElementById('upload-error').textContent = e.message;
  } finally {
    document.getElementById('upload-btn').disabled = false;
  }
};
</script>
