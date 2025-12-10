// eslint.config.js
import angular from '@angular-eslint/eslint-plugin';
import angularTemplate from '@angular-eslint/eslint-plugin-template';
import templateParser from '@angular-eslint/template-parser';
import typescript from '@typescript-eslint/eslint-plugin';
import typescriptParser from '@typescript-eslint/parser';
import importPlugin from 'eslint-plugin-import';
import jsdoc from 'eslint-plugin-jsdoc';
import preferArrow from 'eslint-plugin-prefer-arrow';

export default [
  {
    files: ['**/*.ts'],
    languageOptions: {
      parser: typescriptParser,
      parserOptions: {
        project: ['./tsconfig.app.json', './tsconfig.spec.json'],
        createDefaultProgram: true,
      },
    },
    plugins: {
      '@angular-eslint': angular,
      '@typescript-eslint': typescript,
      import: importPlugin,
      jsdoc: jsdoc,
      'prefer-arrow': preferArrow,
    },
    rules: {
      // Angular specific rules
      '@angular-eslint/component-class-suffix': 'error',
      '@angular-eslint/directive-class-suffix': 'error',
      '@angular-eslint/no-conflicting-lifecycle': 'error',
      '@angular-eslint/no-input-rename': 'error',
      '@angular-eslint/no-output-native': 'error',
      '@angular-eslint/no-output-on-prefix': 'error',
      '@angular-eslint/no-output-rename': 'error',
      '@angular-eslint/use-lifecycle-interface': 'error',
      '@angular-eslint/use-pipe-transform-interface': 'error',

      // TypeScript rules
      ...typescript.configs.recommended.rules,

      // Import rules
      'import/namespace': 'error',
      'import/default': 'error',
      'import/export': 'error',

      // JSDoc rules
      'jsdoc/check-alignment': 'error',
      'jsdoc/check-indentation': 'error',
      'jsdoc/multiline-blocks': 'error',

      // Prefer arrow functions
      'prefer-arrow/prefer-arrow-functions': [
        'error',
        {
          disallowPrototype: true,
          singleReturnOnly: false,
          classPropertiesAllowed: false,
        },
      ],
    },
  },
  {
    files: ['**/*.html'],
    plugins: {
      '@angular-eslint/template': angularTemplate,
    },
    languageOptions: {
      parser: templateParser,
    },
    rules: {
      ...angularTemplate.configs.recommended.rules,
    },
  },
  {
    files: ['**/testing/**/*.ts'],
    rules: {
      '@typescript-eslint/no-explicit-any': 'off',
      'prefer-arrow/prefer-arrow-functions': 'off',
    },
  },
  {
    ignores: ['projects/**/*'],
  },
];
