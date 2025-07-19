const mix = require('laravel-mix');
const TerserPlugin = require('terser-webpack-plugin');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// 我们将创建一个新的JS文件来存放敏感的加密逻辑
mix.js('resources/js/auth/signin.js', 'public/js/auth')
    .webpackConfig({
        optimization: {
            minimize: true,
            minimizer: [new TerserPlugin({
                terserOptions: {
                    mangle: true, // 开启变量名混淆
                    compress: {
                        drop_console: true, // 移除 console.log
                    },
                    output: {
                        comments: false, // 移除所有注释
                    },
                },
                extractComments: false,
            })],
        },
    });

// 如果你还有其他CSS/JS需要处理，可以在这里添加
// mix.postCss('resources/css/app.css', 'public/css', [
//     //
// ]); 