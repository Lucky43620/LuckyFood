import js from '@eslint/js'
import vue from 'eslint-plugin-vue'

const browserGlobals = {
    clearTimeout: 'readonly',
    confirm: 'readonly',
    document: 'readonly',
    FileReader: 'readonly',
    axios: 'readonly',
    navigator: 'readonly',
    route: 'readonly',
    setTimeout: 'readonly',
    window: 'readonly',
}

export default [
    {
        ignores: ['vendor/**', 'node_modules/**', 'public/build/**', 'storage/**', 'bootstrap/cache/**'],
    },
    js.configs.recommended,
    ...vue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{js,vue}', 'tests/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: browserGlobals,
        },
        rules: {
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            'vue/attributes-order': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/html-indent': 'off',
            'vue/html-self-closing': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'off',
            'vue/require-default-prop': 'off',
            'vue/require-prop-types': 'off',
            'vue/singleline-html-element-content-newline': 'off',
        },
    },
]
