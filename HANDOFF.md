# HANDOFF — andw-thumbnail-column

## 現在地

**ワークフローフェーズ: C 完了 → D（実装）待ち**

Phase C（実装準備）完了。Phase 詳細ファイル4つと SONNET-PROMPT.md を作成済み。
Codex 用プロンプトが準備できた。`/codex` スキルで実装を開始できる。

## 完了済み

- Phase A: SPEC.md 確認、PHASE-PLAN.md 初版作成
- Phase B: ペルソナレビュー（5並列）→ 統合 → PHASE-PLAN.md 改訂
- Phase C: Phase 詳細ファイル作成 + SONNET-PROMPT.md 作成
  - `docs/phases/phase-1-skeleton.md` — プラグイン骨格
  - `docs/phases/phase-2-settings.md` — 設定画面
  - `docs/phases/phase-3-column.md` — サムネイル列追加
  - `docs/phases/phase-4-uninstall.md` — アンインストール処理
  - `docs/SONNET-PROMPT.md` — Codex 用実装指示

## テンプレート未置換の修正（Phase B で実施済み）

- `phpcs.xml.dist`: テキストドメイン → `andw-thumbnail-column`、プレフィックス → `andw_tc_`、WPバージョン → 6.0
- `composer.json`: name → `andw/andw-thumbnail-column`
- `phpstan.neon`: `node_modules (?)` → `node_modules`

## 次にやること

- Phase D: `/codex` スキルで Codex に実装を投げる
- Codex は `docs/SONNET-PROMPT.md` を起点に Phase 1〜4 を順次実装
- 実装完了後 Phase E（コードレビュー）へ

## 注意点

- Opus はコーディング禁止（Phase E' の直接修正ルール特例を除く）
- `manage_posts_columns` は使わない → `manage_{$post_type}_posts_columns` で動的登録
- Settings API の全チェックOFF対応を忘れずに（空配列ハンドリング）
