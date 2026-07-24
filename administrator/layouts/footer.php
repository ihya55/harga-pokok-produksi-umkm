<?php if (!empty($is_guest_page)): ?>
        </div>
    </div>
<?php else: ?>
</main>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<?php if (empty($is_guest_page)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const appShell = document.getElementById('appShell');
    const toggleBtn = document.getElementById('btnToggleSidebar');

    const savedState = localStorage.getItem('sidebar_state');
    if (savedState === 'collapsed') {
        appShell.classList.add('sidebar-collapsed');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            appShell.classList.toggle('sidebar-collapsed');

            if (appShell.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebar_state', 'collapsed');
            } else {
                localStorage.setItem('sidebar_state', 'expanded');
            }
        });
    }
});
</script>
<?php endif; ?>
</body>
</html>