<?php
$pageTitle = 'このサイトについて - ' . $siteName;
$pageDescription = $siteName . 'の使い方とキャラクター紹介';
$pageCanonical = '/about';
include 'parts/head.php';
include 'parts/header.php';
?>

    <main class="container">
        <article class="post">
            <header class="post-header">
                <h1>このサイトについて</h1>

                <img src="img/about-mv.jpg" alt="" loading="lazy">
            </header>
            <div class="post-content">
                
                <div class="chat-row student">
                    <div class="icon student"><img src="<?php echo $baseUrl; ?>/img/jk.png" alt="JK" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p>ねえ先生、ここってどういうサイトなんですか？<br>
                            なんか「先生、それ、重くないですか？」とか、私のセリフがタイトルになってますけど。</p>
                        </div>
                    </div>
                </div>

                <div class="chat-row teacher">
                    <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p>ここは、<strong>「登山道具選びに迷える子羊たち」を救うための場所</strong>だ。<br>
                            世の中には無数のアウトドアギアがあるが、初心者が自分に合ったものを選ぶのは至難の業だろう？<br>
                            カタログスペックだけじゃ分からない「現場でのリアルな使用感」を、私が徹底的に解説するサイトだ。</p>
                        </div>
                    </div>
                </div>

                <div class="chat-row student">
                    <div class="icon student"><img src="<?php echo $baseUrl; ?>/img/jk.png" alt="JK" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p>へぇ〜。でも先生の話って、専門用語ばっかりで難しいし、長いんですよね…。<br>
                            私、難しいこと言われても寝ちゃいますよ？</p>
                        </div>
                    </div>
                </div>

                <div class="chat-row teacher">
                    <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            いやいや、だからこそ、君がいるんじゃないか。<br>
                            君は<strong>「初心者代表」</strong>として、分からないことは素直に質問し、重い・ダサいと思ったら正直にツッコミを入れる。<br>
                            それが読者の皆さんの「知りたいこと」に繋がるんだ。</p>
                        </div>
                    </div>
                </div>

                <h2>登場人物紹介</h2>

                <div class="chat-row student">
                    <div class="icon student"><img src="<?php echo $baseUrl; ?>/img/jk.png" alt="JK" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p><strong>JK</strong><br>
                            先生に連れられて山を始めたばかりの女子高生です！<br>
                            正直、機能とかよく分かんないし、可愛くて楽なのが一番だと思ってます。<br>
                            でも、最近ちょっとだけ山の楽しさが分かってきた…かも？</p>
                        </div>
                    </div>
                </div>

                <div class="chat-row teacher">
                    <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p><strong>先生</strong><br>
                            登山歴20年のガイドだ。<br>
                            道具は「命を預ける相棒」だと思っているから、選び方にはうるさいぞ。<br>
                            流行り廃りには疎いが、機能美と耐久性には目がない。<br>
                            君たちが安全に山を楽しめるよう、厳しくも愛のある指導をしていくつもりだ。</p>
                        </div>
                    </div>
                </div>

                <h2>このサイトの使い方</h2>

                <div class="chat-row teacher">
                    <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p>記事はすべて、私とJK君の<strong>「対話形式」</strong>で進んでいく。<br>
                            難しい理屈は抜きにして、二人の会話を盗み聞きする感覚で読んでくれればいい。</p>
                        </div>
                    </div>
                </div>

                <div class="chat-row student">
                    <div class="icon student"><img src="<?php echo $baseUrl; ?>/img/jk.png" alt="JK" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p>ふむふむ。それなら私でも読めそう！<br>
                            でも、忙しくて全部読めない人はどうすればいいんですか？</p>
                        </div>
                    </div>
                </div>

                <div class="chat-row teacher">
                    <div class="icon teacher"><img src="<?php echo $baseUrl; ?>/img/teacher.png" alt="先生" loading="lazy"></div>
                    <div class="bubble">
                        <div class="message">
                            <p>せっかちな現代人のために、記事の最後には<strong>「まとめ・比較表」</strong>を用意している。<br>
                            そこだけ読めば要点が分かるようになっているから、安心してくれ。<br>
                            さあ、準備はいいか？ 終わりのない「道具沼」への入り口へようこそ！</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </main>

<?php include 'parts/footer.php'; ?>
