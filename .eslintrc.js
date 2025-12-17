module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  extends: [
    'eslint:recommended', // ESLint の推奨ルールを有効にする
    // 必要に応じて、より厳格なスタイルガイドを追加できます
    // 例: 'airbnb-base', 'plugin:prettier/recommended'
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module', // ES Modules を使用している場合
  },
  rules: {
    // カスタムルールを追加できます
    'no-unused-vars': 'warn', // 未使用変数を警告
    'no-console': 'warn', // console.log を警告
    'semi': ['error', 'always'], // セミコロンを強制
    'quotes': ['error', 'single'], // シングルクォートを強制
    'no-undef': 'error', // 定義されていない変数をエラーにする
    // 必要に応じて他のルールを追加・変更
  },
};