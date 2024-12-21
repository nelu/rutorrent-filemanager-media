plugin = plugin || {}; // shut up
plugin.flmMedia = function () {

    let config = plugin.config;
    let dialogs = flm.ui.getDialogs();

    this.EVENT_PLAY = 'flm-media:playfile';
    this.endpoint = $type(config.public_endpoint) && config.public_endpoint !== ""
        ? config.public_endpoint
        : flm.utils.rtrim(window.location.href, '/') + '/' + plugin.path + 'view.php';
    this.api = null;
    this.destinationPath = null;
    this.onTaskDone = $.Deferred();

    let media = this;

    this.play = function (target) {
        let mediaEntry = $type(target) === "string" ? this.mediaEntryFrom(target) : target;
        let contentTypePlayer = mediaEntry.isImage ? $(".flm-media-image-viewer") : this.getVideoPlayer();

        let dialogConfig = this.showPlayer(mediaEntry);

        contentTypePlayer.length
        && $(`#${dialogConfig.diagWindow}`).trigger(this.EVENT_PLAY, [mediaEntry]);
    };

    this.onPlayMedia = (diagWindow, call) => {

        // see how to implement unsubscribes / off ()
        // use dialog window as it being removed and its subscribers ?
        $(`#${diagWindow}`).on(this.EVENT_PLAY, call);
    }

    media.getVideoPlayer = function (type = "video") {
        return $(dialogs.getDialogId('media-player') + " video");
    };

    this.mediaEntryFrom = (mediaFile) => {
        const ext = flm.utils.getExt(mediaFile);
        const mediaType = flm.utils.getFileTypeByExtension(ext);

        return {
            file: mediaFile,
            url: flm.utils.buildPath([this.endpoint, mediaFile]),
            type: mediaType,
            isImage: (mediaType === "image"),
            isAudio: (mediaType === "audio"),
            isVideo: (mediaType === "video")
        };
    }

    media.showPlayer = (mediaEntry) => {
        const what = mediaEntry.isImage ? 'media-image-view' : 'media-player';
        let diagConf = dialogs.getDialogConfig(what);

        diagConf.options = {
            mediaEntry: mediaEntry,
            plugin: plugin
        };

        let player = media.getVideoPlayer();

        if(mediaEntry.isImage || !(player.length > 0) || !player.data.inPipMode)
        {
            dialogs.setDialogConfig(what, diagConf)
                .showDialog(what, {beforeHide: () => !mediaEntry.isImage && media.stop()});
        }

        return diagConf;
    }

    media.stop = function () {
        var player = media.getVideoPlayer();
        //console.log('media.stop player', player, player.data.inPipMode);
        player.length > 0 && player[0].pause();
        if (player.data.inPipMode) {
            document.exitPictureInPicture();
        }
    };

    media.doScreenshots = function (sourceFile, screenShotFileName) {

        return this.api.post({
            workdir: flm.getCurrentPath(), method: 'createFileScreenshots', target: sourceFile, to: screenShotFileName
        }).then(function (value) {
            //flm.manager.logAction(theUILang["flm_popup_media-screenshots"], theUILang.flm_media_start_screenshots);
        });

    };

    media.doScreensheet = function (sourceFile, screenshotFile, config) {
        media.onTaskDone = $.Deferred()
        screenshotFile = flm.stripJailPath(screenshotFile);
        flm.media.destinationPath = screenshotFile;

        if ('sheet' === 'sheet') {
            theWebUI.startConsoleTask("screensheet", plugin.name, {
                workdir: flm.getCurrentPath(),
                method: 'createFileScreenSheet',
                target: sourceFile,
                to: screenshotFile,
                settings: config
            }, {noclose: true});
        } else {
            flm.media.doScreenshots(sourceFile, screenshotFile)
        }

        return media.onTaskDone.promise().then(function (task) {
            flm.actions.refreshIfCurrentPath(flm.utils.basedir(screenshotFile));
            return task;
        });
    };

    media.setDialogs = function (flmDialogs) {

        var viewsPath = plugin.path + 'views/';

        flm.views.namespaces['flm-media'] = viewsPath;

        flm.ui.dialogs.setDialogConfig('media-player', {
            options: {
                public_endpoint: media.endpoint,
                views: "flm-media"
            },
            modal: false,
            persist: true,
            pathbrowse: true,
            pathbrowseFiles: true,
            template: viewsPath + "dialog-media-player"
        })
            .setDialogConfig('media-image-view', {
                options: {
                    public_endpoint: media.endpoint,
                    views: "flm-media"
                },
                modal: false,
                persist: true,
                pathbrowse: true,
                pathbrowseFiles: true,
                template: viewsPath + "dialog-image-view"
            })
            .setDialogConfig('media-screenshots', {
                options: {
                    public_endpoint: media.endpoint,
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
        // media icons
        for (let f in config.allowedFormats) {
            flm.utils.extTypes[f] = (ext) => ext.match(new RegExp('^(' + config.allowedFormats[f] + ')$', "i"));
        }

        flm.ui.filenav.onSetEntryMenu(media.setMenuEntries);
        media.setDialogs(flm.ui.getDialogs());

    };

    return media;
}


plugin.onLangLoaded = function () {
    //onSetEntryMenu
    thePlugins.get('filemanager').ready()
        .then(function (r) {
            flm.media = plugin.flmMedia();
            flm.media.init();
            plugin.markLoaded();
            return r;
        }, function (reason) {
            console.error("filemanager-media: base plugin failed to load", reason);
            return reason;
        });
};

plugin.onTaskFinished = function (task, onBackground) {
    flm.media.onTaskDone.resolve(task);
    let destination = flm.media.destinationPath;

    const text = `${theUILang.flm_media_screens_file}: <a href="javascript: flm.showPath('${flm.utils.basedir(destination)}',`
        + `'${flm.utils.basename(destination)}')">${destination}</a>`;

    flm.actions.notify(text, 'success');

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
plugin.loadCSS('css/media');
