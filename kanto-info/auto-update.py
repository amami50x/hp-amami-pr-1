import time
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
import openpyxl

class ExcelHandler(FileSystemEventHandler):
    def on_modified(self, event):
        if event.src_path.endswith(".xlsx"):
            print(f"Excelファイル更新: {event.src_path}")
            # Excelファイルを処理してHTMLを更新
            create_html_from_excel(event.src_path)

def create_html_from_excel(excel_path):
    wb = openpyxl.load_workbook(excel_path)
    sheet = wb.active
    html = '''
    <html>
    <head><title>ショップ一覧</title></head>
    <body><table border="1">
    '''
    for row in sheet.iter_rows(min_row=2, values_only=True):
        html += "<tr>" + "".join(f"<td>{cell}</td>" for cell in row) + "</tr>"
    html += '</table></body></html>'
    
    output_path = excel_path.replace(".xlsx", ".html")
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(html)
    print(f"{output_path} が更新されました")

if __name__ == "__main__":
    path_to_watch = "."  # フォルダ監視場所
    event_handler = ExcelHandler()
    observer = Observer()
    observer.schedule(event_handler, path=path_to_watch, recursive=True)
    observer.start()
    print("監視開始")
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
