import time
import subprocess
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler

WATCH_FILE = 'shops.xlsx'

class ExcelChangeHandler(FileSystemEventHandler):
    def on_modified(self, event):
        if event.src_path.endswith(WATCH_FILE):
            print(f"{WATCH_FILE} が更新されました。処理を開始します。")
            try:
                # HTML作成
                subprocess.run(["python", "shop-html.py"], check=True)
                print("HTML生成が完了しました。")

                # HTMLアップロード
                subprocess.run(["python", "upload_html.py"], check=True)
                print("アップロードが完了しました。")

            except subprocess.CalledProcessError as e:
                print(f"エラーが発生しました: {e}")

if __name__ == "__main__":
    event_handler = ExcelChangeHandler()
    observer = Observer()
    observer.schedule(event_handler, path='.', recursive=False)
    observer.start()
    print("shops.xlsxの変更を監視中です...（終了するには Ctrl+C を押してください）")

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
