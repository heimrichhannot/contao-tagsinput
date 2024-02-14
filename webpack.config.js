const Encore = require('@symfony/webpack-encore');

Encore.configureRuntimeEnvironment('production');

Encore
    .setOutputPath('public/assets/')
    .addEntry('contao-tagsinput-be', './assets/js/contao-tagsinput-be.js')
    .addEntry('contao-tagsinput-be-contao4', './assets/js/contao-tagsinput-be-contao4.js')
    .setPublicPath('/bundles/heimrichhannottagsinput/assets')
    .setManifestKeyPrefix('bundles/heimrichhannottagsinput/assets')
    .enableSassLoader()
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .addExternals({
        'es7-object-polyfill': 'es7-object-polyfill',
        'custom-event-polyfill': 'custom-event-polyfill',
        'nodelist-foreach-polyfill': 'nodelist-foreach-polyfill'
    })
;

module.exports = Encore.getWebpackConfig();
