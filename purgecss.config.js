module.exports = {
  content: [
    './index.html', // HTMLファイル
    './src/**/*.js', // JavaScriptファイル
    './src/**/*.jsx', // ReactのJSXファイル (使っていれば)
    './src/**/*.ts',  // TypeScriptファイル (使っていれば)
    './src/**/*.tsx'  // TypeScriptのJSXファイル (使っていれば)
  ],
  css: ['./src/styles/*.css'], // 使っているCSSファイルのパス
  output: './dist/styles', // 出力先ディレクトリ
};
