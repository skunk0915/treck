#!/usr/bin/env node
/**
 * generate-article-images.mjs — 記事トップ画像（サムネイル）一括自律生成ワーカー
 *
 * 【確定仕様（ユーザー承認済み方針）】
 * 1. 外部APIは一切使用禁止。サブスクの Antigravity CLI（~/.local/bin/agy）を非対話（-p）で1枚ずつ実行。
 * 2. 【最重要: ストーリー性とキャラクターの演技】:
 *    - 単調な立ち絵・静止ポーズは完全禁止。平坦で間延びしたスカスカ感を徹底排除。
 *    - 初心者JKならではのリアルな感情（恐怖、困惑、青ざめ、へばり、感動、ドヤ顔、至福など）を、
 *      「豊かな表情」と「生き生きとした身体の仕草・重心（腰が引ける、膝が震える、岩にしがみつく、へたり込む等）」で全身で演じさせる。
 * 3. 【パース強調・魚眼の禁止】: レンズを効かせた極端なパースや手足の肥大化は不要。自然なカメラアングルで描く。
 * 4. 【文字・セリフ完全禁止】: 画像内にテキスト、セリフ、吹き出し、アルファベット、文字を一切描かない（表情と仕草だけで語る）。
 * 5. 【頭身と輪郭線】: 頭と身体のサイズ比率は１：２（約2〜2.5頭身のデフォルメちびキャラ体型）。caravan_shoes_guide_01.jpg を参照した太くくっきりした黒の主線（アニメ塗り）。
 * 6. 【服装】: 学生服（セーラー服）は完全禁止。必ず記事に合った山ガール登山ウェア（マウンテンパーカー、トレッキングパンツ、グローブ等）を着用。
 * 7. 【道具の実在性と忠実な再現】: 記事の主役ギア、または実在する定番ギア（スポルティバ、キャラバン C1_02S, カリマー Ridge, モンベル等）を忠実にイラスト化。
 * 8. 【レートリミット対策】: 画像生成専用レートリミット（LIMIT <分>）を検知し、安全マージン付きで自動スリープ・自動再開。
 * 9. 全記事完了まで自動継続。
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

/** 記事テーマに合わせたストーリー性とキャラクターの身体演技・表情演出を生成 */
function buildStorySceneInstruction(item) {
  const { slug, title, alt, promptHint } = item;
  const text = `${slug} ${title} ${alt} ${promptHint}`.toLowerCase();

  // 1. アプローチシューズ・岩場・クライミング系
  if (/approach|climbing|岩場/.test(text)) {
    return {
      gear: 'スポルティバのアプローチシューズ（黄色と黒のカラー）を忠実にイラスト化。',
      story: '切り立った断崖絶壁の岩棚。岩壁を登る途中でふと足元の高度感に気付いてしまい、「ひえぇ…高すぎる！」と顔面蒼白で冷や汗を流し、目を白黒させながら岩壁にしがみつき、腰が引けて膝をガクガク震わせている初心者ならではのコミカルで必死な恐怖のシーン。足元のアプローチシューズが小さな岩のステップに必死に乗っかっている。'
    };
  }

  // 2. 登山靴・歩行・トレッキングポール系
  if (/shoes|boots|foot|歩行|technique|walking|stock|pole|trekking_shoes/.test(text)) {
    return {
      gear: '実在する登山靴（キャラバン C1_02S または スポルティバ TX5）と、トレッキングポールを忠実にイラスト化。',
      story: '急な木の根やゴロゴロしたガレ場の登山道。足を取られそうになって「おっとっと…！」と両手を広げてバランスを崩しかけ、慌ててポールを突いて踏ん張っている躍動感と焦りの表情。'
    };
  }

  // 3. ザック・リュック・パッキング・重さ系
  if (/backpack|zack|bag|packing|ridge|saas_fee|aircontact|baltoro|ducan|trion/.test(text)) {
    return {
      gear: '記事に登場する実在の特定ザック（カリマー Ridge 30、ミレー Saas Fee、マムート Trion、グレゴリー等）を忠実にイラスト化。',
      story: '山小屋前や休憩所。パンパンに詰まった大型ザックの重さに後ろに引っ張られ、「重っっ！？これ背負って歩くの！？」と目を丸くして前のめりに踏ん張りながら、困惑と驚きが混ざった愛嬌あるリアクション。'
    };
  }

  // 4. レインウェア・ハードシェル・悪天候・防寒系
  if (/rain|hardshell|weather|storm|wind|typhoon|snow|winter|寒|雨|fleece|down|insulated/.test(text)) {
    return {
      gear: '実在する防水透湿ジャケット（モンベル ストームクルーザー、アークテリクス ベータジャケット等）の鮮やかなカラーとフードを忠実にイラスト化。',
      story: 'ザーザーと雨が降る山道の中、フードをすっぽり被って身をかがめながら、「うわー雨降ってきた…でも全然濡れない！」と袖をまじまじと見つめて驚きと安心が混ざった初心者の素直な表情。'
    };
  }

  // 5. テント・タープ・キャンプ・宿泊系
  if (/tent|tarp|shelter|mat|sleeping|camp|宿泊|ステラリッジ|エアライズ/.test(text)) {
    return {
      gear: '実在する山岳テント（モンベル ステラリッジ、アライテント エアライズ、MSR等）を忠実にイラスト化。',
      story: '夕暮れや青空のテント場。説明書を片手にペグハンマーを持って「これで合ってるのかな…？」と首をかしげて苦戦している姿、または設営できて満足そうにテントから顔を覗かせて喜んでいるシーン。'
    };
  }

  // 6. バーナー・クッカー・山ごはん・行動食系
  if (/burner|cooker|stove|cup|bottle|food|snacks|ごはん|メスティン/.test(text)) {
    return {
      gear: '実在するアウトドア用バーナー・クッカー（SOTO ウィンドマスター、プリムス 153、スノーピーク等）を忠実にイラスト化。',
      story: '山頂の岩場で、風を手で遮りながらバーナーの炎を見つめ、カップ麺やマグカップを抱えて「早くお湯沸いて〜！」とお腹を空かせて待ちきれないコミカルで必死な表情。'
    };
  }

  // 7. 遭難・安全・熊鈴・ヘッドランプ・小物系
  if (/bear|bell|headlamp|first_aid|trouble|safety|gps|cocoheli|救助|遭難|熊/.test(text)) {
    return {
      gear: '実在する熊鈴やヘッドランプ、エマージェンシー小物を忠実にイラスト化。',
      story: '薄暗い樹林帯の山道で、ザックにつけた熊鈴のリンリンという音にビクビクしながら、「熊出ないよね…？」と周りをキョロキョロ警戒して歩いているコミカルな緊張感。'
    };
  }

  // 8. 初心者ガイド・コース選び・高山病・バテ対策系
  return {
    gear: '実在する定番ギア（キャラバン C1_02S登山靴、カリマー Ridge 30ザック、モンベルウェア）を忠実にイラスト化。',
    story: '果てしなく続く階段の急登で、膝に手をついて魂が口から出そうになりながら「山頂…まだですか…」とへたり込んでいる初心者ならではのリアルなバテ姿、または山頂標識の横で両手を突き上げて歓喜の叫びをあげているシーン。'
  };
}

/** agy に渡すプロンプトを構築 */
function buildPrompt(item) {
  const storyInfo = buildStorySceneInstruction(item);
  const altOrHint = item.alt || item.promptHint || '';

  const promptText = `
画像生成ツールで登山ブログの記事アイキャッチ画像を1枚生成してください（アスペクト比 16:9 横長）。

【最重要ルール: スタイル・頭身・輪郭線（厳守）】
- 頭と身体のサイズ比率は１：２（約2〜2.5頭身のデフォルメちびキャラ体型）。リアル等身や4頭身以上は絶対に禁止。
- 添付の caravan_style.jpg と同じ、くっきりとした太い黒の輪郭線（太い主線のアニメ塗り）で描いてください。
- 顔立ちと髪型は添付の jk_face.jpg（茶髪ショートボブ、愛嬌のある表情）に合わせてください。
- ⚠️ パースを強調するような極端な遠近感や魚眼レンズ効果は一切不要です（禁止）。自然なカメラアングルで描いてください。
- ⚠️ 文字、セリフ、吹き出し、アルファベット、効果音等のテキストは画像内に絶対に描かないでください（完全禁止）。

【ストーリー性とキャラクターの演技（最重要）】
⚠️ 単調な立ち絵や静止ポーズは禁止です。一目でシチュエーションと感情がわかるストーリーを描いてください。
- シーン: ${storyInfo.story}
${altOrHint ? `※記事の個別コンテキスト: 「${altOrHint}」` : ''}
- 初心者JKの表情と仕草: テキストやセリフを使わず、目・口・冷や汗などの表情と、身体の重心・仕草（しがみつく、腰が引ける、膝が震える、へばる、驚く、喜ぶなど）だけで感情豊かに表現してください。

【服装と道具の実在性】
- 服装: 山ガールのアウトドア登山ウェア（マウンテンパーカー、トレッキングパンツ、グローブ、ザック）。学生服は禁止。
- 道具: ${storyInfo.gear} 実在する道具を忠実にイラスト化してください。架空の道具は厳禁。
- 背景: 切り立った岩肌、樹林帯、山並み、雲海など、シチュエーションに即したリアルな日本の山岳自然風景。

それ以外の作業は一切せず、最後に GENERATED とだけ出力してください。
`.trim();

  return promptText;
}

/** agy -p で1枚生成 */
function generateOne(item) {
  return new Promise((done) => {
    rmSync(WORK_DIR, { recursive: true, force: true });
    mkdirSync(WORK_DIR, { recursive: true });

    const faceRef = resolve(IMG_DIR, 'jk-body.jpg');
    const styleRef = resolve(IMG_DIR, 'caravan_shoes_guide_01.jpg');
    if (existsSync(faceRef)) copyFileSync(faceRef, resolve(WORK_DIR, 'jk_face.jpg'));
    if (existsSync(styleRef)) copyFileSync(styleRef, resolve(WORK_DIR, 'caravan_style.jpg'));

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
  log('記事トップ画像一括自律生成ワーカー起動（ストーリー性・演技重視・パース禁止）');
  log('====================================================');

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

    const result = await generateOne(current);

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
