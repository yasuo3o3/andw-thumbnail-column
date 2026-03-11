# Codex テスト記録 — andw-thumbnail-column

このファイルは Codex（gpt-5.3-codex）の動作テストの記録。
うまくいったこと・いかなかったことを都度記録し、ワークフロー改善に活かす。

## テスト環境
- モデル: gpt-5.3-codex
- ツール: codex-cli 0.114.0
- 呼び出し方法: `/codex` スキル（Claude Code 経由）

---

## 記録フォーマット

```
### [日付] タスク名
- **指示内容**: Codexに投げたプロンプト
- **結果**: OK / NG / 部分的
- **良かった点**:
- **問題点**:
- **対処**:
```

---

## 記録

### 2026-03-11 Phase 1 実装（初回テスト）
- **指示内容**: `codex exec --full-auto -m gpt-5.3-codex "composer install → phase-1-skeleton.md を読んで実装 → php -l → git commit"`
- **結果**: NG
- **良かった点**: ドキュメントの読み込み・理解は正確。タスク分解も適切だった
- **問題点**:
  - `--full-auto` + `sandbox: read-only` でファイル書き込みがすべてブロックされた
  - `composer install`、ファイル作成（apply_patch）、`php -l`、`git commit` すべて `rejected: blocked by policy`
  - Codex は `--full-auto` でも実質 read-only になっている模様
- **対処**: `--full-auto` を外して対話モードで実行するか、Sonnet に切り替えて実装する

### 2026-03-11 Phase 1〜4 実装（sandbox 設定変更後）
- **指示内容**: `codex exec -s danger-full-access -m gpt-5.3-codex "docs/phases/phase-N-xxx.md を読んで実装して..."` を Phase ごとに4回実行
- **結果**: OK（全4 Phase 成功）
- **良かった点**:
  - `-s danger-full-access` で書き込み制限が解除された
  - Phase 詳細ファイルの骨格コードをほぼそのまま正確に再現
  - `apply_patch` によるファイル作成・編集が安定動作
  - `php -l` 構文チェックも全 Phase パス
  - コミットメッセージ・対象ファイルの指示を忠実に実行
- **問題点**:
  - `-s workspace-write` が効かなかった（表示は常に `read-only`）。原因不明、Windows環境の制約の可能性
  - `composer install` は `danger-full-access` でもブロックされた（Phase 1 のみ。影響は軽微）
  - Codex がファイル内容を PowerShell の here-string で書き出す際、シングルクォートのエスケープが不安定（Phase 1）。`apply_patch` 方式の方が安定（Phase 2〜4）
- **対処**:
  - `/codex` スキルの `--full-auto` を `-s danger-full-access` に変更すべき
  - `composer install` は事前に手動で実行しておく運用にする
- **トークン消費**: Phase 1: 5,811 / Phase 2: 11,107 / Phase 3: 12,272 / Phase 4: 10,502（合計約4万トークン）
