# WordPress Plugin Review (Codex)

対象:
- `andw-thumbnail-column.php`
- `uninstall.php`

## Findings

### andw-thumbnail-column.php

1. **重要度: MEDIUM** 取得時のオプション値が再検証されていない
- 箇所: `andw-thumbnail-column.php:57-63`, `andw-thumbnail-column.php:96-101`
- 詳細: `get_enabled_post_types()` は「配列であること」しか確認せず返却しています。DB上の値が直接改変されると、想定外の文字列が `manage_{$post_type}_posts_columns` などの動的フック名に混入します。
- 影響: 直ちにRCEになる類ではありませんが、予期しないフック登録・保守性低下・デバッグ困難化を招きます。
- 推奨: 読み出し時にも `sanitize_key` と `get_post_types( [ 'show_ui' => true ] )` でホワイトリスト検証を実施し、`sanitize_post_types()` と同等の整合性を保証してください。

2. **重要度: LOW** Settings API の `do_settings_sections()` が実質未使用
- 箇所: `andw-thumbnail-column.php:193`
- 詳細: `add_settings_section()` / `add_settings_field()` が登録されていないため、`do_settings_sections( 'andw-thumbnail-column' )` は何も表示しません。
- 影響: 動作不良はありませんが、Settings API 利用方針が混在しており将来の拡張時に混乱を招きます。
- 推奨: 今の実装方針を維持するなら `do_settings_sections()` を外すか、完全に Settings API へ寄せるなら section/field 登録を追加してください。

3. **重要度: LOW** 一覧表示時の `file_exists()` 呼び出しが投稿行ごとに発生する
- 箇所: `andw-thumbnail-column.php:128-133`
- 詳細: 投稿一覧の各行でファイルシステムI/Oが発生します。件数が多い環境では管理画面のレスポンスに影響する可能性があります。
- 影響: 主に性能面のリスク（機能上は問題なし）。
- 推奨: `get_the_post_thumbnail()` の結果をそのまま使う、または必要時のみ軽量チェックにするなどでI/Oを減らしてください。

### uninstall.php

1. **重要度: MEDIUM** マルチサイト環境での削除範囲が不十分
- 箇所: `uninstall.php:14`
- 詳細: 現状は `delete_option()` のみで、単一サイトのオプション削除しか考慮していません。ネットワーク有効化/複数ブログ運用時に、他ブログ側の設定が残留する可能性があります。
- 影響: アンインストール後の設定残存（データクリーンアップ不備）。
- 推奨: `is_multisite()` 時に全ブログを走査して `delete_option()` する、または保存戦略に応じて `delete_site_option()` を併用してください。

## Good Points

### andw-thumbnail-column.php
- `ABSPATH` ガードがあり、直接アクセス対策が入っています（`andw-thumbnail-column.php:18-20`）。
- 設定画面は `manage_options` 権限を確認しており、権限制御が明確です（`andw-thumbnail-column.php:182-184`）。
- `settings_fields()` を使って nonce を含む標準フローを利用しており、CSRF対策は Settings API 側で担保されています（`andw-thumbnail-column.php:192`）。
- 出力時の `esc_html` / `esc_attr`、サムネイルHTMLの `wp_kses_post` が適切です（`andw-thumbnail-column.php:132,190,205-206,209`）。
- `register_setting()` に `sanitize_callback` が設定され、入力サニタイズ方針が明確です（`andw-thumbnail-column.php:82-89`）。
- `in_array(..., true)` を利用して厳密比較しており、型安全性への配慮があります（`andw-thumbnail-column.php:207`）。

### uninstall.php
- `WP_UNINSTALL_PLUGIN` チェックがあり、意図しない直接実行を防いでいます（`uninstall.php:10-12`）。
- 削除対象を明示的に1つに絞っており、処理が簡潔で副作用が小さいです（`uninstall.php:14`）。

## Overall
- 重大な脆弱性（HIGH）は見当たりません。
- 改善優先度は、1) 読み出し時の再サニタイズ、2) マルチサイト時のアンインストール完全性、の順が妥当です。
