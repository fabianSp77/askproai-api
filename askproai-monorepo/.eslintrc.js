module.exports = {
  root: true,
  extends: ["@askproai/config/eslint/base.js"],
  settings: {
    next: {
      rootDir: ["apps/*/"],
    },
  },
}