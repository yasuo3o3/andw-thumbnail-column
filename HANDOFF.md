# HANDOFF — andw-thumbnail-column

## 現在地

**ワークフローフェーズ: C（実装準備）**

Phase B（ペルソナレビュー）完了。5ペルソナの指摘を統合し PHASE-PLAN.md を改訂済み。
これから Phase C（実装準備: Phase 詳細ファイル + Codex 用プロンプト作成）に進む。

## 完了済み

- Phase A: SPEC.md 確認、PHASE-PLAN.md 初版作成
- Phase B: ペルソナレビュー（5並列）→ 統合 → PHASE-PLAN.md 改訂
  - BUG 3件、FIX 6件、IMP 5件 → すべて PHASE-PLAN.md に反映済み
  - テンプレート未置換修正: phpcs.xml.dist, composer.json, phpstan.neon

## Phase B で反映した主な変更

- `manage_posts_columns` → `manage_{$post_type}_posts_columns` 動的登録に一本化
- Phase 構成を 5→4 に再編（設定画面を列表示より先に）
- セキュリティ要件セクションを新設（nonce, 権限, サニタイズ, エスケープ, ABSPATH）
- 全チェックOFF時の空配列ハンドリング追加
- CSS 出力を `wp_add_inline_style()` に明記
- 画像ファイル消失時のフォールバック定義

## 次にやること

- Phase C: Phase 詳細ファイル作成 + SONNET-PROMPT.md 作成
- Notion ガントチャート初期登録

## 注意点

- Opus はコーディング禁止。設計・レビュー・プロンプト作成のみ
- SPEC.md の minimum_wp_version は 6.0（phpcs.xml.dist も 6.0 に修正済み）
