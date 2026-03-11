# Claude Code コマンド一覧

> 更新日: 2026-03-11
> 配置場所: `~/.claude/commands/`（グローバル。全プロジェクト共通）

---

## モデル使い分けガイド

コマンドによって求められる判断の深さが異なる。適切なモデルで実行すること。

| 推奨モデル | 理由 | 対象コマンド |
|-----------|------|------------|
| **Opus** | コード解析の深さが品質に直結 | `/estimate`, `/spec-doc`, `/user-guide`, `/contract-draft` |
| **Sonnet + Opus** | Sonnet がペルソナレビュー、Opus が統合・判定 | `/review-code`（Phase E）, `/review-design`（Phase B） |
| **Sonnet** | 中程度の判断力で十分 | `/acceptance-checklist`, `/test-report`, `/spec-prep` |
| **Haiku** | 会話の要約がメインで判断が少ない | `/meeting-notes`, `/change-request`, `/blog-*` |

---

## 仕様整備系

| コマンド | 説明 | 出力先 | モデル |
|---------|------|--------|-------|
| `/spec-prep` | スマホClaudeから持ち込んだ仕様メモを、要求定義＋要件定義の正式な仕様書に清書 | `docs/SPEC.md` | Sonnet |

---

## 納品ドキュメント系

| コマンド | 説明 | 出力先 | モデル |
|---------|------|--------|-------|
| `/estimate` | 開発費用の見積もりを作成。企画段階・計画段階・完成後の3モード対応 | `docs/deliverables/ESTIMATE-{DRAFT,PLAN,FINAL}.md` | Opus |
| `/acceptance-checklist` | 検収チェックリスト兼簡易マニュアルを生成。PHASE-PLAN・コード・Issuesから自動構築 | `docs/deliverables/ACCEPTANCE-CHECKLIST.md` | Sonnet |
| `/contract-draft` | 業務委託契約書のたたき台を生成。見積書・PHASE-PLANから案件情報を自動反映 | `docs/deliverables/CONTRACT-DRAFT.md` | Opus |
| `/meeting-notes` | 会話ログや打ち合わせ内容から議事録（決定事項+宿題）を生成 | `docs/deliverables/MEETING-NOTES-YYYY-MM-DD.md` | Haiku |
| `/change-request` | 仕様変更・追加要望シートを生成。GitHub Issuesや会話ログから自動収集 | `docs/deliverables/CHANGE-REQUESTS.md` | Haiku |
| `/spec-doc` | 要件定義書を生成。PHASE-PLAN・仕様メモ・コードから自動構築 | `docs/deliverables/SPEC-DOC.md` | Opus |
| `/test-report` | テスト結果報告書を生成。TESTING.md・テスト実行結果・GitHub Issuesから自動構築 | `docs/deliverables/TEST-REPORT.md` | Sonnet |
| `/user-guide` | 詳細操作マニュアルを生成。管理画面コード・設定画面から自動構築 | `docs/deliverables/USER-GUIDE.md` | Opus |

---

## レビュー・品質管理系

| コマンド | 説明 | 対応フェーズ |
|---------|------|------------|
| `/review-code` | Sonnet 5並列（beginner/crawler/security/wp/breaker）→ Opus統合・判定 | Phase E |
| `/review-design` | Sonnet 5人並列で設計レビュー、Opus が統合 | Phase B |
| `/create-test-issues` | テストIssueを管理画面用・フロント用に分けてGitHubに作成 | Phase F |

---

## ブログ記事系

| コマンド | 説明 |
|---------|------|
| `/blog-log` | 現在の会話を生のログとして漏らさず保存 |
| `/blog-draft` | 保存済みの生ログからブログ記事の草稿を作成 |
| `/blog-yasuo` | 草稿と生ログから、やすおさんの声で記事を書き直す |
| `/blog-netservice` | やすお化した記事をnetservice.jp向けの技術記録記事に書き直す |
| `/blog-ai-counselor` | やすおの困りごとをAIが上から目線で解決する「AIお悩み相談所」記事を書く |
| `/blog-all` | blog-log → blog-draft → blog-yasuo を確認なしで一気に実行 |

---

## 運用系

| コマンド | 説明 |
|---------|------|
| `/sync-template` | テンプレート（andw-template）との同期。Git管理から外すべきファイルのクリーンアップも実行 |

---

## 操作小技（キーボードショートカット）

| キー | 効果 |
|------|------|
| **Tab** | 許可ダイアログで Yes/No ではなく追加指示を入力できる。「この部分だけ変えて」等の微調整に便利 |
| **Ctrl+B** | 長い処理（テスト、lint等）をバックグラウンドに送り、メインの会話を継続できる |
| **Shift+Tab** | Plan モードの切り替え。調査→計画フェーズでコンテキスト消費を抑えたい時に使う |
