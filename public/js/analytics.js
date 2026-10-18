(function () {
    "use strict";

    var VIEW_URL = "/api/track/view";
    var FLUSH_URL = "/api/track/flush";
    var FLUSH_INTERVAL_MS = 15000;
    var SECTION_THRESHOLD = 0.5;

    function sessionId() {
        try {
            var id = localStorage.getItem("lsa_sid");
            if (!id) {
                id = window.crypto && window.crypto.randomUUID
                    ? window.crypto.randomUUID()
                    : Date.now().toString(36) + Math.random().toString(36).slice(2);
                localStorage.setItem("lsa_sid", id);
            }
            return id;
        } catch (e) {
            return "anon";
        }
    }

    function post(url, data, useBeacon) {
        var body = JSON.stringify(data);
        if (useBeacon && navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([body], { type: "application/json" }));
            return;
        }
        return fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: body,
            keepalive: true,
        }).catch(function () {});
    }

    var viewId = null;
    var sections = {}; // name -> { visible, enteredAt, accSeconds }
    var pageActiveSince = document.hidden ? null : Date.now();
    var pageAccSeconds = 0;

    function pauseAll() {
        var t = Date.now();
        if (pageActiveSince !== null) {
            pageAccSeconds += (t - pageActiveSince) / 1000;
            pageActiveSince = null;
        }
        Object.keys(sections).forEach(function (name) {
            var s = sections[name];
            if (s.visible && s.enteredAt !== null) {
                s.accSeconds += (t - s.enteredAt) / 1000;
                s.enteredAt = null;
            }
        });
    }

    function resumeAll() {
        var t = Date.now();
        pageActiveSince = t;
        Object.keys(sections).forEach(function (name) {
            var s = sections[name];
            if (s.visible) {
                s.enteredAt = t;
            }
        });
    }

    function flush(useBeacon) {
        if (!viewId) {
            return;
        }

        pauseAll();

        var payloadSections = [];
        Object.keys(sections).forEach(function (name) {
            var s = sections[name];
            if (s.accSeconds >= 1) {
                payloadSections.push({ name: name, seconds: Math.round(s.accSeconds) });
                s.accSeconds = 0;
            }
        });

        var durationDelta = Math.round(pageAccSeconds);
        pageAccSeconds = 0;

        if (!document.hidden) {
            resumeAll();
        }

        if (durationDelta <= 0 && payloadSections.length === 0) {
            return;
        }

        post(FLUSH_URL, { id: viewId, duration_delta: durationDelta, sections: payloadSections }, useBeacon);
    }

    function watchSections() {
        var els = document.querySelectorAll("[data-track-section]");
        if (!els.length || !("IntersectionObserver" in window)) {
            return;
        }

        els.forEach(function (el) {
            sections[el.getAttribute("data-track-section")] = {
                visible: false,
                enteredAt: null,
                accSeconds: 0,
            };
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var name = entry.target.getAttribute("data-track-section");
                var s = sections[name];
                if (!s) {
                    return;
                }
                if (entry.isIntersecting && !s.visible) {
                    s.visible = true;
                    if (!document.hidden) {
                        s.enteredAt = Date.now();
                    }
                } else if (!entry.isIntersecting && s.visible) {
                    s.visible = false;
                    if (s.enteredAt !== null) {
                        s.accSeconds += (Date.now() - s.enteredAt) / 1000;
                        s.enteredAt = null;
                    }
                }
            });
        }, { threshold: SECTION_THRESHOLD });

        els.forEach(function (el) {
            observer.observe(el);
        });
    }

    post(VIEW_URL, {
        session_id: sessionId(),
        path: location.pathname,
        referrer: document.referrer || null,
    }, false).then(function (response) {
        return response ? response.json() : null;
    }).then(function (data) {
        if (!data || !data.id) {
            return;
        }
        viewId = data.id;
        watchSections();
        setInterval(function () { flush(false); }, FLUSH_INTERVAL_MS);
    }).catch(function () {});

    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            flush(true);
        } else {
            resumeAll();
        }
    });

    window.addEventListener("pagehide", function () {
        flush(true);
    });
})();
