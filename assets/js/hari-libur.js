document.addEventListener('DOMContentLoaded', function () {
  function showModalById(id) {
    var el = document.getElementById(id);
    if (!el) return;
    if (window.bootstrap && bootstrap.Modal) {
      var m = bootstrap.Modal.getOrCreateInstance(el);
      m.show();
    }
  }
  document.querySelectorAll('button[data-bs-target="#modalAdd"]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      showModalById('modalAdd');
    });
  });
  document.querySelectorAll('button[data-bs-target="#modalEdit"]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      var id = btn.getAttribute('data-id');
      var tanggal = btn.getAttribute('data-tanggal');
      var nama = btn.getAttribute('data-nama');
      var jenis = btn.getAttribute('data-jenis');
      var ket = btn.getAttribute('data-keterangan');
      var act = btn.getAttribute('data-active') === '1';
      document.getElementById('edit_id').value = id || '';
      document.getElementById('edit_tanggal').value = tanggal || '';
      document.getElementById('edit_nama').value = nama || '';
      document.getElementById('edit_jenis').value = jenis || '';
      document.getElementById('edit_keterangan').value = ket || '';
      document.getElementById('edit_active').checked = !!act;
      showModalById('modalEdit');
    });
  });
  document.querySelectorAll('button[data-bs-target="#modalDelete"]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      var id = btn.getAttribute('data-id');
      var did = document.getElementById('del_id');
      if (did) did.value = id || '';
      showModalById('modalDelete');
    });
  });
});
