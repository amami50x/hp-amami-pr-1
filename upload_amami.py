import ftplib
import os
import logging
import traceback

logging.basicConfig(filename='upload_error_log.txt', level=logging.DEBUG)

try:
    ftp = ftplib.FTP('violetfoal2.sakura.ne.jp')

    # デバッグレベルを設定
    ftp.set_debuglevel(2)  

    ftp.login('violetfoal2', 'wAHabRb3qLA.')
    print("FTP接続成功！")

    ftp.set_pasv(True)
    ftp.cwd('www/hp-amami-pr-1')
    print("正しいサーバーフォルダに移動しました。")

    # amami.htmlをアップロード
    with open('amami.html', 'rb') as f:
        ftp.storbinary('STOR amami.html', f)
        print("amami.html をアップロードしました！")

    ftp.quit()
    print("FTPセッションを終了しました。")
    print("アップロード完了！さくらサーバーをテストしてください。")

except ftplib.all_errors as e:
    print(f"FTPエラーが発生しました: {e}")
    logging.error(f"FTPエラー: {e}")
    traceback.print_exc()

except Exception as e:
    print(f"予期しないエラーが発生しました: {e}")
    logging.error(f"予期しないエラー: {e}")
    traceback.print_exc()

input("処理が完了しました。Enterを押して終了します。")
