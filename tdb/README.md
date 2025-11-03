# TDB Directory - CSV Configuration Files

このディレクトリには、システムの設定データを格納するCSVファイルが含まれています。

## AFAD連携設定

### afad_config.csv

AFAD連携機能の設定ファイルです。広告ごとのAFAD設定を管理します。

**注意:** このファイルは`.gitignore`に含まれており、バージョン管理されません。
各環境ごとに手動で作成する必要があります。

### ファイルフォーマット

```csv
adwares_id,enabled,parameter_name,postback_url,group_id,send_uid,send_uid2,send_amount,approval_status,timeout_seconds,retry_max,url_passthrough,cookie_expire_days,created_at,updated_at,deleted_at
```

### サンプルデータ

```csv
123,true,afad_sid,https://ac.afad.jp/12345/ac/,GRP001,true,false,true,1,10,3,true,30,2025-11-02 00:00:00,2025-11-02 00:00:00,
```

### カラム説明

| カラム名 | 型 | 説明 |
|---------|-----|------|
| adwares_id | int | 広告ID |
| enabled | boolean | AFAD連携有効フラグ(true/false) |
| parameter_name | string | セッションID受け取り用パラメータ名 |
| postback_url | string | AFADポストバックURL |
| group_id | string | AFAD広告グループID |
| send_uid | boolean | uid(注文番号等)を送信するか |
| send_uid2 | boolean | uid2を送信するか |
| send_amount | boolean | 成果金額を送信するか |
| approval_status | int | 送信する承認ステータス(1:承認待ち, 2:承認, 3:否認) |
| timeout_seconds | int | HTTPリクエストタイムアウト(秒) |
| retry_max | int | リトライ最大回数 |
| url_passthrough | boolean | リダイレクト先URLにもセッションIDを渡すか |
| cookie_expire_days | int | Cookie有効期限(日数) |
| created_at | datetime | 作成日時 |
| updated_at | datetime | 更新日時 |
| deleted_at | datetime | 削除日時（論理削除） |

## 重要な注意事項

1. **データベースとの併用**
   - このCSV設定ファイルは、Supabaseデータベースが利用できない場合のフォールバックとして使用されます
   - 本番環境では、Supabaseの`afad_configs`テーブルを使用することを強く推奨します
   - CSVはテスト環境やローカル開発環境での利用を想定しています

2. **セキュリティ**
   - このファイルには機密情報（AFAD API URL等）が含まれる可能性があります
   - `.gitignore`に追加されていることを確認してください
   - バージョン管理システムにコミットしないでください

3. **バックアップ**
   - 本番環境では、このファイルを定期的にバックアップしてください
   - 変更前に必ずバックアップを取ってください

4. **ファイル権限**
   ```bash
   chmod 644 tdb/afad_config.csv
   chown www-data:www-data tdb/afad_config.csv
   ```

## 参照ドキュメント

- [AFAD連携設計書](../docs/AFAD_SOCKET_INTEGRATION_DESIGN.md)
- [デプロイメントチェックリスト](../docs/AFAD_DEPLOYMENT_CHECKLIST.md)
- [Supabaseデータベース設計](../database/supabase/DESIGN.md)
