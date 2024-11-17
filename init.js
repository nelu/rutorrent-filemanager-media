plugin = plugin || {}; // shut up
plugin.flmMedia = function () {

    let media = this;
    media.stp = 'plugins/mediastream/view.php';
    media.api = null;
    media.destinationPath =  null;
    media.onTaskDone =  $.Deferred();

    media.play = function (target) {
        const ext = flm.utils.getExt(target);
        const isImage = ext.match(new RegExp('^(' + plugin.config.allowedFormats.image + ')$', "i"));
        flm.ui.dialogs.showDialog(isImage ? 'media-image-view' : 'media-player',
            {afterHide: () => !isImage && media.stop()});
    };

    media.getVideoPlayer = function () {
        var diagId = flm.ui.getDialogs().getDialogId('media-player');
        return $(diagId + " video");
    };

    media.stop = function () {

        var player = media.getVideoPlayer();
        player.length > 0 && player[0].pause();
    };

    media.doScreenshots = function (sourceFile, screenShotFileName) {

        return this.api.post({
            workdir: flm.getCurrentPath(), method: 'createFileScreenshots', target: sourceFile, to: screenShotFileName
        }).then(function (value) {
            //flm.manager.logAction(theUILang["flm_popup_media-screenshots"], theUILang.flm_media_start_screenshots);
        });

    };

    media.doScreensheet = function (sourceFile, screenShotFileName, config) {
        media.onTaskDone = $.Deferred()
        theWebUI.startConsoleTask("screensheet", plugin.name, {
            workdir: flm.getCurrentPath(),
            method: 'createFileScreenSheet',
            target: sourceFile,
            to: screenShotFileName,
            settings: config
        }, {noclose: true});

        return media.onTaskDone.promise();
    };

    media.setDialogs = function (flmDialogs) {

        var viewsPath = plugin.path + 'views/';
        var endpoint = $type(plugin.config.public_endpoint) && plugin.config.public_endpoint !== "" ? plugin.config.public_endpoint : flm.utils.rtrim(window.location.href, '/') + '/' + plugin.path + 'view.php';

        flm.views.namespaces['flm-media'] = viewsPath;

        flm.ui.dialogs.setDialogConfig('media-player', {
                options: {
                    public_endpoint: endpoint,
                    views: "flm-media"
                },
                modal: false,
                template: viewsPath + "dialog-media-player"
            })
            .setDialogConfig('media-image-view', {
                options: {
                    public_endpoint: endpoint,
                    views: "flm-media"
                },
                modal: false,
                template: viewsPath + "dialog-image-view"
            })
            .setDialogConfig('media-screenshots', {
                options: {
                    public_endpoint: endpoint,
                    views: "flm-media"
                },
                modal: true,
                pathbrowse: true,
                template: viewsPath + "dialog-screenshots"
            });

    };

    media.setMenuEntries = function (menu, path) {

        if (plugin.enabled) {

            var ext = flm.utils.getExt(path);

            var re = new RegExp('^(' + plugin.config.allowedViewFormats + ')$', "i");

            if (ext.match(re)) {

                var openPos = thePlugins.get('filemanager').ui.getContextMenuEntryPosition(menu, theUILang.fOpen);

                if (openPos > -1) {
                    menu.splice(++openPos, 0, [theUILang.fView, function () {
                        media.play(path);
                    }]);
                    menu.splice(++openPos, 0, [CMENU_SEP]);
                }

                var videoRe = new RegExp('^(' + plugin.config.allowedFormats.video + ')$', "i");

                if (ext.match(videoRe)) {
                    var createPos = thePlugins.get('filemanager').ui.getContextMenuEntryPosition(menu, theUILang.fcreate, 1);

                    if (createPos > -1) {
                        menu[createPos][2].push([theUILang['flm_popup_media-screenshots'],
                            (thePlugins.isInstalled('screenshots')
                                && !flm.utils.isDir(path)
                                && flm.utils.getExt(path)
                                    .match(new RegExp("^(" + thePlugins.get('screenshots').extensions.join('|') + ")$", "i")))

                                ? function () {
                                    flm.ui.getDialogs().showDialog('media-screenshots');
                                } : null]);
                    }

                }
            }

        }

    };

    media.init = function () {
        window.flm.ui.filenav.onSetEntryMenu(media.setMenuEntries);
        media.setDialogs(flm.ui.getDialogs());
    };

    return media;
}


plugin.onLangLoaded = function () {
    //onSetEntryMenu
    thePlugins.get('filemanager').ui.readyPromise
        .then(function () {
            flm.media = plugin.flmMedia();
            flm.media.init();
            plugin.markLoaded();
        }, function (reason) {
            console.error("filemanager-media: base plugin failed to load", reason);
        });
};

plugin.onTaskFinished = function (task, onBackground) {
    flm.media.onTaskDone.resolve(task);
    let destination = flm.media.destinationPath;
    plugin.noty = $.noty({
        text: theUILang.flm_media_screens_file
            + ': <a href="javascript: flm.showPath(\'' + flm.utils.basedir(destination) + '\', \''
            + flm.utils.basename(destination) + '\')">' + destination + '</a>',
        layout: 'bottomLeft',
        type: 'success',
        timeout: 10000,
        closeOnSelfClick: true
    });

    if (task.errors === 0) {
        // log the request error as task errors
        task.status = 1;
        task.errors = [($type(theUILang.fErrMsg[task.errcode])
            ? theUILang.fErrMsg[task.errcode] + " -> " + task.msg
            : task.msg)];
        delete task.errcode;
        thePlugins.get("_task").check(task);
    }
};

/*plugin.onRemove = function() {
	theWebUI.VPLAY.stop();
	$('#VPLAY_diag').remove();
}*/
plugin.loadLang();
plugin.loadCSS('media');
