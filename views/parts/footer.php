    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?> All rights reserved.</p>
        </div>
    </footer>
    <button id="backToTop" class="back-to-top" aria-label="ページトップへ戻る">▲</button>
    
    <script>
        var siteBaseUrl = "<?php echo $baseUrl; ?>";
        var relatedArticlesData = <?php echo json_encode($relatedByTag ?? []); ?>;
        var allArticlesData = <?php echo json_encode($allArticles ?? []); ?>;
    </script>
    <script src="<?php echo $baseUrl; ?>/js/common.js"></script>
    <script src="<?php echo $baseUrl; ?>/js/toc.js"></script>
    <script src="<?php echo $baseUrl; ?>/js/hamburger.js"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
