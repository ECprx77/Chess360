module.exports = {
  presets: [
    '@vue/cli-plugin-babel/preset',
    ['@babel/preset-env', { modules: false }]
  ],
  plugins: [
    ['@vue/babel-plugin-jsx', { optimize: false }],
    ['@babel/plugin-transform-runtime', { regenerator: true }]
  ]
}
