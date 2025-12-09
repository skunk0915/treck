<div id="pwaPromptModal" class="modal-overlay">
    <div class="modal-content">
        <button id="closePwaModal" class="close-btn">&times;</button>
        <div class="pwa-dialogue">
            
            <div class="chat-row teacher">
                <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生"></div>
                <div class="bubble">
                    <div class="message">おや？結構、熱心に読んでくれているじゃないか。</div>
                </div>
            </div>

            <div class="chat-row student">
                <div class="icon student"><img src="<?php echo $baseUrl; ?>/img/jk.png" alt="JK"></div>
                <div class="bubble">
                    <div class="message">はい、なんか意外と面白いかも。</div>
                </div>
            </div>

            <div class="chat-row teacher">
                <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生"></div>
                <div class="bubble">
                    <div class="message">
                        それなら、<span class="highlight">「ホーム画面に追加」</span>しておくといい。<br>
                        アプリみたいにすぐに開けるようになって便利だぞ。
                    </div>
                </div>
            </div>

            <div class="chat-row student">
                <div class="icon student"><img src="<?php echo $baseUrl; ?>/img/jk.png" alt="JK"></div>
                <div class="bubble">
                    <div class="message">あ、それいいかも。毎回ブラウザからアクセスするの面倒だったし。</div>
                </div>
            </div>
            
            <div class="pwa-instruction">
                <p>ブラウザのメニューから<br><strong>「ホーム画面に追加」</strong><br>を選んでください。</p>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('closePwaModal').addEventListener('click', function() {
        document.getElementById('pwaPromptModal').style.display = 'none';
    });
</script>
