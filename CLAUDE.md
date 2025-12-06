# HameThread - WordPress Forum Plugin

## プロジェクト概要

WordPressのフォーラム/スレッド機能を提供するプラグイン。

### バージョン要件

| 項目 | 参照ファイル |
|------|-------------|
| PHP / WordPress | `hamethread.php` ヘッダー、`composer.json` |
| Node.js | `package.json` の `engines` および `volta` |

W.I.P ここに対照表を書く

## ディレクトリ構成

```
hamethread/
├── app/Hametuha/Thread/    # PHPソースコード（PSR-0オートロード）
│   ├── Hooks/              # WordPressフック実装
│   ├── Rest/               # REST APIエンドポイント
│   ├── UI/                 # UIコンポーネント
│   ├── Model/              # データモデル
│   ├── Pattern/            # 抽象パターン
│   └── Screen/             # Hashboard画面統合
├── src/                    # ソースアセット
│   ├── js/                 # JavaScriptソース
│   └── scss/               # SASSソース
├── assets/                 # ビルド済みアセット（自動生成）
│   ├── css/
│   └── js/
├── template-parts/         # PHPテンプレート
├── includes/               # その他PHPファイル
├── tests/                  # PHPUnitテスト
└── bin/                    # ビルドスクリプト
```

## 依存関係の構造

### Hashboard統合について

このプラグインは単体では `hametuha/hashboard` に依存しない。Hashboardはcomposer.jsonの `require-dev` にのみ含まれる。

- **Hashboardがない環境**: 通常のWordPressとして動作
- **Hashboardがある環境**: `SupportHashboard.php` と `HashboardScreen.php` が動作し、管理画面スクリーンを追加。この場合のみBootstrap UIを使用する。

### Bootstrap依存の分離

- `hamethread-hashboard.scss` / `hamethread-hashboard.js`: Hashboard環境専用（Bootstrap依存あり）
- `hamethread.scss` / `hamethread-comment.js`: コメントフォーム用（Bootstrap依存なし）

wp-dependencies.jsonで依存関係を管理:
- `hamethread-hashboard` CSSは `bootstrap` に依存
- `hamethread`, `hamethread-comment` はBootstrapに依存しない

## ビルドシステム

### @kunoichi/grab-deps

JS/CSSファイルのヘッダーコメントから依存関係を抽出し、`wp-dependencies.json` を生成する。
コメントは `/*!` ではじめないと、トランスパイル時に消されてしまいます。

**JSファイルヘッダー例:**
```javascript
/*!
 * Handle: hamethread
 * @deps jquery-effects-highlight, wp-i18n
 */
```

**SCSSファイルヘッダー例:**
```scss
/*!
 * Handle: hamethread-hashboard
 * @deps bootstrap
 */
```

### 主要コマンド

```bash
# 開発環境起動（wp-env）
npm start

# フルビルド（CSS + JS + dependencies）
npm run build

# 個別ビルド
npm run build:css    # SASS → CSS
npm run build:js     # JS バンドル
npm run dump         # wp-dependencies.json 生成

# ファイル監視
npm run watch

# Lint
npm run lint         # CSS + JS lint
npm run fix:js       # JS auto fix
npm run fix:css      # Sass auto fix
composer lint        # PHP lint (PHPCS)
composer fix         # PHP auto-fix (PHPCBF)

# テスト
composer test        # PHPUnit
npm run test         # wp-env経由でPHPUnit（ローカル開発用）

## 翻訳ファイル生成（ローカル用）
npm run i18n         # poファイルの生成
npm run i18n:compile # poファイルを元にmoと翻訳用JSONを生成
```

## PHP構造

### オートロード

- `Hametuha\Thread` 名前空間 → `app/` ディレクトリ（PSR-0）
- Composerオートローダーで読み込み

### 主要クラス

| クラス | 役割 |
|--------|------|
| `Thread.php` | メインシングルトン、初期化 |
| `Hooks/SupportHashboard.php` | Hashboard検出・統合 |
| `Screen/HashboardScreen.php` | Hashboard管理画面 |
| `UI/CommentForm.php` | コメントフォーム（Bootstrap非依存） |

### パターン

`hametuha/pattern` ライブラリを使用したシングルトンパターン。

## テスト

### PHPUnit

PHPのバージョンを揃えるため、Docker内で実行します。

```bash
# Docker起動（起動していない場合のみ）
npm start
# テスト実行
npm test
```

テストファイルは `tests/` に配置。プレフィックス `Test` + サフィックス `.php`。

### Lint

- **PHP**: PHPCS (WordPress Coding Standards)
- **JS**: ESLint (@wordpress/scripts)
- **CSS**: stylelint (@wordpress/scripts)

## GitHub Actions

`.github/workflows/` を参照。PHPUnit、PHPCS、フロントエンドビルドのCIが設定されている。

## Git操作

```bash
# コミット（Co-authored-by自動付与）
git cc-commit "コミットメッセージ"
```

## ローカル環境

- @wordpress/env でDockerが立ち上がります。 `http://localhost:8888` でアクセスできます。
- `npm run cli` で `wp` と同様のwp-cliコマンドが使えます。
- `tests/hashboard-local.php` はローカル開発限定で定数 `HAMETUHA_LOGGED_IN_AS` にユーザー名を定義することにより、
  特定のユーザーとしてログインした状態でアクセスできます。Chrome MCP Serverで利用できます。

### デバッグログ

PHPエラーログは Docker内の `/var/www/html/wp-content/debug.log` に出力される。

```bash
npm run log        # ログ全体を表示
npm run log:tail   # リアルタイムで監視（Ctrl+C で終了）
```


## 注意事項

1. **アセットの直接編集禁止**: `assets/` 内のファイルは自動生成される。`src/` を編集すること。
2. **依存関係の記述**: JS/CSSファイルのヘッダーコメントで依存関係を宣言。`npm run dump` で `wp-dependencies.json` に反映。
3. **Hashboard条件分岐**: Hashboardの有無は実行時に判定。`class_exists()` や `did_action()` で確認。
4. **Node.js バージョン**: Voltaでv24にpinされている。
