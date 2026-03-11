# WordPress プラグイン開発 - AI コーディング再発防止パターン集

AI（Sonnet等）にコーディングを任せた際に実際に発生したバグと、正しいパターンをまとめる。

---

## 1. admin_enqueue_scripts の hook suffix 問題

### 症状
- 管理画面のカスタムページでJS/CSSが読み込まれない
- `admin_enqueue_scripts` のコールバックで `$hook_suffix` をハードコードしているのが原因

### なぜ起こるか
- hook suffix は `add_submenu_page()` の親slug・ページslugの組み合わせで自動生成される
- 形式: `{親slug}_page_{ページslug}` だが、親slugに特殊文字があると変わる
- CF7の場合: 親が `wpcf7` なので `contact_page_xxx` になるが、これは推測に過ぎない
- AIモデルはCodexの一般例から推測してハードコードしがち

### 正しいパターン
```php
function my_enqueue_admin_assets( $hook_suffix ) {
    // 自分のカスタムページは $_GET['page'] で判定（確実）
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'my-plugin-page' !== $page ) {
        return;
    }
    // ... enqueue ...
}
```

### 補足
- CF7本体のページ（`toplevel_page_wpcf7`, `contact_page_wpcf7-new`）は hook suffix で判定してよい（CF7が定義するため安定）
- 自分で追加したサブメニューページは `$_GET['page']` が確実

---

## 2. CF7 v2 タグジェネレーター - DOM タイミング問題

### 症状
- タグジェネレーターのパラメータ入力を変更しても `.tag.code` 入力が更新されない
- Insert Tag ボタンを押しても何も挿入されない
- `.tag.code` readonly入力が空のまま表示される（ユーザーには「謎のインプット」に見える）

### なぜ起こるか
- CF7 v2（`'version' => '2'`）のタグジェネレーターはページ読み込み時にHTMLを出力する
- v1は動的にDOMに挿入していた（ボタンクリック時）
- AIは「動的追加されるはず」と仮定して MutationObserver を使うが、パネルは最初からDOMにある
- MutationObserverが一度も発火しない → 初期化されない → tag codeが空

### 正しいパターン
```javascript
// 委譲イベントリスナー（DOMのタイミングに依存しない）
$(document).on('input change', '.my-tg-param', function () {
    var $box = $(this).closest('.control-box');
    refreshTagCode($box);
});

// ページ読み込み時に既存パネルも初期化
$(document).ready(function () {
    $('.control-box.my-tag-generator').each(function () {
        refreshTagCode($(this));
    });
});
```

### HTML構造（CF7 v2）
```html
<div class="control-box my-tag-generator">  <!-- パラメータ入力 -->
    <input class="my-tg-param" data-param="group" ...>
</div>
<div class="insert-box">                    <!-- 出力 + Insert Tag -->
    <input type="text" name="tag-name" class="tag code" readonly>
    <input type="button" class="button button-primary insert-tag" value="Insert Tag">
</div>
```
- `.control-box` と `.insert-box` は兄弟要素
- CF7の Insert Tag ボタンは `.tag.code` の value をエディタに挿入する

---

## 3. uninstall.php のオプション名不一致

### 症状
- プラグインをアンインストールしてもデータが残る

### なぜ起こるか
- 開発途中でオプション名を変更した場合、uninstall.php の更新を忘れる
- AIが仮のオプション名で uninstall.php を書き、後でオプション名を変更した場合に不一致が残る

### チェックリスト
- `register_setting()` / `add_option()` / `update_option()` で使うキーと uninstall.php の `delete_option()` が一致しているか
- Settings APIの group name（`register_setting` の第1引数）はオプションではないので `delete_option` 不要

---

## 4. AI にコーディングを任せる際の一般注意

- WordPress固有のフック/フィルター名の正確さを必ず検証する
- CF7など外部プラグインのAPI仕様はドキュメントが少ないため、AIの推測が外れやすい
- 生成されたコードは「構文的に正しいが環境依存で動かない」パターンに注意
- 管理画面のアセット読み込みは実際のページでブラウザ開発者ツール（F12 → Network）で確認するのが最速

---

## 5. 本番サーバー・DB操作の副作用（シンVPS）

### 症状
- `ALTER USER` でパスワードを再設定 → ハッシュ形式が md5→SCRAM-SHA-256 に変化
- `pg_hba.conf` が md5 のまま → n8nからDB接続不可に

### なぜ起こるか
- AIは「同じパスワードの再設定は無害」と判断する
- しかし PostgreSQL の `password_encryption` 設定次第でハッシュ形式が変わる
- 1台のVPSに複数サービス（n8n, PostgreSQL, nginx等）が同居しており、1つの変更が他サービスに波及する

### ルール
1. **`ALTER USER` / `DROP` / `GRANT`（既存ユーザー変更）は実行前にオーナーに確認必須**
2. **新規テーブル・新規ユーザーの作成は安全**（既存に影響しない）
3. **判断に迷ったら「このDBユーザーを他のサービスが使っていないか？」をオーナーに確認する**

### 発生日
2026-03-06: Sonnet が n8n_user のパスワードを ALTER USER で再設定 → SCRAM-SHA-256 に変化 → n8n接続断
