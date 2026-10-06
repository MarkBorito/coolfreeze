window.cfApi = function (url, options) {
  return fetch(url, Object.assign({ credentials: 'same-origin' }, options || {}))
    .then(function (r) {
      return r.json()
        .catch(function () { return { success: false, message: 'Unexpected server response.' }; })
        .then(function (data) {
          data.status = r.status;
          if (r.status === 401 && data.redirect) { window.location.replace(data.redirect); }
          return data;
        });
    })
    .catch(function () { return { success: false, message: 'Network error. Please try again.', status: 0 }; });
};

// data: a FormData or a plain object
window.cfPost = function (url, data) {
  return window.cfApi(url, { method: 'POST', body: data instanceof FormData ? data : new URLSearchParams(data) });
};

window.cfFirstError = function (res) {
  if (res.errors) { return res.errors[Object.keys(res.errors)[0]]; }
  return res.message || 'Something went wrong.';
};

window.cfEscape = function (text) {
  var d = document.createElement('div');
  d.textContent = text;
  return d.innerHTML;
};