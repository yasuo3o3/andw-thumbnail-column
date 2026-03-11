# WORDPRESS.md — WordPress プラグイン開発規約（v1.0）

目的：WordPress.org の審査・運用で詰まらない"素直な"プラグインを作る。

**本ファイルは最上位規範**：他ドキュメント（[CONTRIB.md](CONTRIB.md)）と矛盾時は本ファイルを優先。

## 禁止事項
1. **コア直読み禁止**：wp-config.php / wp-load.php / wp-admin/includes/* の直接require禁止。例外は「hook内でrequire_once→即利用」のみ
2. **`<script>/<style>`直書き禁止**：JS/CSSは wp_enqueue_* と wp_add_inline_* のみ。デバッグconsoleもinline経由
3. **php://input丸読み禁止**：`$_POST/$_GET`全量処理・全量ログ禁止。必要キーのホワイトリスト抽出＋型検証のみ

## 命名規約
**接頭辞 andw 必須**：関数/クラス/定数/フック/オプション/CPT/タクソノミー/JS変数/CSSクラスに適用

## セキュリティ実装
- **入口先頭で nonce + current_user_can() 必須**
- **RESTは permission_callback と args/schema 必須**

## i18n
**Text Domain=プラグインスラッグ**。不一致は出荷不可。

## 出荷ゲート
以下のどれか一つでもNGなら出荷不可：
1. Contributors に申請アカウント名が含まれている
2. Text Domain＝スラッグ
3. 直書き `<script>/<style>` が無い
4. コア直読みが無い（例外要件を満たす場合のみ可）
5. 全エンドポイントで nonce+capability を先頭に実装
6. 全量入力・全量ログを禁止

## アンインストールポリシー（必須）
- ルートに `uninstall.php` を置くこと。プラグイン停止では実行しない。削除時のみ発火。
- 削除対象（当プラグインが作成したものに限定）：
  - options：当プラグインが `add_option()` / `update_option()` で保存した全キー
  - 独自テーブル：当プラグインが作成したテーブル（存在する場合のみ）
  - user_meta：当プラグインが保存したメタキー（存在する場合のみ）
  - transients / キャッシュ：当プラグイン接頭辞のキーのみ
- `wp_cache_flush()` は使用禁止。サイト全体に影響する操作は不可。

## バージョニング規約
- メインPHPのヘッダー `Version` は **コミット毎に +0.0.1** で増分。
- `readme.txt` の `Stable tag` と常に一致させる（不一致は出荷不可）。

## スラッグ変更対応
スラッグ変更時は Text Domain/メインPHPファイル名/言語フォルダ/readme を同時更新

## 原則（参考）
1) セキュリティ：入力 `sanitize_*`、出力 `esc_*`、変更系は **nonce + current_user_can()**
2) i18n：すべての文字列を `__()` 等で。**Text Domain＝プラグインスラッグ**
3) DB：`$wpdb->prepare()` 必須＋**キャッシュ**（`wp_cache_*` / Transients）
4) 構成：エントリ最小、機能は `includes/`、資産は `assets/`、翻訳は `languages/`
5) 読み込み：CSS/JSは**必要な画面だけ** enqueue
6) 更新：**独自アップデータ禁止**（更新フック横取りNG）
7) 命名：接頭辞で衝突回避（andw を使用）
8) ライセンス：**すべてGPL互換**（画像・JS・同梱物も含む）

## Plugin Directory 規約で落ちやすい点（要対応）
- 可読コード義務：難読化/名前潰しNG。ミニファイは**元ソース同梱/URL提示**  
- 有料/試用制限NG：プラグイン自体の機能ロック・期限切れは不可（SaaS連携は説明必須）  
- “なんちゃってSaaS”禁止：ライセンス検証だけの外部依存はNG  
- トラッキングは**オプトイン**＋readmeに明記  
- 外部コード配信/外部更新禁止：WordPress.org外からのコード注入/更新は不可  
- フロントの「Powered by」等は**デフォルトOFF**（ユーザーが有効化できる）  
- タグスパム禁止：readmeタグは**5個以内**、競合名や過剰キーワードNG  
- WP同梱ライブラリの利用：jQuery/PHPMailer等は**同梱しない**  
- 商標配慮：スラッグ先頭に「wordpress」や他社商標を置かない  
- 提出は**完成物のみ**。スラッグ取り置き不可

## readme.txt / ヘッダー
- `Plugin Name / Description / Version / License / Text Domain / Requires at least / Requires PHP` を整合
- `Stable tag` は `Version` と一致させる（配布運用に合わせて）

## チェック手順（推奨フロー）
1) `php -l` / 単体実行テスト  
2) PHPCS（WPCS: `WordPress`, `WordPress-Extra`）  
3) Plugin Check  
4) 目視確認：i18n、権限、キャッシュ、外部通信のオプトイン、readme掲載内容

## Done条件（WP）
- **合格ライン：エラーなし／警告は理由付きで最小限**  
- Directory規約違反がない（上記リスト参照）



## プラグインチェック作業中によく出てきた注意点。
### 出力エスケープの原則（Plugin Check対策）

- **基本ルール**
  - HTML属性 → `esc_attr()`
  - テキスト → `esc_html()`
  - URL → `esc_url()`
  - HTML断片（信頼済みHTML） → `wp_kses_post()` か `wp_kses()`
  - 翻訳出力は `esc_html__()` / `esc_attr__()` で文脈に応じてエスケープ

- **注意すべきケース**
  - `__()` や `_n()` など翻訳関数を直接 `echo` しない
  - `printf()` / `sprintf()` の引数も必ずエスケープ
  - メソッドの戻り値や外部HTMLも直接 `echo` せず `wp_kses_post()` を挟む
  - `selected()`, `checked()`, `disabled()` などWPコア関数は安全なのでOK

- **Plugin Checkでよく出るエラー**
  - `WordPress.Security.EscapeOutput.OutputNotEscaped`
    → 上記の対応で解消


### 不要なヘッダ削除ルール
- `Network:` ヘッダは `true` 以外不要。`false` はエラーになるため削除する。
- `Tested up to:` は WordPress 最新版に合わせて更新。古いままだと検索に出ない。


### 翻訳ロードの新方針
- `load_plugin_textdomain()` は不要（WP4.6+で自動ロード）。
- `languages/` ディレクトリとスラッグ命名が正しければ翻訳は自動反映。


### Plugin Check 対策の明文化
- Plugin Check は毎回リリース前に通すこと。
- エラーは必ず解消、警告は理由をコメントに明記。

### エスケープの早見表（簡易版）
- 属性 → esc_attr()
- テキスト → esc_html()
- URL → esc_url()
- HTML断片 → wp_kses_post()
- 翻訳 → esc_html__() / esc_attr__()
- WPコア関数（selected()等）は安全


## readme.txt管理のルール
- Stable tag と Version の一致を毎回確認
- Requires at least と Requires PHP も最新版に合わせる

## Plugin Checkのワークフロー明文化
- php -l → PHPCS → Plugin Check → 手動レビュー
- 審査通過に必要な「検索に出る条件」も簡単に書くと良い

## セキュリティ標準の一言メモ
- 「出力はesc_、入力はsanitize_、変更操作はnonce+権限チェック」→ ゴールデンルールを見出しで強調


## 命名・プレフィックス規約
- 4文字以上の固有接頭辞を使用すること（現行: `andw`）。3文字以下は衝突リスクが高いため禁止。
- 対象：関数、クラス、定数、グローバル変数、フック名、オプション名、`do_action`/`apply_filters`のタグ文字列。
- 予約・混同NG：`wp_` / `_` / `__` はWPコア予約なので使用禁止。
- 検証：旧接頭辞が残っていないか `grep` で確認し、`php -l` で構文確認。

## Contributors管理
- `readme.txt`の`Contributors:`にはWordPress.orgのユーザー名を列挙。
- 表示したくない場合は空でも可。
- 公開後に新しいアカウントへオーナー移管可能。

## アセット管理（WordPress.org SVN 提出時のみ）
- WordPress.org SVN の `assets/` はバナー画像・アイコン専用。プラグイン本体の `assets/`（CSS/JS）とは別物。
- SVN提出時のみ `.gitattributes` で `assets export-ignore` を設定し、バナー/アイコンをZIPから除外する。
- プラグインのランタイム資産（`assets/css/`, `assets/js/`）は配布ZIPに含めること。

## SVN構造と配布ZIP
- SVN標準：`/trunk`（本体）`/tags`（スナップショット）`/branches`。
- ZIPには`tags/`や`branches/`を含めない。
- 推奨`.gitattributes`：
  - `tags export-ignore`
  - `branches export-ignore`
  - `.vscode export-ignore`
  - `.history export-ignore`
  - `.claude export-ignore`
  - `.gitignore export-ignore`
  - `.gitattributes export-ignore`
  - `CHANGELOG.md export-ignore`
  - `TESTING.md export-ignore`
  - `DEVELOPER.md export-ignore`
  - `README.md export-ignore`
- ZIP作成：
  ```bash
  git archive --format=zip --output=../PLUGIN-SLUG.zip --prefix=PLUGIN-SLUG/ HEAD
  ```

### ドキュメント配布方針
- `docs/` 以下（含む `conversation-log/`）は配布ZIPから除外。
- `.gitattributes` 例：`docs export-ignore` / `CHANGELOG.md export-ignore`

## 翻訳ファイル（.po/.mo）
- 翻訳文字列は命名規約対象外。
- `msgid`/`msgstr`はそのままで問題なし。
- `Text Domain`はスラッグ名と一致させる。

## レビュー対応ワークフロー
- `php -l`で構文確認。
- PHPCS(WPCS)・Plugin Checkを実行。
- 旧接頭辞や非標準接頭辞が残っていないか `grep` で確認。
- ZIPに不要物が入っていないか確認。
- `readme.txt`のContributors・Stable tag・Requires(WP/PHP)整合性確認。
- 修正版ZIPをアップ後、スレッドに「反映済み」と返信。

## 連絡・オーナーシップ
- レビュー連絡は既存スレッドで返信（新規メール作成禁止）。
- メール受信問題はSPF/DKIM/DMARCと`@wordpress.org`許可設定を確認。
- スラッグは承認後変更不可。必要な場合は審査中に明示。


## 開発指針
### 1. ブロック識別子の統一（Critical）
- 指針: block.json / register_block_type() で登録したスラッグと、PHP 側の has_block() 検出ロジックを必ず一致させる。
- 注意点: リファクタ時には登録元と検出箇所を必ず同時にレビュー。

### 2. テーマ統合時のアセット読込（Critical）
- 指針: wp_enqueue_scripts 以降にテーマ関数を呼んでも必ずリソースが enqueue されるように、did_action('wp_enqueue_scripts') を確認し、必要なら強制 enqueue メソッドを用意する。

### 3. カスタムCSSのサニタイズ（Major）
- 指針: 保存時は sanitize_textarea_field() + wp_strip_all_tags() までに留める。
- 禁止事項: クォートやコロン等、CSS必須文字を削除しない。
- 補足: 出力前に safecss_filter_attr() 等で再検証を行う。

### 4. 自動挿入とショートコード重複防止（Major）
- 指針: auto_inject を実装する場合、has_shortcode() で検出し、ショートコードやブロックが存在する場合は自動挿入をスキップする。
- テスト項目: ブロック／ショートコード／自動挿入の併用時にも描画は一度だけであることを確認する。

### 5. Gutenbergコンポーネントの安定API使用（Major）
- 指針: __experimental* 系コンポーネントは採用しない。
- 対応: 後継の安定API（例: NumberControl）に随時移行する。
- 運用: WordPress更新時は @wordpress/components の破壊的変更を確認する。

### 6. アンインストール時のキャッシュ削除（Minor）
- 指針: uninstall.php では wp_cache_flush() を使用しない。
- 対応: 自プラグインの接頭辞を持つ transients / オブジェクトキャッシュのみを個別削除する。