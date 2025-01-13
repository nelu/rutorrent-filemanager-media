function MediaEntry(mediaFile) {

    const ext = flm.utils.getExt(mediaFile);
    const mediaType = flm.utils.getFileTypeByExtension(ext);

    this.file = mediaFile;
    this.type = mediaType;
    this.url = [flm.media.streamEndpoint, mediaFile].join('/');
    this.isImage = (mediaType === "image");
    this.isAudio = (mediaType === "audio");
    this.isVideo = (mediaType === "video");

    return this;
}

export function FileManagerMedia(plugin, fm) {

    let config = plugin.config;
    let self = this;

    this.streamEndpoint = plugin.streamEndpoint;
    this.EVENT_PLAY = 'flm.media.play';
    this.api = null;
    this.destinationPath = null;
    this.onTaskDone = $.Deferred();

    let dialogs = flm.ui.getDialogs();

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

    this.getVideoPlayer = function (type = "video") {
        return $(dialogs.getDialogId('media_player') + " video");
    };

    this.mediaEntryFrom = (mediaFile) => new MediaEntry(mediaFile);

    self.showPlayer = (mediaEntry) => {
        const what = mediaEntry.isImage ? 'media_image_view' : 'media_player';
        let diagConf = dialogs.getDialogConfig(what);

        diagConf.options = {
            mediaEntry: mediaEntry,
            plugin: plugin
        };

        let player = self.getVideoPlayer();

        if (mediaEntry.isImage || !(player.length > 0) || !player.data.inPipMode) {
            dialogs.setDialogConfig(what, diagConf)
                .showDialog(what, {beforeHide: () => !mediaEntry.isImage && self.stop()});
        }

        return diagConf;
    }

    this.stop = function () {
        var player = self.getVideoPlayer();
        //console.log('self.stop player', player, player.data.inPipMode);
        player.length > 0 && player[0].pause();
        if (player.data.inPipMode) {
            document.exitPictureInPicture();
        }
    };

    this.doScreensheet = function (videoFile, screenshotFile, config) {
        let sourceFile = videoFile.val();

        screenshotFile = screenshotFile.val();
        flm.media.destinationPath = screenshotFile;

        //flm.manager.logAction(theUILang["flm_popup_media_screenshots"], theUILang.flm_media_start_screenshots);

        return flm.api.runTask('screensheet', {
            method: ('sheet' === 'sheet') ? 'createFileScreenSheet' : 'createFileScreenshots',
            workdir: flm.getCurrentPath(),
            target: sourceFile,
            to: screenshotFile,
            settings: config
        }, plugin.name)
            .then((t) => {
                flm.Refresh(flm.utils.basedir(screenshotFile))
                    .done(() => flm.showInTable(screenshotFile));
                return {
                    notify: [`${theUILang.flm_media_screens_file}: ${screenshotFile} <- ${sourceFile}`],
                }
            });

    };

    this.setDialogs = function (dialogs) {
        const vp = plugin.viewsPath;
        const opts = {
            streamEndpoint: plugin.streamEndpoint,
            views: "flm-media"
        };

        flm.views.namespaces['flm-media'] = vp;

        dialogs.setDialogConfig('media_player', {
            options: opts,
            modal: false,
            persist: true,
            pathbrowse: true,
            pathbrowseFiles: true,
            template: vp + "dialog-media-player"
        }).setDialogConfig('media_image_view', {
            options: opts,
            modal: false,
            persist: true,
            pathbrowse: true,
            pathbrowseFiles: true,
            template: vp + "dialog-image-view"
        }).setDialogConfig('media_screenshots', {
            options: opts,
            modal: true,
            pathbrowse: true,
            pathbrowseFiles: true,
            template: vp + "dialog-screenshots"
        });

    };

    this.isMediaFile = (file) => {
        return flm.utils.fileMatches(file, config.allowedViewFormats);
    }

    this.isMediaFormat = (file, format) => flm.utils.fileMatches(file, config.allowedFormats[format]);

    this.setMenuEntries = function (menu, path) {

        if (plugin.enabled && self.isMediaFile(path)) {
            flm.ui.addContextMenu(menu, [theUILang.fView, () => self.play(path)], theUILang.fOpen);
            flm.ui.addContextMenu(menu, [CMENU_SEP], theUILang.fView);

            if (!flm.utils.isDir(path) && self.isMediaFormat(flm.utils.getExt(path), 'video')) {
                let createPos = flm.ui.getContextMenuEntryPosition(menu, theUILang.fcreate, 1);
                flm.ui.addContextMenu(menu[createPos][2], [theUILang.flm_popup_media_screenshots, () => self.showCreate()]);
            }
        }

    };

    this.showCreate = function () {
        return flm.ui.dialogs.showDialog('media_screenshots');
    }

    this.init = function () {
        // self icons
        for (let f in config.allowedFormats) {
            flm.utils.extTypes[f] = (ext) => self.isMediaFormat(ext, f);
        }

        flm.ui.filenav.onContextMenu(self.setMenuEntries);
        self.setDialogs(flm.ui.dialogs);

    };

    return this;
}