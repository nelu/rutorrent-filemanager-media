
plugin.viewsPath = plugin.path + 'views/';
plugin.streamEndpoint = $type(plugin.config.streampath) && plugin.config.streampath !== ''
    ? plugin.config.streampath
    : window.location.href.replace(/[\/?#]+$/, '') + '/' + plugin.path + 'view.php';

plugin.onLangLoaded = () => {
    Promise.all([thePlugins.get('filemanager').loaded()])
        .then(() => import('./' + plugin.path + 'js/media.js'))
        .then((m) => {
            flm.media = new m.FileManagerMedia(plugin, flm);
            flm.media.init();
            plugin.markLoaded();
        });
};

plugin.onRemove = function () {
    flm.media.stop();
    $('[id^="flm_popup_media"], [id^="flm-media"]').remove();
}

plugin.onTaskFinished = (task) => flm.triggerEvent('taskDone', [task]);

if (plugin.enabled) {
    plugin.loadLang();
    plugin.loadCSS('css/media');
}

