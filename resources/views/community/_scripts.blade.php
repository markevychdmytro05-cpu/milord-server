<script>
    (function () {
        document.querySelectorAll('[data-cm-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                var dialog = document.getElementById(button.getAttribute('data-cm-open'));
                if (dialog && dialog.showModal) { dialog.showModal(); }
            });
        });
        document.querySelectorAll('[data-cm-close]').forEach(function (button) {
            button.addEventListener('click', function () { button.closest('dialog').close(); });
        });
        document.querySelectorAll('dialog.cm-dialog').forEach(function (dialog) {
            dialog.addEventListener('click', function (event) { if (event.target === dialog) { dialog.close(); } });
            if (dialog.hasAttribute('data-open-on-load') && dialog.showModal) { dialog.showModal(); }
        });
        document.querySelectorAll('[data-cm-share]').forEach(function (button) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-cm-share');
                if (navigator.share) { navigator.share({ url: url, title: document.title }).catch(function () {}); return; }
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function () {
                        var label = button.querySelector('span');
                        var original = label.textContent;
                        label.textContent = button.getAttribute('data-cm-copied');
                        setTimeout(function () { label.textContent = original; }, 2000);
                    });
                }
            });
        });
    })();
</script>
