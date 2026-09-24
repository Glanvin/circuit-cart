//CSRF helper for CodeIgniter 3 pages whose forms are submitted via fetch() or
//programmatically built, where the framework's form_open() token input is
//unavailable. Because csrf_regenerate is TRUE, the token must be read fresh
//from the cookie on every single request.
(function () {
    'use strict';

    function attr(name) {
        return document.body ? (document.body.getAttribute(name) || '') : '';
    }

    function token() {
        var cookieName = attr('data-csrf-cookie');
        var csrfName = attr('data-csrf-name');

        if (!cookieName || !csrfName || !document.cookie) {
            return '';
        }

        var prefix = cookieName + '=';
        var parts = document.cookie.split(';');

        for (var i = 0; i < parts.length; i++) {
            var part = parts[i].trim();
            if (part.indexOf(prefix) === 0) {
                var value = part.substring(prefix.length);
                try {
                    value = decodeURIComponent(value);
                } catch (e) {
                    //keep raw value if it is not percent-encoded
                }
                return value;
            }
        }

        return '';
    }

    //Appends "&csrf_token_name=<token>" to an existing urlencoded body, or
    //returns just the pair when body is empty. Returns body untouched when
    //CSRF is not active (no name/cookie on the page).
    function appendTo(body) {
        var value = token();
        if (value === '') {
            return body || '';
        }
        var pair = encodeURIComponent(attr('data-csrf-name')) + '=' + encodeURIComponent(value);
        return body ? body + '&' + pair : pair;
    }

    window.CSRF = {
        name: function () {
            return attr('data-csrf-name');
        },
        cookieName: function () {
            return attr('data-csrf-cookie');
        },
        token: token,
        appendTo: appendTo
    };
})();