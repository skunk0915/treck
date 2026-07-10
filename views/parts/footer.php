    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?> All rights reserved.</p>
        </div>
        <script src="http://localhost:4173/embed.js" data-site-key="sc_pk_IXSmqrVkj5OYb9Tx7EcsnvUQ" async></script>
    </footer>
    <button id="backToTop" class="back-to-top" aria-label="ページトップへ戻る">▲</button>
    
    <script>
        var siteBaseUrl = "<?php echo $baseUrl; ?>";
        var relatedArticlesData = <?php echo json_encode($relatedByTag ?? []); ?>;
    </script>
    <script src="<?php echo $baseUrl; ?>/js/common.js" defer></script>
    <script src="<?php echo $baseUrl; ?>/js/toc.js" defer></script>
    <script src="<?php echo $baseUrl; ?>/js/hamburger.js" defer></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
