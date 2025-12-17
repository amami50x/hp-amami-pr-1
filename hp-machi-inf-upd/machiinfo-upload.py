@app.route("/download_csv")
def download_csv():
    city = request.args.get("city")
    category = request.args.get("category")

    # セル番号の取得
    cell_number = cell_mapping[city][category]

    def generate():
        yield "市町村名,ジャンル,セル番号,場所名,説明\n"
        for row in tourism_data:
            if row["セル番号"] == cell_number:
                yield f"{row['市町村名']},{row['ジャンル']},{row['セル番号']},{row['場所名']},{row['説明']}\n"

    return Response(generate(), mimetype="text/csv", headers={"Content-Disposition": "attachment; filename=data.csv"})