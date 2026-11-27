</div><!-- /.main-content -->

    <footer class="site-footer">
        <div class="footer-container">
            <div>
                <strong>EventHub</strong> &copy; <?= date('Y') ?> — Plateforme de gestion d'évènements.
            </div>
            <div class="footer-links">
                <a href="index.php">Accueil</a>
                <a href="events.php">Catalogue</a>
                <?php if (current_user()): ?>
                    <a href="profile.php">Mon compte</a>
                <?php else: ?>
                    <a href="login.php">Connexion</a>
                    <a href="register.php">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('navToggle');
            const collapse = document.getElementById('navCollapse');
            if (toggle && collapse) {
                toggle.addEventListener('click', function () {
                    const isOpen = collapse.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            }
        });
    </script>
</body>

</html>