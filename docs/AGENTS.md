# AGENTS.md — 共通運用ガイド (Unified v4)

このリポジトリで動作する **すべてのAIエージェント（Gemini, Claude, etc.）** のための共通行動規範。
プロジェクト固有の仕様や規約は `WORDPRESS.md` や `CONTRIB.md` に分離されている。

## 最上位規範
**WORDPRESS.md が最上位規範** である。
本書（AGENTS.md）や Agent Skills の内容と矛盾する場合は、常に `WORDPRESS.md` を優先する。

## ドキュメント読み込み優先順位
1. **WORDPRESS.md** (Governance): プロジェクトの絶対的な規約・禁止事項。
2. **AGENTS.md** (Methodology): 本書。AIの振る舞い、手順、スキルの利用方法。
3. **CONTRIB.md** (Context): 担当者や環境固有のルール。

---

## 1. Agent Skills の活用規約（新規）
実装作業を行う際は、必ず以下の手順で「WordPress Agent Skills」を参照し、ベストプラクティスを遵守すること。

1. **スキル確認**: `docs/external/agent-skills/skills/` ディレクトリを確認する。
2. **SKILL読込**: 作業内容に関連するスキルがある場合、そのフォルダ内の `SKILL.md` を読み込む。
   - プラグイン開発全般 → `wp-plugin-development`
   - ブロック開発 → `wp-block-development`
   - API実装 → `wp-rest-api` / `wp-interactivity-api`
   - パフォーマンス → `wp-performance`
3. **優先順位**: スキルの内容が `WORDPRESS.md` と矛盾する場合のみ、`WORDPRESS.md` を優先する。それ以外はスキルの手順に従う。

---

## 2. ワークフロー
1. **Plan**: 課題を整理。`WORDPRESS.md` / `CONTRIB.md` を確認。
2. **Skill Check**: 関連する Agent Skill (`docs/external/agent-skills/skills/*/SKILL.md`) を読む。
3. **Verify**: 前提条件や制約を確認。
4. **Implement**: 小ステップで実装。安全優先。
5. **Test**: 構文チェック (`php -l`)・Plugin Check・ドキュメント更新。
6. **Log**: 作業後に Implementation Log または Conversation Log を出力。

---

## 3. 会話・出力ルール
- **言語**: 日本語（コード内のコメントも日本語）。
- **順序**: 「背景 → 変更点 → 最小 git diff → 実行/確認手順 → 想定コミット文」
- **明示**: 具体的なファイルパスと変更箇所を省略せずに書く。
- **選択肢**: 特に指示がなければ「1. Yes」を推奨（連続作業許可）。

## 4. 承認と安全
- **破壊的操作**: 削除、初期化、force-push 等は **必ず1回確認** を挟む。
- **モード**: Agentic Mode（自律実行）を推奨。

## 5. コーディング共通
- **静的チェック**: `php -l` 等を必ず通す。
- **依存性**: 追加は最小限に。
- **コミット**: 短文・1コミット1意図・日本語。「何を／なぜ」を明確に。

---

## 6. Conversation Log（日次）
- 場所：`/docs/conversation-log/YYYY-MM-DD.md`
- 内容：**User Request** (依頼内容) と **Final Answer** (最終結果/報告) のみ。
- 除外：途中経過の思考やコードは記録しない。配布ZIPには含めない。

## 7. 引き継ぎ
- `HANDOFF.md`（ルート）が存在する場合は読み込み、前回の状況を把握する。
- セッション終了時は `HANDOFF.md` を更新する（現在地・次にやること・注意点）。

## 8. 作業停止ゲート (Stop Gates)
以下に該当する場合は直ちに作業を停止し、オーナーに確認すること：
- Text Domain ≠ スラッグ
- Contributors に申請アカウント名が無い
- `<script>/<style>` 直書き、コア直読み、`$_POST` 全量処理の指示
- `WORDPRESS.md` の禁止事項に触れる指示
- **本番DBの既存ユーザー・権限・設定の変更**（`ALTER USER`, `DROP`, `pg_hba.conf` 編集等）
- **本番サーバーのサービス設定変更**（nginx, PostgreSQL, systemd の設定ファイル編集等）
- **ワークフロー GATE 違反**: `docs/WORKFLOW.md` で定義された GATE を超えてコーディングに進もうとする場合。オーナーの「OK」はフェーズ遷移の承認であり、コーディング開始の許可ではない

## 9. Done条件
- 仕様を満たし、静的チェックを通過していること。
- `WORDPRESS.md` の規約に違反していないこと。
- 関連する Agent Skill の手順に沿っていること。
