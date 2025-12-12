document.addEventListener('DOMContentLoaded', function () {
	// Access Logging & PWA Prompt
	fetch('/api/log_access.php')
		.then(response => response.json())
		.then(data => {
			if (data.showPwaPrompt) {
				showPwaPrompt();
			}
		})
		.catch(err => console.error('Error logging access:', err));

	function showPwaPrompt() {
		const baseUrl = ''; // Root relative
		const html = `
<div id="pwaPromptModal" class="modal-overlay">
    <div class="modal-content">
        <button id="closePwaModal" class="close-btn">&times;</button>
        <div class="pwa-dialogue">
            
            <div class="chat-row teacher">
                <div class="icon teacher"><img src="${baseUrl}/img/teacher.png" alt="先生"></div>
                <div class="bubble">
                    <div class="message">おや？結構、熱心に読んでくれているじゃないか。</div>
                </div>
            </div>

            <div class="chat-row student">
                <div class="icon student"><img src="${baseUrl}/img/jk.png" alt="JK"></div>
                <div class="bubble">
                    <div class="message">はい、なんか意外と面白いかも。</div>
                </div>
            </div>

            <div class="chat-row teacher">
                <div class="icon teacher"><img src="${baseUrl}/img/teacher.png" alt="先生"></div>
                <div class="bubble">
                    <div class="message">
                        それなら、<span class="highlight">「ホーム画面に追加」</span>しておくといい。<br>
                        アプリみたいにすぐに開けるようになって便利だぞ。
                    </div>
                </div>
            </div>

            <div class="chat-row student">
                <div class="icon student"><img src="${baseUrl}/img/jk.png" alt="JK"></div>
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
        `;
		document.body.insertAdjacentHTML('beforeend', html);

		// Add Event Listener
		document.getElementById('closePwaModal').addEventListener('click', function () {
			document.getElementById('pwaPromptModal').style.display = 'none';
		});

		// Ensure it is visible
		document.getElementById('pwaPromptModal').style.display = 'block';
	}
});
