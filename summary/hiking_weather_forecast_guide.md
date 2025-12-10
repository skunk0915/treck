# 登山の天気予報の見方ガイド【天候判断と中止基準】- 調査サマリ

## 1. 山岳天気予報サービスの比較

### てんきとくらす（てんくら）
- **費用**: 基本無料（関連サービス「お天気ナビゲータ 登山ナビ」は月額110円〜288円）
- **特徴**: 独自の「登山指数」（A, B, C）で登山における天候の快適さを視覚的に表示。2,300以上の山に対応
- **登山指数の意味**:
  - A: 登山に適している
  - B: 風または雨が強くやや不適
  - C: 風または雨が強く登山に適していない
- **注意点**: 登山指数は特定時間帯（例: 午前9時）の山頂の天気基準。一日全体や登山道全体の状況を示すものではない。登山口が雨でも山頂が晴れていればA判定になることも。雷の可能性は考慮されていない

**参考情報**: 「登山指数は特定の時間帯（例: 午前9時）の山頂の天気を基にしており、一日全体の天候や登山道全体の状況を示すものではないため、誤解を招く可能性があります」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQHiR7RrAnzS7TZevGrP9FiHEUHqiyu9PODIO9wpX3D-1vjjGowZgPsKr4qdSosYHh8KiywqRQ29-jaFBTgORA2PNuThvxVe_tPnv0tMXssQFGFcL8URWFz9y3rrP2uzl8q9Adq6UPs=`</details>

### ヤマテン（山の天気予報）
- **費用**: 月額550円（2024年6月25日より改定）。1週間の無料試用期間あり
- **特徴**: 山岳専門の気象予報士集団が運営。山岳ツアー会社や山小屋などプロからも信頼される高精度予報
- **詳細な解説**: 天気マークだけでなく、気象予報士による詳細コメント、稜線上の風の強さ、ガスの発生しやすさなど登山特化の気象リスク情報
- **スペシャル予報**: 主要な山岳（全国330山）に対して3日先までの詳細予報

**参考情報**: 「ヤマテンは、山岳専門の気象予報士集団が運営する有料の山岳天気予報サービスです」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQFF4AMDewsYXFKkDHsfEYvd97Jrfmsm9i1Y37npQ3WnEZt_iVhhjdQ3339zq1wR2fcVRZ9lDUcjFXJp9mYcutwhKmIZjwkfjZuv1lM91mOGBBsYl9Icht2jFzzaILGiWPpXCw==`</details>

### Windy
- **費用**: 無料版でも登山に必要な機能が揃う
- **特徴**: 風の動きや雲の広がり、雨雲の範囲・強さがアニメーションで視覚的に確認可能。複数の予報モデル比較が可能

**参考情報**: 「Windyは、無料版でも登山に必要な機能が揃っており、風の動きや雲の広がり、雨雲の範囲・強さがアニメーションで視覚的に確認できます」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQFVHUUQ_YxU0qtPg2CH4Uq_-B7LYvf4-4aE-OIWsPukqiV6tM1IzN-3Jc6B6ZsNodvn7pKjHhkjsJn6j4tJTUev9QOKLijnWUMS2gWCPDGKWqA6AEnvIw7c41kJjtMyELIaYPYjjFrbTU1khA==`</details>

### SCW（スーパーコンピューター予測）
- **特徴**: 非常に詳細な雨雲の動きをリアルタイムで確認可能。短期間の予報に特化

### tenki.jp 登山天気
- **特徴**: 気象庁のデータを基にした麓の天気確認に便利。1時間ごとの標高別天気予報や雷危険度、風の予測などリスク管理機能

### mountain-forecast.com
- **特徴**: 世界中の主要な山の5日先までの予報確認可能。凍結高度も表示され冬山登山にも有用

---

## 2. 山の天気の基礎知識

### 標高と気温の関係
- **気温減率**: 標高が100m上がるごとに気温が約0.6℃〜0.65℃低下
- **乾燥/湿潤の違い**: 乾燥時は100mで約1℃、湿潤時は約0.5℃下がる
- **計算例**: 平地が25℃の場合、3000mの山では約7℃になる

**参考情報**: 「山では標高が100m上がるごとに、気温が約0.6℃～0.65℃低下するのが一般的です」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQH2TECn2rh2dXXcF95THM5Q2mz0Zbx5Nhu6WIF0HUkGKJpv0lSxqOxvbPRv4hbZbTM-nEwpaVpiNc2ZDFPxNnFvb2zGiax8ZlRRWdAProDzGkF22HC8VRYKimFdDAaJ7dqraA==`</details>

### 風速と体感温度
- **基本法則**: 風速が1m/s強くなるごとに体感温度は約1℃下がる
- **山の風速**: 山では平地の2〜3倍の風速になることも珍しくない
- **警戒基準**: 風速10m/s以上は警戒が必要（傘が差せない、風に向かって歩きにくいレベル）
- **計算例**: 平地25℃、標高3000m、風速10m/sの場合 → 体感温度-3℃

**参考情報**: 「風速が1m/s強くなるごとに体感温度は約1℃下がるとされています」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQEDtGJWvm1Gx6aBZjn57B5oknmT8_Gq3frEKoirHypQD4tHno-HN8FdJS27ptVgNJr2GvbVo7hd87cP3W-p4gaIFC4tYwyE-lOZk5ElWV0hvzLrvZFvhWFDNq6nPeo=`</details>

### 天気予報で確認すべきポイント
1. **天候（晴れ/雨/曇り）**: 雨具の有無やコース変更の判断に直結
2. **最高・最低気温**: 朝晩の冷え込みに備える（テント泊は特に重要）
3. **風速・風向**: 体感温度に大きく影響
4. **降水量**: 総合的に判断
5. **雲量**: 重要な予報要素
6. **日の出・日の入り**: 行動計画やヘッドライトの必要性判断

---

## 3. 登山の中止基準

### 具体的な数値基準
- **降水確率**: 
  - 森林限界を超える山行: 麓で60％以上で中止という意見
  - 30%以上で登山を見送るという基準を持つ人も
- **気象警報**: 前日17時時点で現地の気象警報（大雨、洪水、暴風など）が発令なら中止
- **風速**: 15m/s以上は山での行動を避けるべき（風に向かって歩けない、石が飛んでくる危険）
- **降水量**: 24時間以内の降水量が50mmを超えた場合はコース変更基準
- **連続雨量**: 土砂降り（15mm/時間）が6時間続くと連続雨量90mm → 土砂崩れ危険性増大
- **雷**: 雷雨が予想される場合は中止

**参考情報**: 「風速15m/sを超える場合は山での行動を避けるべきとされます。風速15m/sは風に向かって歩けないレベルで、石が飛んでくる危険もあります」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQHr0vOX3jIAHUV8BQmbxty8tL4GFmgAkfGD_QdmZjf7Ue5JM5oq6FXqduaXp3w7A9YlHTfne-ywBX6KFnxqCsMG5tX4Cqgk-tquUEnfgFMDvqpVHggF3worppZi580cFGKnGUzw8A5G8ntlNbnPxQ0oO3P4NuM0snfyG6Pnc9j-X1TaQTq2fPU1mMux8vclELJbQ-g=`</details>

### 悪天候がもたらすリスク
1. **低体温症**: 雨や風で体が濡れると体温が奪われる
2. **滑落・転倒**: 濡れた岩場や木の根、木道、土の斜面が滑りやすくなる
3. **視界不良・道迷い**: 濃霧や激しい雨で視界悪化
4. **沢の増水・土砂災害**: 雨量が多い日は鉄砲水の危険
5. **強風**: バランスを崩す、体感温度低下

---

## 4. 観天望気（現場での天候判断）

### 危険を示す雲の種類
1. **積乱雲（せきらんうん・入道雲）・かなとこ雲**: 即座の撤退が必要。雷、大雨、突風、雹などの激しい気象現象を引き起こす最も危険な雲
2. **レンズ雲（笠雲）**: 強風と悪天候の前兆
3. **巻積雲（うろこ雲、いわし雲）**: 天候下り坂の兆候
4. **乱層雲（らんそううん）**: 本格的な雨の到来
5. **ちぎれ雲（断片雲）の高速移動**: 暴風雨の直前の可能性

**参考情報**: 「積乱雲（せきらんうん・入道雲）・かなとこ雲は、即座の撤退が必要です」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQFoQSngWP4LSOhnVjNFtvvzMmStw3Ouzuo2dra0lDLnrPJ4jqCE2Qurt4EOHS7Mu0Nza9cdDN17csMWmOCRQArbX8ka-oIMRJ-QrrGBG3VjhZCP2FkVz_WWF8S-pvbAhj4d3vMO7FFNA4jqTqbA5UGHN2tTxUR_ETwszKV11QE=`</details>

### 登山中の「やばい予兆」
- **急に風が止む**: 次に突風が来る前触れの可能性
- **雲の色が濃くなり、形がモコモコ膨らむ**: 雷の兆し
- **気温が一気に下がる**: 寒冷前線の接近

### 気圧配置の確認ポイント
- **低気圧**: 雨が降りやすくなる
- **高気圧**: 雲ができにくい
- **前線**: 温度差のある気団の境目で雨が降りやすい。寒冷前線付近では雷雨が発生しやすい
- **等圧線**: 間隔が狭いほど強い風

### 注意すべき気圧配置パターン
1. **西高東低（冬型気圧配置）**: 日本海側で雪、太平洋側で乾燥した晴れ
2. **南岸低気圧**: 関東などで雪や雨
3. **夏の不安定（積乱雲の発達）**: 午後の雷雨に注意

---

## 5. 雷対策

### 事前対策
- 天気予報で雷注意報の有無を確認
- 「上空に寒気が流れ込み」「大気が不安定」という言葉に警戒
- 雷は特に夏場の午後（14時以降）に多く発生 → 午前中に行動を終える計画

### 稜線上で雷に遭遇した場合の行動
1. **稜線からの退避**: 安全な斜面へ下り、できるだけ低い位置へ移動
2. **姿勢を低くする**: しゃがみこんで両足を揃え、地面との接触面積を小さく
3. **避難場所**: 低いくぼ地、ハイマツなどの低木の中
4. **危険な場所**: 大きな岩、尖った岩、木の近く（4m以上離れる）、開けた場所、水場

### 金属製品について
- 金属製のザックフレームや登山道具が落雷を誘発するという科学的根拠は薄い
- 無理に外す必要はないが、尖ったトレッキングポールなどは体から離して置く

**参考情報**: 「金属製のザックフレームや登山道具、身につけている金属製品が落雷を誘発するという科学的根拠は薄く、無理に外す必要はありません」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQHI5qcVfwPKZX4CPWTAHmrAYhDJWlCMcX2FlyBp1Dt2JhG0mEBxQBfom1cm0ZZIHE7LtdRJtjxptfPl6MjNwXn5LTSFRiAt1yUcozPCYvVcoTis327YexhRTA==`</details>

### グループでの対処
- 一箇所に固まらず、5m以上の間隔を空けて分散
- 雷鳴が聞こえなくなってから少なくとも30分間は安全な場所で待機

---

## 6. 気象遭難の事例

### トムラウシ山遭難事故（2009年7月）
- 梅雨明け間近の7月、北海道大雪山系トムラウシ山で発生
- 暴風雨に遭遇し、ガイドを含む8名が低体温症で死亡
- **教訓**: 夏山でも標高が高い場所では低体温症のリスクがある。気温がそれほど低くなくても、雨による濡れと風による冷却が重なると低体温症が急速に進行

**参考情報**: 「トムラウシ山遭難事故は、気温がそれほど低くなくても、雨による濡れと風による冷却が重なることで低体温症が急速に進行する危険性を示しています」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQEozXYGjxQmGC77VP1FmS4jE98GwV7AXhvQmFQ3uSThd7ca_zX8T1QvaDXc6-6JPObwoy1rbeD8CJ8HS7oPUgpiT69amboTa1dGJpbtKzOzUDt0KahrsRM8qDu3`</details>

### 朝日岳低体温症事件（2023年10月）
- 新潟県と栃木県の境にある朝日岳で発生
- 急激な天候悪化と強風が原因で4人の登山者が低体温症で死亡
- 救助隊到着時も暴風と雨、体感温度は-1℃という過酷な状況

**参考情報**: 「朝日岳低体温症事件では、急激な天候悪化と強風が原因で4人の登山者が低体温症で亡くなりました」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQH_WghhhwcF9t82hOeJx0k5WkZW_hkx4ZPFe4DG7LKzk0B4f_WjQEaC_iaYDhHbqVFq6BFRCjAMLU6SFiskdkjTmtzt9t8h6WZsIr6FPXNM8zJkus4rQ3Cq6Nbc7QbajVO2USMfG0IVnuw4Uw==`</details>

### 低体温症を引き起こす気象条件
- 気温10℃以下
- 雨や雪で体が濡れる
- 風速10m/s以上の強風
- 水は空気の約25倍の熱伝導率 → 濡れた服は体の熱を奪う

---

## 7. 「勇気ある撤退」の重要性

- 「せっかく来たから」という気持ちより、危険を感じたら引き返す判断が命を守る
- 天候判断に100%の確実性はないため、常に撤退の選択肢を持つ
- 山はいつでもそこにある

**参考情報**: 「『せっかく来たから』という気持ちよりも、危険を感じたら引き返す『勇気ある撤退』が命を守ります」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQFzvbdAWYlwWhReRWkO1Usvhx3fOWkBygad9aMvMYKG5JWijpQjOVe1BuUDXJqTc0VCxdUjfsQr-etsVwK-zgTsSX1ww_w0aoulFQXpPweNQuEmmXo1nn9hsIh4MGRRZKCTX2aTZR5Ju0MbpyXt2lTW0VG0`</details>

---

## 8. 複数情報源の活用

- 一つの天気予報サイトだけでなく、複数のサイトやアプリを併用
- 現地での観天望気、山小屋スタッフからの情報なども総合的に判断

**参考情報**: 「多くの登山者は、一つのサービスに頼り切らず、複数の天気予報サービスを併用して総合的に判断することを推奨しています」
<details><summary>出典</summary>`https://vertexaisearch.cloud.google.com/grounding-api-redirect/AUZIYQH3XtIoRaljhHGSWjm4b2Gbis4WhN30r2XjGZOlwbIRtBZ1dk_F7PnYouBSjncW4uEwXx_AHXxDza_r7UfAYknTQoR-1sQLtC5HIdEmkvsqhry3Cf1UY2wM-OsIYuu9LpR8nGcyqI8TdQ-04g==`</details>

---

## 記事作成時の注意点

1. 天気予報サービスの使い分けを具体的に説明
2. 数値基準（風速15m/s、降水確率60%など）を明確に提示
3. 観天望気の具体的な雲の見分け方を解説
4. トムラウシ山事故など実際の事例を引用して危険性を伝える
5. 「勇気ある撤退」の重要性を強調
6. JKらしい率直なツッコミ（「そんなに気にする必要ある？」「晴れてるから大丈夫でしょ」など）を入れて、読者の本音を代弁させる
