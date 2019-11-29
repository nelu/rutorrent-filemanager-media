plugin = plugin || {}; // shut up

plugin.loadLang();


(function flmMedia(window) {

	var media = {
		stp:  'plugins/mediastream/view.php'
	};

	media.play = function(target) {

		flm.ui.getDialogs().showDialog('flm-media-player',
			{
				afterHide: function () {
					media.stop();
				}
			});
	};

	media.getVideoPlayer = function()
	{		var diagId = flm.ui.getDialogs().getDialogId('flm-media-player');

		return $(diagId).find('video');
	};
	
	media.stop= function() {

		var player = media.getVideoPlayer();
		player.length > 0 && player[0].pause();
	};

	media.createScreenshots= function (target) {

		if (!(theWebUI.fManager.actiontoken.length > 1)) {

			$('#fMan_Screenshotslist').html(flm.currentPath + '<strong>' + target + '</strong>');
			$('#fMan_Screenshotsbpath').val(this.homedir + flm.currentPath + 'screens_' + this.recname(target) + '.png');
			$('#fMan_Screenshots .fMan_Start').attr('disabled', false);
		}

		this.makeVisbile('fMan_Screenshots');
	};
	media.doScreenshots = function (button, diag) {

		var screen_file = this.checkInputs(diag);
		if (screen_file === false) {
			return false;
		}

		var video = $('#fMan_Screenshotslist').text();

		$(button).attr('disabled', true);

		this.actStart(diag);


	};

	media.setDialogs = function(flmDialogs) {

		var viewsPath = plugin.path + 'views/';

		flm.views.namespaces['flm-media'] = viewsPath;

		flmDialogs.forms['flm-media-player'] = {
				options: {
					public_endpoint: plugin.config.public_endpoint,
					views: "flm-media"
				},
				modal: false,
				template: viewsPath + "dialog-media-player"
		};

		flmDialogs.forms['media-screenshots'] = {
			options: {
				public_endpoint: plugin.config.public_endpoint,
				views: "flm-media"
			},
			modal: true,
			pathbrowse: true,
			template: viewsPath + "dialog-screenshots"
		};
	};

	media.setMenuEntries = function (menu, path) {

		var pathIsDir = flm.utils.isDir(path);

		if(plugin.enabled) {

			var ext = flm.utils.getExt(path);

			if(ext.match(/^(mp[34]|avi|divx|mkv|png|jpeg|gif)$/i)) {

				var el = theContextMenu.get(theUILang.fOpen);
				if(el)
				{
					menu.add(el,[CMENU_SEP]);
					menu.add(el,[theUILang.fView, function() {media.play(path);}]);
					menu.add(el,[CMENU_SEP]);
				}

				if(ext.match(/^(mp4|avi|divx|mkv)$/i)) {
					var sub = theContextMenu.get(theUILang.fcreate).children().last();
					if(sub)
					{
						menu.add(sub,[theUILang['flm_popup_media-screenshots'], /*(
							thePlugins.isInstalled('screenshots')
							&& !pathIsDir
							&& flm.utils.getExt(path).match(new RegExp("^(" + thePlugins.get('screenshots').extensions.join('|') + ")$", "i"))
						) */
						true
							? function () {
								console.log('need screenshots?');
								flm.ui.getDialogs().showDialog('media-screenshots');
							}
							: null]);
					}
				}
			}

		}

	};

	window.flm.api.createScreensheet = function(source, destination, options) {
		return window.flm.api.post({
			method: 'fileScreenSheet',
			options: options,
			target: source,
			to: destination
		});
	};

	//onSetEntryMenu
	thePlugins.get('filemanager').ui.readyPromise
		.then(
			function (flmUi) {
				window.flm.ui.browser.onSetEntryMenu(media.setMenuEntries);
				media.setDialogs(flm.ui.getDialogs());

				console.log(plugin.config);
				window.flm.media = media;
			},
			function (reason) {

			}
		);

})(window);



plugin.onLangLoaded = function() {
	plugin.markLoaded();

};

/*plugin.onRemove = function() {
	theWebUI.VPLAY.stop();
	$('#VPLAY_diag').remove();
}*/

plugin.loadCSS('media');
