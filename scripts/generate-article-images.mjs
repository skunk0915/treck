#!/usr/bin/env node
/**
 * generate-article-images.mjs — 記事トップ画像（サムネイル）一括自律生成ワーカー
 *
 * 【要件】
 * 1. 外部APIは一切使用禁止。サブスクの Antigravity CLI（~/.local/bin/agy）を非対話（-p）で1枚ずつ実行。
 * 2. 画像生成専用レートリミット（および通常制限）を正確に検知し、解除までの分数（LIMIT <分>）を把握して安全マージン付きでスリープ・自動再開。
 * 3. 道具の実在性・忠実な再現（最重要）:
 *    - 道具紹介記事: その特定モデル（ブランド・型番・色・ロゴ・外観特徴）を忠実にイラスト化。
 *    - ノウハウ記事: 架空の適当なギアは厳禁。実在する定番ギア（Caravan C1_02S, Karrimor Ridge 30, Montbell等）を具体指定。
 * 4. 金太郎飴・テンプレート構図の完全排除:
 *    - 直立してただ立っているだけの単調な構図は厳禁。
 *    - テーマごとに、超ローアングルのパース、岩場の三点支持、風雨に耐える耐風姿勢、パッキングの奮闘、
 *      テント設営の躍動感など、ドラマチックで動き・躍動感・感情に溢れた構図を動的生成。
 * 5. デフォルメJK（頭身1:2〜1:2.5、茶髪ショートボブ、山ガール）の参照画像を作業フォルダに配置し、キャラクターとアニメ調の画風を統一。
 * 6. 全記事完了まで自動継続。
 */

import { spawn, execFileSync } from 'node:child_process';
import {
  copyFileSync,
  existsSync,
  mkdirSync,
  readFileSync,
  readdirSync,
  rmSync,
  statSync,
  writeFileSync,
  unlinkSync
} from 'node:fs';
import { dirname, resolve, basename, extname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const ARTICLE_DIR = resolve(ROOT, 'article');
const IMG_DIR = resolve(ROOT, 'img');
const WORK_DIR = resolve(ROOT, 'tmp', 'image-work');
const AGY = resolve(process.env.HOME, '.local', 'bin', 'agy');
const BRAIN_DIR = resolve(process.env.HOME, '.gemini', 'antigravity-cli', 'brain');
const LOG_FILE = resolve(ROOT, 'tmp', 'article-image-worker.log');
const STATE_FILE = resolve(ROOT, 'tmp', 'article-image-state.json');
const LIMIT_FILE = resolve(ROOT, 'tmp', 'article-image-limit.json');

const CALL_TIMEOUT_MS = 6 * 60 * 1000; // 6分
const DEFAULT_LIMIT_WAIT_MIN = 15;

const now = () => new Date().toLocaleString('sv-SE', { timeZone: 'Asia/Tokyo' });
const log = (msg) => {
  const line = `[${now()}] ${msg}`;
  console.log(line);
  mkdirSync(dirname(LOG_FILE), { recursive: true });
  try {
    if (existsSync(LOG_FILE) && statSync(LOG_FILE).size > 10 * 1024 * 1024) {
      unlinkSync(LOG_FILE);
    }
    writeFileSync(LOG_FILE, line + '\n', { flag: 'a', encoding: 'utf8' });
  } catch {}
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function readState() {
  if (!existsSync(STATE_FILE)) return { completed: [], failed: {} };
  try {
    return JSON.parse(readFileSync(STATE_FILE, 'utf8'));
  } catch {
    return { completed: [], failed: {} };
  }
}

function writeState(data) {
  mkdirSync(dirname(STATE_FILE), { recursive: true });
  const tmp = `${STATE_FILE}.${process.pid}.tmp`;
  writeFileSync(tmp, JSON.stringify(data, null, 2), 'utf8');
  execFileSync('mv', [tmp, STATE_FILE]);
}

function imageFormat(path) {
  try {
    const out = execFileSync('sips', ['-g', 'format', '-g', 'pixelWidth', path], {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    });
    const format = out.match(/format:\s*(\w+)/)?.[1];
    const width = Number(out.match(/pixelWidth:\s*(\d+)/)?.[1] || 0);
    return width > 0 ? format : null;
  } catch {
    return null;
  }
}

function getReferenceImage() {
  const candidates = [
    resolve(IMG_DIR, 'caravan_shoes_guide_01.jpg'),
    resolve(IMG_DIR, 'mammut_backpack_article_01.jpg'),
    resolve(IMG_DIR, 'bear_bell_guide_01.jpg'),
  ];
  for (const c of candidates) {
    if (existsSync(c)) return c;
  }
  return null;
}

/** 未作成記事の一覧を取得 */
export function getPendingArticles() {
  const files = readdirSync(ARTICLE_DIR)
    .filter((f) => f.endsWith('.md'))
    .sort();
  const existingImages = new Set(readdirSync(IMG_DIR));
  const pending = [];

  for (const f of files) {
    const slug = basename(f, '.md');
    const fullPath = resolve(ARTICLE_DIR, f);
    const content = readFileSync(fullPath, 'utf8');
    const lines = content.split('\n');

    const header = lines.slice(0, 15).join('\n');
    const m = header.match(/!\[(.*?)\]\(\/img\/([^)]+)\)/);
    const targetFile = m ? m[2] : `${slug}_01.jpg`;
    const alt = m ? m[1] : '';

    const hasImage =
      existingImages.has(targetFile) ||
      existingImages.has(`${slug}_01.jpg`) ||
      existingImages.has(`${slug}_01.png`) ||
      existingImages.has(`${slug}_01.webp`);

    if (!hasImage) {
      const title = (lines[0] || slug).replace(/^#+\s*/, '').trim();
      const promptMatch = content.match(/> \*\*Image Prompt:\*\*\s*(.+)/);
      const promptHint = promptMatch ? promptMatch[1] : '';

      pending.push({
        slug,
        mdPath: fullPath,
        targetFile,
        title,
        alt,
        promptHint,
      });
    }
  }

  return pending;
}

/** 記事テーマに合わせたダイナミックなアクション・構図・実在ギアの演出指示を生成 */
function buildDynamicSceneInstruction(item) {
  const { slug, title, alt, promptHint } = item;
  const text = `${slug} ${title} ${alt} ${promptHint}`.toLowerCase();

  // 1. 靴・歩行・トレッキングポール系
  if (/shoes|boots|foot|歩行|technique|walking|stock|pole|trekking_shoes/.test(text)) {
    return {
      gear: '実在する本格登山靴（例: スポルティバ TX5 GTX、キャラバン C1_02S、またはサロモン X ULTRA）と、ブラックダイヤモンドのトレッキングポールを忠実にイラスト化。',
      composition: '【ダイナミック構図】超ローアングルからの迫力あるワイドパース。濡れたガレ場や木の根が露出した急斜面で、靴のソール（深いラグとクライミングゾーン）がしっかりと岩を捉えて踏み込んでいる足元に力強い焦点を当てつつ、前傾姿勢でバランスを取りながら一歩を踏み出すJKの真剣で凛々しい表情を捉えた躍動感あふれる構図。'
    };
  }

  // 2. ザック・リュック・パッキング系
  if (/backpack|zack|bag|packing|ridge|saas_fee|aircontact|baltoro|ducan|trion/.test(text)) {
    return {
      gear: '記事に登場する実在の特定モデル（例: カリマー Ridge 30、ミレー Saas Fee、ドイター Aircontact、マムート Trion、グレゴリー Baltoro等）のブランドロゴ、特徴的なサイドポケット、2気室構造、チェストハーネスの形状を忠実にイラスト化。',
      composition: '【ダイナミック構図】テンプレートな立ち絵は厳禁。荷物がパンパンに入った大型ザックを膝に乗せて「よっこらしょ！」と気合いを入れて背負い上げようとする躍動の瞬間、または山小屋やテント場で床いっぱいにギアを広げ、ザックに乗りかかるように体重をかけて無理やりファスナーを閉めようと奮闘するユーモラスで動きのある構図。'
    };
  }

  // 3. レインウェア・ハードシェル・悪天候・防寒着系
  if (/rain|hardshell|weather|storm|wind|typhoon|snow|winter|寒|雨|fleece|down|insulated/.test(text)) {
    return {
      gear: '実在する防水透湿ジャケット（例: モンベル ストームクルーザー、アークテリクス ベータジャケット、ノースフェイス クライムライト等）の止水ファスナー、立体裁断フード、ブランドロゴを忠実にイラスト化。',
      composition: '【ダイナミック構図】強風が吹き荒れ、雨しぶきや吹雪が横殴りに吹き付ける荒天の稜線。フードを顔ギリギリまで絞り込み、身を低くして斜めに傾けながら風雪に耐える力強い「耐風姿勢」。雨粒や雪煙がダイナミックに舞い散るドラマチックなアングル。'
    };
  }

  // 4. テント・タープ・宿泊・キャンプギア系
  if (/tent|tarp|shelter|mat|sleeping|camp|宿泊|ステラリッジ|エアライズ/.test(text)) {
    return {
      gear: '実在する山岳テント（例: モンベル ステラリッジ、アライテント エアライズ、MSR エリクサー等）のポール構造、鮮やかなフライシートの張り、ガイライン（張り綱）を忠実にイラスト化。',
      composition: '【ダイナミック構図】夕暮れの茜色または朝焼けに染まるドラマチックな山岳稜線。風でしなるテントポールを両手で力強く抑えながらペグを打ち込む躍動感のある設営シーン、または完成したテントの前室から淹れたてのマグカップを持って嬉しそうに顔を出し、眼下に広がる大雲海を見渡す広角アングル。'
    };
  }

  // 5. バーナー・クッカー・山ごはん・行動食系
  if (/burner|cooker|stove|cup|bottle|food|snacks|ごはん|メスティン/.test(text)) {
    return {
      gear: '実在するアウトドア用バーナー・クッカー（例: SOTO ウィンドマスター、プリムス 153ウルトラバーナー、スノーピーク チタンマグ等）の五徳やガス缶、炎を忠実にイラスト化。',
      composition: '【ダイナミック構図】山頂の岩場で手前に対角線構図でバーナーと湯気の立つクッカーを大きく配置。風防を手でかばいながら青い炎を見守り、カップ麺を両手で抱えて「まだかな…」と待ちきれない表情で熱気に顔を近づけている臨場感あふれる近接ショット。'
    };
  }

  // 6. 遭難・安全・熊鈴・ヘッドランプ・エマージェンシー系
  if (/bear|bell|headlamp|first_aid|trouble|safety|gps|cocoheli|救助|遭難|熊/.test(text)) {
    return {
      gear: '実在する熊鈴（例: 東京ベル、カウベルタイプ）、実在のヘッドランプ（Black Diamond、Petzl Actik等）、エマージェンシーシートを忠実にイラスト化。',
      composition: '【ダイナミック構図】薄暗い樹林帯や日没後の山道。ヘッドランプのシャープな光筋が霧深い登山道を力強く照らし出すドラマチックなコントラスト。背後の物音にビクッと肩を跳ね上がらせて振り返る緊迫感とコミカルさが同居した生き生きとした表情とポーズ。'
    };
  }

  // 7. 初心者ガイド・コース選び・高山病・バテ対策系
  return {
    gear: '実在する定番ギア（キャラバン C1_02Sの登山靴、カリマー Ridge 30のザック、モンベルのレインジャケット）を忠実にイラスト化。',
    composition: '【ダイナミック構図】急峻な木の階段や見上げるような岩場の急登。膝に両手を当てて息を切らし、魂が口から出そうなほどへたり込むJKと、ぐんぐん上へと続く険しい登山道を見上げるドラマチックなパース。または山頂に到達して青空をバックに両手を突き上げて歓喜の叫びをあげる全身の躍動ポーズ。'
  };
}

/** agy に渡すプロンプトを構築 */
function buildPrompt(item) {
  const dynamic = buildDynamicSceneInstruction(item);
  const altOrHint = item.alt || item.promptHint || '';

  const promptText = `
画像生成ツールで登山ブログの記事トップ画像（サムネイル）を1枚だけ生成してください（16:9 横長、アスペクト比 16:9）。

【最優先要件: 構図のダイナミズムと脱・金太郎飴】
⚠️ 単調な直立ポーズや、要素だけを入れ替えたテンプレート・金太郎飴のような画像は厳禁です！
${dynamic.composition}
${altOrHint ? `※記事の個別コンテキスト: 「${altOrHint}」の情景を取り入れてください。` : ''}

【道具の実在性と忠実な再現（最重要）】
${dynamic.gear}
- 道具が主役の記事の場合: その商品（モデル名・ブランドロゴ・カラー・ポケットやベルトの形状）を忠実にイラスト化すること。
- ノウハウ記事の場合: 架空の適当な道具を描くことは厳禁。上記で指定された実在の登山道具を忠実にイラスト化して身につけさせること。

【画風・キャラクターの統一】
- 作業フォルダ内の jk_ref.jpg を参照画像として画像生成ツールに渡し、キャラクターの見た目（茶髪ショートボブ、頭身1:2〜1:2.5のデフォルメ調、山ガール登山ウェア）と画風（アニメ調、くっきりした黒の主線、鮮明なアニメ塗り）を完全に一致させてください。
- 背景: 本格的でリアルな日本の山岳自然（北アルプス風の岩稜帯、樹林帯、山小屋、雲海など、テーマに即した情景）。

それ以外の作業（参照画像の読み込み以外のファイル閲覧・編集・コピー、コマンド実行、解説文の出力）は一切しないでください。

最後に次のどれか1行だけを出力してください。
- 生成できた: GENERATED
- 画像生成の利用上限（レートリミット）に達している: LIMIT <解除までの分数（整数）>（分数が分からなければ LIMIT unknown）
- それ以外の理由で生成できない: FAILED <理由>
`.trim();

  return promptText;
}

/** agy -p で1枚生成 */
function generateOne(item, refImage) {
  return new Promise((done) => {
    rmSync(WORK_DIR, { recursive: true, force: true });
    mkdirSync(WORK_DIR, { recursive: true });

    if (refImage && existsSync(refImage)) {
      copyFileSync(refImage, resolve(WORK_DIR, 'jk_ref.jpg'));
    }

    const startedAt = Date.now();
    const prompt = buildPrompt(item);

    log(`🚀 [${item.slug}] agy による生成を開始...`);

    const child = spawn(
      AGY,
      ['-p', prompt, '--output-format', 'json', '--mode', 'accept-edits', '--print-timeout', '300s'],
      {
        cwd: WORK_DIR,
        stdio: ['ignore', 'pipe', 'pipe'],
      }
    );

    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (d) => { stdout += d; });
    child.stderr.on('data', (d) => { stderr += d; });

    const timer = setTimeout(() => {
      child.kill('SIGTERM');
    }, CALL_TIMEOUT_MS);

    child.on('close', (code) => {
      clearTimeout(timer);
      let response = '';
      let conversationId = null;

      try {
        const j = JSON.parse(stdout);
        response = j.response || '';
        conversationId = j.conversation_id || null;
      } catch {
        response = stdout;
      }

      const lines = response.trim().split('\n').map((l) => l.trim()).filter(Boolean);
      const last = lines[lines.length - 1] || '';

      // ① brain フォルダから生成画像を探す
      const brain = conversationId ? resolve(BRAIN_DIR, conversationId) : null;
      let found = null;
      if (brain && existsSync(brain)) {
        const images = readdirSync(brain)
          .filter((f) => /\.(jpe?g|png|webp)$/i.test(f))
          .map((f) => resolve(brain, f))
          .filter((p) => statSync(p).mtimeMs >= startedAt - 1000)
          .sort((x, y) => statSync(y).mtimeMs - statSync(x).mtimeMs);
        found = images.find((p) => imageFormat(p));
      }

      if (found) {
        return done({ kind: 'saved', path: found, format: imageFormat(found) });
      }

      // ② レートリミット判定（LIMIT <分>）
      const limit = last.match(/^LIMIT\s+(\d+|unknown)/i);
      if (limit) {
        const mins = /^\d+$/.test(limit[1]) ? Number(limit[1]) : null;
        return done({ kind: 'limit', minutes: mins, reason: last });
      }

      if (/rate limit|quota exceeded|limit reached|too many requests|5時間/i.test(response)) {
        const m = response.match(/(\d+)\s*(?:minutes?|分)/i);
        const mins = m ? Number(m[1]) : null;
        return done({ kind: 'limit', minutes: mins, reason: 'rate limit detected in response' });
      }

      const reason = /^GENERATED\b/.test(last)
        ? `GENERATED と返ったが画像が見つからない (conversation: ${conversationId})`
        : (last || stderr.trim().split('\n').pop() || `終了コード ${code}`);

      done({ kind: 'failed', reason: reason.slice(0, 300) });
    });
  });
}

function placeImage(tempPath, targetFileName) {
  const dest = resolve(IMG_DIR, targetFileName);
  mkdirSync(dirname(dest), { recursive: true });
  const ext = extname(targetFileName).toLowerCase();
  const want = ext === '.png' ? 'png' : ext === '.webp' ? 'webp' : 'jpeg';

  try {
    execFileSync('sips', ['-s', 'format', want, tempPath, '--out', dest], { stdio: 'ignore' });
  } catch {
    copyFileSync(tempPath, dest);
  }
}

function updateArticleMarkdown(item) {
  const { mdPath, targetFile, alt, title } = item;
  const content = readFileSync(mdPath, 'utf8');
  const lines = content.split('\n');

  const header = lines.slice(0, 15).join('\n');
  if (/!\[.*?\]\(\/img\/[^)]+\)/.test(header)) return;

  const imageAlt = alt || `${title}のイメージ画像`;
  const imgTag = `\n![${imageAlt}](/img/${targetFile})\n`;

  let insertIdx = 1;
  for (let i = 0; i < lines.length && i < 10; i++) {
    if (lines[i].startsWith('Tags:') || lines[i].startsWith('# ')) {
      insertIdx = i + 1;
    }
  }

  lines.splice(insertIdx, 0, imgTag);
  writeFileSync(mdPath, lines.join('\n'), 'utf8');
}

function commitProgress(item) {
  const relImg = `img/${item.targetFile}`;
  const relMd = `article/${item.slug}.md`;

  try {
    execFileSync('git', ['add', relImg, relMd], { cwd: ROOT, stdio: 'ignore' });
    const msg = `内容: ${item.slug} のトップ画像を実在ギアイラストで作成 [機能: 記事トップ画像一括生成]`;
    execFileSync('git', ['commit', '-m', msg], { cwd: ROOT, stdio: 'ignore' });
    log(`📦 コミット完了: ${msg}`);
  } catch (e) {
    log(`⚠️ コミットスキップ: ${e.message}`);
  }
}

async function main() {
  log('====================================================');
  log('記事トップ画像一括自律生成ワーカー起動');
  log('====================================================');

  const refImage = getReferenceImage();
  if (refImage) {
    log(`🎨 キャラクター参照画像: ${basename(refImage)}`);
  }

  for (;;) {
    const pending = getPendingArticles();
    if (pending.length === 0) {
      log('🎉 全記事のトップ画像が完成しました！');
      try { unlinkSync(LIMIT_FILE); } catch {}
      break;
    }

    log(`📋 未作成のトップ画像: 残り ${pending.length} 件`);
    const current = pending[0];
    log(`👉 処理開始: [${current.slug}] ${current.title}`);

    const result = await generateOne(current, refImage);

    if (result.kind === 'saved') {
      log(`✅ 生成成功: ${result.path}`);
      placeImage(result.path, current.targetFile);
      updateArticleMarkdown(current);
      commitProgress(current);

      const state = readState();
      state.completed.push(current.slug);
      writeState(state);

      try { unlinkSync(LIMIT_FILE); } catch {}
      await sleep(3000);

    } else if (result.kind === 'limit') {
      const waitMin = result.minutes && result.minutes > 0 ? result.minutes + 2 : DEFAULT_LIMIT_WAIT_MIN;
      const untilMs = Date.now() + waitMin * 60 * 1000;
      log(`⏳ 【画像生成レートリミット検知】 ${result.reason}`);
      log(`💤 解除まで ${waitMin} 分間スリープして待機します (再開予定: ${new Date(untilMs).toLocaleTimeString('sv-SE')})`);

      writeFileSync(LIMIT_FILE, JSON.stringify({
        until: new Date(untilMs).toISOString(),
        minutes: waitMin,
        reason: result.reason,
        since: new Date().toISOString()
      }, null, 2), 'utf8');

      await sleep(waitMin * 60 * 1000);
      try { unlinkSync(LIMIT_FILE); } catch {}
      log('⏰ スリープ終了。生成処理を自動再開します...');

    } else {
      log(`❌ 生成失敗: ${result.reason}`);
      const state = readState();
      state.failed[current.slug] = (state.failed[current.slug] || 0) + 1;
      writeState(state);

      if (state.failed[current.slug] >= 3) {
        log(`⚠️ 3回失敗したため、10分待機後に再試行します...`);
        await sleep(10 * 60 * 1000);
      } else {
        await sleep(5000);
      }
    }
  }
}

// CLIから直接実行された場合のみ main() を呼ぶ
if (process.argv[1] === fileURLToPath(import.meta.url)) {
  main().catch((err) => {
    log(`💥 予期せぬエラー: ${err.stack || err}`);
    process.exit(1);
  });
}
