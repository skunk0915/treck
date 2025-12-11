    <header>
        <div class="container">
            <a href="<?php echo $baseUrl; ?>/" class="logo">
                <img src="<?php echo $baseUrl; ?>/img/logo.png" alt="<?php echo htmlspecialchars($siteName); ?>">
            </a>
            <nav class="desktop-nav">
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>/">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/about">About</a></li>
                </ul>
            </nav>
            <button id="hamburger-btn" class="hamburger-btn" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- Menu Modal -->
        <div id="menu-modal" class="menu-modal">
            <div class="menu-modal-overlay"></div>
            <div class="menu-modal-content">
                <button id="menu-close-btn" class="menu-close-btn">&times;</button>
                <div class="menu-search-container">
                     <input type="text" id="menu-search-input" placeholder="記事を検索..." class="menu-search-input">
                </div>
                <div id="menu-search-results" class="menu-list" style="display:none; margin-bottom: 2rem;"></div>
                <div id="menu-related-section" class="menu-section" style="display:none;">
                    <h3>関連記事</h3>
                    <div id="menu-related-list" class="menu-list"></div>
                </div>
                <div id="menu-all-section" class="menu-section">
                     <h3>こんな記事もあります</h3>
                     <div id="menu-all-list" class="menu-list"></div>
                </div>
                <div id="menu-tags-section" class="menu-section menu-tags-section">
                    <button id="menu-tags-toggle" class="menu-tags-toggle">タグ一覧 <span>+</span></button>
                    <div id="menu-tags-list" class="menu-tags-list" style="display:none;"></div>
                </div>
            </div>
        </div>
    </header>
