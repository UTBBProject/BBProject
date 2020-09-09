// const BundleAnalyzerPlugin = require('webpack-bundle-analyzer')
//     .BundleAnalyzerPlugin;

module.exports = {
    lintOnSave: true,
    outputDir: process.env.VUE_APP_STATIC_OUTPUT_DIR,
    configureWebpack:{
        optimization: {
            splitChunks: {
                minSize: 10000,
                maxSize: 200000,
            }
        }
    }
    // configureWebpack: {
    //     plugins: [new BundleAnalyzerPlugin()]
    // }
}
