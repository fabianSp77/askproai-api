/** @type {import("eslint").Linter.Config} */
module.exports = {
  extends: ['./base.js'],
  env: {
    node: true,
  },
  rules: {
    'no-console': 'error',
  },
  overrides: [
    {
      files: ['*.ts', '*.tsx'],
      parserOptions: {
        project: './tsconfig.json',
      },
    },
  ],
}