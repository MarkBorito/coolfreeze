// Admin Profile interactions. Everything is saved through backend/api/admin/profile.php
// (the page itself is filled by PHP from the database, see pages/admin/profile.php).
document.addEventListener('DOMContentLoaded', function () {
    var cfg = window.CF_PROFILE || {};
    var API = cfg.api;
    var $ = function (id) { return document.getElementById(id); };

    function toast(icon, text) {
        if (window.cfAlert) { cfAlert.toast(icon, text); } else { alert(text); }
    }
    function fail(text) {
        if (window.cfAlert) { cfAlert.error(text); } else { alert(text); }
    }
    function post(fields) {
        return window.cfPost(API, fields);
    }

    // ----- Mobile sidebar (uses the shared topbar's menu button) -----
    var sidebar = $('sidebar');
    var overlay = $('overlay');
    var menuBtn = $('menuButton');
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
            $(tab.dataset.tab).classList.add('active');
        });
    });

    // ----- Modals -----
    function openModal(id) {
        var m = $(id);
        m.classList.add('open');
        m.setAttribute('aria-hidden', 'false');
    }
    function closeModal(m) {
        m.classList.remove('open');
        m.setAttribute('aria-hidden', 'true');
    }

    // Values as last saved (the edit form is reset to these whenever it is opened)
    var saved = {};
    ['full_name', 'email', 'phone', 'username'].forEach(function (k) { saved[k] = $('f_' + k).value; });
    var editMsg = $('editMsg');

    document.querySelectorAll('[data-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.open === 'editModal') {
                Object.keys(saved).forEach(function (k) { $('f_' + k).value = saved[k]; });
                editMsg.textContent = '';
                showEditGroup(btn.dataset.group);
            }
            if (btn.dataset.open === 'passwordModal') {
                $('passwordForm').reset();
                $('pwMsg').textContent = '';
            }
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

    function applyProfile(profile) {
        Object.keys(profile).forEach(function (key) {
            var text = profile[key];
            if (key === 'phone' && text === '') text = 'Not set';
            document.querySelectorAll('[data-bind="' + key + '"]').forEach(function (el) { el.textContent = text; });
        });
        saved = { full_name: profile.full_name, email: profile.email, phone: profile.phone, username: profile.username };
        var top = document.querySelector('.topbar .profile-link span');   // shared topbar shows the username
        if (top) top.textContent = profile.username;
    }

    $('editForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var data = new FormData(e.target);
        data.append('action', 'update');
        var btn = $('editSave');
        editMsg.textContent = '';
        btn.disabled = true;
        post(data).then(function (res) {
            btn.disabled = false;
            if (!res.success) { editMsg.textContent = window.cfFirstError(res); return; }
            applyProfile(res.profile);
            closeModal($('editModal'));
            toast('success', res.message || 'Profile updated.');
        });
    });

    // ----- Photo: preview right away, then upload -----
    var photoInput = $('photoInput');
    var avatarImg = $('avatarImg');
    $('cameraBtn').addEventListener('click', function () { photoInput.click(); });
    photoInput.addEventListener('change', function () {
        var file = photoInput.files[0];
        photoInput.value = '';
        if (!file) return;
        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) { fail('Use a JPG, PNG, or WEBP image.'); return; }
        if (file.size > 2 * 1024 * 1024) { fail('Image must be 2 MB or smaller.'); return; }

        var previous = avatarImg.src;
        var preview = URL.createObjectURL(file);
        avatarImg.src = preview;
        var wrap = avatarImg.parentElement;
        wrap.classList.add('busy');

        var data = new FormData();
        data.append('action', 'photo');
        data.append('profile_image', file);
        post(data).then(function (res) {
            wrap.classList.remove('busy');
            URL.revokeObjectURL(preview);
            if (!res.success) {
                avatarImg.src = previous;
                fail(window.cfFirstError(res));
                return;
            }
            avatarImg.src = res.image_url + '?v=' + Date.now();   // the file name is new, the query just avoids a stale cache
            toast('success', res.message || 'Profile photo updated.');
        });
    });

    // ----- Change password modal -----
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = $(btn.dataset.target);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });
    });
    $('passwordForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var cur = $('pw_current').value;
        var nw = $('pw_new').value;
        var conf = $('pw_confirm').value;
        var msg = $('pwMsg');
        msg.classList.remove('ok');
        if (!cur || !nw || !conf) { msg.textContent = 'Please fill in all fields.'; return; }
        if (nw !== conf) { msg.textContent = 'New password and confirmation do not match.'; return; }

        var btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        msg.textContent = '';
        post({ action: 'password', current_password: cur, new_password: nw, confirm_password: conf }).then(function (res) {
            btn.disabled = false;
            if (!res.success) { msg.textContent = window.cfFirstError(res); return; }
            e.target.reset();
            if (res.password_changed) $('pwChanged').textContent = res.password_changed;
            closeModal($('passwordModal'));
            toast('success', res.message || 'Password updated.');
        });
    });

    // ----- Login Security: Edit unlocks the switches and dropdown, Done saves them -----
    var body = $('securityBody');
    var editBtn = $('securityEdit');
    var secMsg = $('securityMsg');

    function switchOn(id) { return $(id).classList.contains('on') ? '1' : '0'; }

    editBtn.addEventListener('click', function () {
        if (body.classList.contains('locked')) {           // Edit
            body.classList.remove('locked');
            editBtn.querySelector('span').textContent = 'Done';
            secMsg.textContent = '';
            return;
        }
        editBtn.disabled = true;                            // Done -> save
        post({
            action: 'security',
            two_factor: switchOn('sw_two_factor'),
            failed_login: switchOn('sw_failed_login'),
            auto_logout: switchOn('sw_auto_logout'),
            logout_after: $('logoutAfter').value
        }).then(function (res) {
            editBtn.disabled = false;
            if (!res.success) { secMsg.textContent = window.cfFirstError(res); return; }
            secMsg.textContent = '';
            body.classList.add('locked');
            editBtn.querySelector('span').textContent = 'Edit';
            toast('success', res.message || 'Security settings saved.');
        });
    });
    document.querySelectorAll('.switch').forEach(function (sw) {
        sw.addEventListener('click', function () {
            var on = sw.classList.toggle('on');
            sw.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    });
});
