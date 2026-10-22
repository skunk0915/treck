#!/usr/bin/env node
/**
 * generate-article-images.mjs — 記事トップ画像（サムネイル）一括自律生成ワーカー
 *
 * 【重要要件】
 * 1. 外部APIは一切使用禁止。サブスクの Antigravity CLI（~/.local/bin/agy）を非対話（-p）で1枚ずつ実行。
 * 2. 【最重要・等身の厳守】: 確実に「3頭身くらい」のデフォルメちびキャラ（3 heads tall chibi style）。
 *    リアル等身や4頭身以上は絶対に禁止。頭が大きめで手足が短い愛嬌のある3頭身スタイル。
 * 3. 【「ダイナミック」指示は撤廃】: 奇抜な魚眼や極端なパースは使わず、自然な構図にする。
 * 4. 【初心者JKの豊かな喜怒哀楽】: アウトドア初心者ならではのリアルな感情（へばる・困る等のトラブルだけでなく、感動・ドヤ顔・ワクワク・ホッとする等、喜怒哀楽をバランスよく豊かに表現）。
 * 5. 【服装の厳格な指示】: 参照画像（jk-body.jpg）のセーラー服（学生服）は禁止。必ず記事に合った山ガール登山ウェア・アウトドアファッションへ着せ替える。
 * 6. 【道具の実在性と忠実な再現】: 記事の主役ギア、または実在する定番ギア（キャラバン C1_02S, カリマー Ridge, モンベル等）を忠実にイラスト化。
 * 7. 【レートリミット対策】: 画像生成専用レートリミット（LIMIT <分>）を正確に検知し、安全マージン付きで自動スリープ・自動再開。
 * 8. 全記事完了まで自動継続。
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

const CALL_TIMEOUT_MS = 6 * 60 * 1000;
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
  const masterRef = resolve(IMG_DIR, 'jk-body.jpg');
  if (existsSync(masterRef)) return masterRef;

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

/** 記事テーマに合わせた初心者JKのシチュエーション・感情表現（喜怒哀楽）・実在ギアの演出指示 */
function buildSceneInstruction(item) {
  const { slug, title, alt, promptHint } = item;
  const text = `${slug} ${title} ${alt} ${promptHint}`.toLowerCase();

  // 1. 靴・アプローチシューズ・歩行系
  if (/approach|shoes|boots|foot|歩行|technique|walking|stock|pole|trekking_shoes/.test(text)) {
    return {
      gear: '実在する登山靴・アプローチシューズ（例: スポルティバ TX4 または TX5、キャラバン C1_02S）を忠実にイラスト化。',
      scene: '【シチュエーションと感情】岩場のある登山道で、「よいしょ…！」と真剣なジト目で一歩を踏み出し、靴のグリップを確かめながら慎重に歩いている初心者JKの可愛らしい姿。自然なアングルで、キャラクターの全身（3頭身）と着用している登山靴、山道の岩肌がしっかりと見える構図。'
    };
  }

  // 2. ザック・リュック・パッキング系
  if (/backpack|zack|bag|packing|ridge|saas_fee|aircontact|baltoro|ducan|trion/.test(text)) {
    return {
      gear: '記事に登場する実在の特定ザック（例: カリマー Ridge 30、ミレー Saas Fee、マムート Trion/Ducan等）のブランドロゴ、カラー、ポケット形状を忠実にイラスト化。',
      scene: '【シチュエーションと感情】自分の体に対して大きめのザックを背負い、「重っ…！でもカッコいいかも！」と少し誇らしげなドヤ顔と困り顔が混ざったような初心者らしい表情。山小屋前や登山道の自然な立ち姿。'
    };
  }

  // 3. レインウェア・ハードシェル・悪天候・防寒系
  if (/rain|hardshell|weather|storm|wind|typhoon|snow|winter|寒|雨|fleece|down|insulated/.test(text)) {
    return {
      gear: '実在する防水透湿ジャケット（例: モンベル ストームクルーザー、アークテリクス ベータジャケット等）の鮮やかなカラーとフード、ブランドロゴを忠実にイラスト化。',
      scene: '【シチュエーションと感情】霧雨や小雨が降る登山道で、フードを被りながら「雨降ってきたけど、このカッパ弾いてる！」と袖の水滴を不思議そうに見つめる、初心者らしい素直なリアクション。'
    };
  }

  // 4. テント・タープ・キャンプ・宿泊系
  if (/tent|tarp|shelter|mat|sleeping|camp|宿泊|ステラリッジ|エアライズ/.test(text)) {
    return {
      gear: '実在する山岳テント（例: モンベル ステラリッジ、アライテント エアライズ、MSR エリクサー等）を忠実にイラスト化。',
      scene: '【シチュエーションと感情】テント場の美しい山並みを背景に、設営したテントの横で「できたー！」と満足そうに笑みを浮かべたり、テントからちょこんと顔を出して外の景色にワクワクしている初心者JKの微笑ましい姿。'
    };
  }

  // 5. バーナー・クッカー・山ごはん・行動食系
  if (/burner|cooker|stove|cup|bottle|food|snacks|ごはん|メスティン/.test(text)) {
    return {
      gear: '実在するアウトドア用バーナー・クッカー（例: SOTO ウィンドマスター、プリムス 153、スノーピーク等）を忠実にイラスト化。',
      scene: '【シチュエーションと感情】山頂のベンチや岩の上で、湯気の立つマグカップやクッカーを両手で持ち、「ふーふー」しながら嬉しそうに温かい飲み物や山ごはんを味わっている至福の表情。'
    };
  }

  // 6. 遭難・安全・熊鈴・ヘッドランプ・小物系
  if (/bear|bell|headlamp|first_aid|trouble|safety|gps|cocoheli|救助|遭難|熊/.test(text)) {
    return {
      gear: '実在する熊鈴やヘッドランプ、エマージェンシー小物を忠実にイラスト化。',
      scene: '【シチュエーションと感情】静かな樹林帯の山道で、ザックにつけた熊鈴を「リンリン鳴ってちょっと恥ずかしいな…」と気にしながらジト目で歩いているユーモラスな表情。'
    };
  }

  // 7. 初心者ガイド・コース選び・高山病・バテ対策系
  return {
    gear: '実在する定番ギア（キャラバン C1_02S登山靴、カリマー Ridge 30ザック、モンベルウェア）を忠実にイラスト化。',
    scene: '【シチュエーションと感情】山道を歩きながら、「山頂まであとどれくらいですか…？」と少し疲れた表情を浮かべたり、逆に登頂して「着いたー！」と達成感いっぱいに喜んでいる、初心者のリアルな感情が伝わるシーン。'
  };
}

/** agy に渡すプロンプトを構築 */
function buildPrompt(item) {
  const sceneInfo = buildSceneInstruction(item);
  const altOrHint = item.alt || item.promptHint || '';

  const promptText = `
画像生成ツールで登山ブログの記事トップ画像（サムネイル）を1枚だけ生成してください（アスペクト比 16:9 横長）。

【最優先要件: 3頭身のデフォルメちびキャラ（等身の厳守）】
⚠️ 等身が高くなることは絶対に禁止です！
- 必ず頭のサイズが全体の約1/3を占める「3頭身くらい」のデフォルメちびキャラ（3 heads tall chibi style）で描いてください。
- 4頭身以上やリアル等身は絶対にNGです。頭が大きめで手足が短く、愛嬌のあるちびキャラ等身を厳格に維持してください。
- 極端な魚眼レンズやパース歪み（手足を巨大に突き出す構図）は禁止です。自然な視野角で、キャラクターの全身が画面に可愛らしく収まるようにしてください。

【キャラクターと服装の指定（学生服は禁止）】
- 作業フォルダ内の jk_ref.jpg（img/jk-body.jpg）を参照画像として画像生成ツールに渡し、キャラクターの顔立ち（茶髪ショートボブ、ジト目で愛嬌のある目元、3頭身のちびキャラ造形）と画風（アニメ調、くっきりした黒の主線、鮮明なアニメ塗り）を完全に一致させてください。
- ⚠️ 参照画像では学生服（セーラー服）を着ていますが、学生服は絶対に描かないでください！記事内容が登山・アウトドアのため、必ず記事テーマに合った「山ガール登山ウェア（機能性マウンテンパーカー、トレッキングパンツ、山スカート＋タイツ、登山帽子など）」に着せ替えてください。

【シチュエーションと初心者JKの感情表現（喜怒哀楽）】
${sceneInfo.scene}
${altOrHint ? `※記事の個別コンテキスト: 「${altOrHint}」` : ''}

【道具の実在性と忠実な再現】
${sceneInfo.gear}
- 架空の適当な道具を描くことは厳禁。指定された実在の登山道具を忠実にイラスト化して身につけさせてください。
- 背景: 美しい日本の山岳風景（樹林帯の登山道、岩場、稜線、山小屋前、雲海など、シーンに合った自然な山岳風景）。

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
  log('記事トップ画像一括自律生成ワーカー起動（3頭身・喜怒哀楽・実在ギア）');
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

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  main().catch((err) => {
    log(`💥 予期せぬエラー: ${err.stack || err}`);
    process.exit(1);
  });
}
