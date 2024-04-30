plugin = plugin || {}; // shut up

plugin.loadLang();


(function flmMedia(window) {

    var media = {
        stp: 'plugins/mediastream/view.php',
        api: null
    };

    media.onTaskDone = function (task) {
        task.params && task.params.workdir === flm.getCurrentPath()
        && flm.Refresh();
    };

    media.play = function (target) {

        var ext = flm.utils.getExt(target);

        flm.ui.dialogs.forms['flm-media-player'].options.isImage = ext.match(new RegExp('^(' + plugin.config.allowedFormats.image + ')$', "i"));
        flm.ui.dialogs.showDialog('flm-media-player',
            {
                afterHide: function () {
                    media.stop();
                }
            });
    };

    media.getVideoPlayer = function () {
        var diagId = flm.ui.getDialogs().getDialogId('flm-media-player');
        return $(diagId).find('video');
    };

    media.stop = function () {

        var player = media.getVideoPlayer();
        player.length > 0 && player[0].pause();
    };

    media.doScreenshots = function (sourceFile, screenShotFileName) {

        return this.api.post({
            workdir: flm.getCurrentPath(),
            method: 'createFileScreenshots',
            target: sourceFile,
            to: screenShotFileName
        }).then(function (value) {
            //flm.manager.logAction(theUILang["flm_popup_media-screenshots"], theUILang.flm_media_start_screenshots);
        });

    };

    media.doScreensheet = function (sourceFile, screenShotFileName, config) {
        var def = $.Deferred()
        theWebUI.startConsoleTask("screensheet", plugin.name,
            {
                workdir: flm.getCurrentPath(),
                method: 'createFileScreenSheet',
                target: sourceFile,
                to: screenShotFileName,
                settings: config
            },
            {noclose: true});

        def.resolve(theWebUI.getConsoleTask());
        return def.promise();
    };

    media.setDialogs = function (flmDialogs) {

        var viewsPath = plugin.path + 'views/';
        var endpoint = $type(plugin.config.public_endpoint) && plugin.config.public_endpoint !== ""
            ? plugin.config.public_endpoint
            : flm.utils.rtrim(window.location.href, '/') + '/' + plugin.path + 'view.php';

        flm.views.namespaces['flm-media'] = viewsPath;

        flmDialogs.forms['flm-media-player'] = {
            options: {
                public_endpoint: endpoint,
                views: "flm-media"
            },
            modal: false,
            template: viewsPath + "dialog-media-player"
        };

        flmDialogs.forms['media-screenshots'] = {
            options: {
                public_endpoint: endpoint,
                views: "flm-media"
            },
            modal: true,
            pathbrowse: true,
            template: viewsPath + "dialog-screenshots"
        };
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
                        menu[createPos][2].push([theUILang['flm_popup_media-screenshots'], (
                            thePlugins.isInstalled('screenshots')
                            && !flm.utils.isDir(path)
                            && flm.utils.getExt(path).match(new RegExp("^(" + thePlugins.get('screenshots').extensions.join('|') + ")$", "i"))
                        )

                            ? function () {
                                flm.ui.getDialogs().showDialog('media-screenshots');
                            }
                            : null]);
                    }

                }
            }

        }

    };

    media.init = function () {
        window.flm.ui.browser.onSetEntryMenu(media.setMenuEntries);
        media.setDialogs(flm.ui.getDialogs());
    };

    //onSetEntryMenu
    thePlugins.get('filemanager').ui.readyPromise
        .then(
            function (flmUi) {
                media.init();

                window.flm.media = media;
            },
            function (reason) {

            }
        );

})(window);


plugin.onLangLoaded = function () {
    plugin.markLoaded();

};

plugin.onTaskFinished = function (task, onBackground) {
    window.flm.media.onTaskDone(task)

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

plugin.loadCSS('media');
