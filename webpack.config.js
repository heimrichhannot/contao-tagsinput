const Encore = require('@symfony/webpack-encore');

Encore.configureRuntimeEnvironment('production');

Encore
    .setOutputPath('public/assets/')
    .addEntry('contao-tagsinput', './assets/js/contao-tagsinput.js')
    .addEntry('contao-tagsinput-be', './assets/js/contao-tagsinput-be.js')
    .addStyleEntry('contao-tagsinput-be-theme', './assets/css/contao-tagsinput-be.scss')
    .addStyleEntry('contao-tagsinput-be-contao4-theme', './assets/css/contao-tagsinput-be-contao4.scss')
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
