document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('btnToggleSidebar');
  var sidebar = document.getElementById('appSidebar');
  if (btn && sidebar) {
    btn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
    });
  }

  // Konfirmasi hapus data
  document.querySelectorAll('.btn-confirm-delete').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm('Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.')) {
        e.preventDefault();
      }
    });
  });

  // Auto dismiss alert
  document.querySelectorAll('.alert-auto-dismiss').forEach(function (el) {
    setTimeout(function () {
      var alert = bootstrap.Alert.getOrCreateInstance(el);
      alert.close();
    }, 4000);
  });
});
