<?php $_footerIsLoggedIn = isLoggedIn(); ?>

<?php if ($_footerIsLoggedIn): ?>
    </div><!-- /.app-main -->
</div><!-- /.app-layout -->

<?php else: ?>
</main>

<footer class="site-footer">
    <span>CodeVault &middot; Open source under MIT</span>
    <div class="site-footer-links">
        <a href="https://github.com/IManss-ai/codevault">GitHub</a>
        <a href="<?= BASE_URL ?>/docs">API Docs</a>
    </div>
</footer>

<?php endif; ?>

<!-- Prism.js Core + Autoloader -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>

<!-- CodeVault Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
