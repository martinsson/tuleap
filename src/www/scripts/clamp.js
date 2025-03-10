/*!
* Clamp.js 0.5.1
*
* Copyright 2011-2013, Joseph Schmitt http://joe.sh
* Released under the WTFPL license
* http://sam.zoy.org/wtfpl/
*/
(function() {
    window.$clamp = function(c, d) {
        function s(a, b) {
            n.getComputedStyle ||
                (n.getComputedStyle = function(a) {
                    this.el = a;
                    this.getPropertyValue = function(b) {
                        //eslint-disable-next-line no-useless-escape
                        var c = /(\-([a-z]){1})/g;
                        "float" == b && (b = "styleFloat");
                        c.test(b) &&
                            (b = b.replace(c, function(a, b, c) {
                                return c.toUpperCase();
                            }));
                        return a.currentStyle && a.currentStyle[b] ? a.currentStyle[b] : null;
                    };
                    return this;
                });
            return n.getComputedStyle(a, null).getPropertyValue(b);
        }
        function t(a) {
            a = a || c.clientHeight;
            var b = u(c);
            return Math.max(Math.floor(a / b), 0);
        }
        function x(a) {
            return u(c) * a;
        }
        function u(a) {
            var b = s(a, "line-height");
            "normal" == b && (b = 1.2 * parseInt(s(a, "font-size"), 10));
            return parseInt(b, 10);
        }
        function l(a) {
            if (a.lastChild.children && 0 < a.lastChild.children.length) {
                return l(Array.prototype.slice.call(a.children).pop());
            }
            if (
                a.lastChild &&
                a.lastChild.nodeValue &&
                "" != a.lastChild.nodeValue &&
                a.lastChild.nodeValue != b.truncationChar
            ) {
                return a.lastChild;
            }
            a.lastChild.parentNode.removeChild(a.lastChild);
            return l(c);
        }
        function p(a, d) {
            if (d) {
                var e = a.nodeValue.replace(b.truncationChar, "");
                f || ((h = 0 < k.length ? k.shift() : ""), (f = e.split(h)));
                1 < f.length ? ((q = f.pop()), r(a, f.join(h))) : (f = null);
                m &&
                    ((a.nodeValue = a.nodeValue.replace(b.truncationChar, "")),
                    (c.innerHTML = a.nodeValue + " " + m.innerHTML + b.truncationChar));
                if (f) {
                    if (c.clientHeight <= d) {
                        if (0 <= k.length && "" != h) {
                            r(a, f.join(h) + h + q), (f = null); //eslint-disable-line no-sequences
                        } else {
                            return c.innerHTML;
                        }
                    }
                } else {
                    "" == h &&
                        (r(a, ""),
                        (a = l(c)),
                        (k = b.splitOnChars.slice(0)),
                        (h = k[0]),
                        (q = f = null));
                }
                if (b.animate) {
                    setTimeout(function() {
                        p(a, d);
                    }, !0 === b.animate ? 10 : b.animate);
                } else {
                    return p(a, d);
                }
            }
        }
        function r(a, c) {
            a.nodeValue = c + b.truncationChar;
        }
        d = d || {};
        var n = window,
            b = {
                clamp: d.clamp || 2,
                useNativeClamp: "undefined" !== typeof d.useNativeClamp ? d.useNativeClamp : !0,
                splitOnChars: d.splitOnChars || [".", "-", "\u2013", "\u2014", " "],
                animate: d.animate || !1,
                truncationChar: d.truncationChar || "\u2026",
                truncationHTML: d.truncationHTML
            },
            e = c.style,
            y = c.innerHTML,
            z = "undefined" !== typeof c.style.webkitLineClamp,
            g = b.clamp,
            v = g.indexOf && (-1 < g.indexOf("px") || -1 < g.indexOf("em")),
            m;
        b.truncationHTML &&
            ((m = document.createElement("span")), (m.innerHTML = b.truncationHTML));
        var k = b.splitOnChars.slice(0),
            h = k[0],
            f,
            q;
        "auto" == g ? (g = t()) : v && (g = t(parseInt(g, 10)));
        var w;
        z && b.useNativeClamp
            ? ((e.overflow = "hidden"),
              (e.textOverflow = "ellipsis"),
              (e.webkitBoxOrient = "vertical"),
              (e.display = "-webkit-box"),
              (e.webkitLineClamp = g),
              v && (e.height = b.clamp + "px"))
            : ((e = x(g)), e <= c.clientHeight && (w = p(l(c), e)));
        return { original: y, clamped: w };
    };
})();
