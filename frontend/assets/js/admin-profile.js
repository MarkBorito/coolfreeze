// Admin Profile interactions (frontend demo only: nothing is saved to a server)
document.addEventListener('DOMContentLoaded', function () {

    // ----- Mobile sidebar (uses the shared topbar's menu button) -----
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');
    var menuBtn = document.getElementById('menuButton');
    if (sidebar && menuBtn) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }
    if (overlay && sidebar) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // ----- Tabs -----
    var tabs = document.querySelectorAll('.tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.classList.remove('active'); });
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });

    // ----- Modals -----
    function openModal(id) {
        var m = document.getElementById(id);
        m.classList.add('open');
        m.setAttribute('aria-hidden', 'false');
    }
    function closeModal(m) {
        m.classList.remove('open');
        m.setAttribute('aria-hidden', 'true');
    }
    document.querySelectorAll('[data-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.open === 'editModal') showEditGroup(btn.dataset.group);
            openModal(btn.dataset.open);
        });
    });
    document.querySelectorAll('.modal').forEach(function (m) {
        m.addEventListener('click', function (e) { if (e.target === m) closeModal(m); });
        m.querySelectorAll('[data-close]').forEach(function (b) {
            b.addEventListener('click', function () { closeModal(m); });
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(closeModal);
    });

    // ----- Edit modal: show only the fields for the clicked section -----
    function showEditGroup(group) {
        document.querySelectorAll('#editForm label').forEach(function (label) {
            label.style.display = label.dataset.group.split(' ').indexOf(group) !== -1 ? '' : 'none';
        });
    }
    document.getElementById('editForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var data = new FormData(e.target);
        data.forEach(function (value, key) {
            document.querySelectorAll('[data-bind="' + key + '"]').forEach(function (el) {
                el.textContent = value;
            });
        });
        closeModal(document.getElementById('editModal'));
    });

    // ----- Photo preview (not uploaded) -----
    var photoInput = document.getElementById('photoInput');
    document.getElementById('cameraBtn').addEventListener('click', function () { photoInput.click(); });
    photoInput.addEventListener('change', function () {
        var file = photoInput.files[0];
        if (file && file.type.indexOf('image/') === 0) {
            document.getElementById('avatarImg').src = URL.createObjectURL(file);
        }
    });

    // ----- Eye icon: toggles a placeholder value only -----
    var pwDisplay = document.getElementById('pwDisplay');
    document.getElementById('pwEye').addEventListener('click', function (e) {
        var icon = e.currentTarget.querySelector('i');
        var hidden = icon.classList.contains('fa-eye-slash');
        pwDisplay.textContent = hidden ? pwDisplay.dataset.real : pwDisplay.dataset.mask;
        icon.className = hidden ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });

    // ----- Change password modal -----
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });
    });
    document.getElementById('passwordForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var cur = document.getElementById('pw_current').value;
        var nw = document.getElementById('pw_new').value;
        var conf = document.getElementById('pw_confirm').value;
        var msg = document.getElementById('pwMsg');
        msg.classList.remove('ok');
        if (!cur || !nw || !conf) { msg.textContent = 'Please fill in all fields.'; return; }
        if (nw !== conf) { msg.textContent = 'New password and confirmation do not match.'; return; }
        msg.classList.add('ok');
        msg.textContent = 'Demo only: your password was not changed.';
    });

    // ----- Login Security: Edit unlocks the switches and dropdown -----
    var body = document.getElementById('securityBody');
    var editBtn = document.getElementById('securityEdit');
    editBtn.addEventListener('click', function () {
        var locked = body.classList.toggle('locked');
        editBtn.querySelector('span').textContent = locked ? 'Edit' : 'Done';
    });
    document.querySelectorAll('.switch').forEach(function (sw) {
        sw.addEventListener('click', function () {
            var on = sw.classList.toggle('on');
            sw.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    });
});