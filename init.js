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
/*
		theWebUI.fManager.action.request('action=sess',
			function (data) {
				if(theWebUI.fManager.isErr(data.errcode)) {log('Play failed'); return false;}
				theWebUI.fManager.makeVisbile('VPLAY_diag');
				try {
					theWebUI.VPLAY.player.Open(theWebUI.VPLAY.stp+'?ses='+encodeURIComponent(data.sess)+'&action=view&dir='+encodeURIComponent(theWebUI.fManager.curpath)+'&target='+encodeURIComponent(target));
				} catch(err) { }
			});*/
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

		this.action.request('action=scrn&target=' + encodeURIComponent(video) + '&to=' + encodeURIComponent(screen_file));


		var actioncall = {
			method: 'fileScreenSheet',
			target: video,
			to: screen_file
		};


		this.action.postRequest({action: flm.utils.json_encode(actioncall)});


	};

	media.setDialogs = function(flmDialogs) {

		flm.views.namespaces['flm-media'] = plugin.path + 'views/';

		flmDialogs.forms['flm-media-player'] = {
				options: {
					public_endpoint: plugin.config.public_endpoint,
					views: "flm-media"
				},
				modal: false,
				template: plugin.path + "views/dialog-media-player"
		};
	};

	media.setMenuEntries = function (menu, path) {

		var pathIsDir = flm.utils.isDir(path);

		if(plugin.enabled) {

			var el = theContextMenu.get(theUILang.fOpen);
			if(el && flm.utils.getExt(path).match(/^(mp[34]|avi|divx|mkv|png|jpeg|gif)$/i)) {

				menu.add(el,[CMENU_SEP]);
				menu.add(el,[theUILang.fView, function() {media.play(path);}]);
				menu.add(el,[CMENU_SEP]);


				var sub = theContextMenu.get(theUILang.fcreate).children().last();
				menu.add(sub,[theUILang.fcScreens, (
					thePlugins.isInstalled('screenshots')
					&& !pathIsDir
					&& flm.utils.getExt(path).match(new RegExp("^(" + thePlugins.get('screenshots').extensions.join('|') + ")$", "i"))
				) ? function () {
						console.log('need screenshots?');
					}
					: null]);


			}
		}

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

	/*
        injectScript('plugins/mediastream/settings.js.php');
    */

/*

	theWebUI.VPLAY.player =  document.getElementById('np_plugin');
	theDialogManager.setHandler('VPLAY_diag','afterHide', "theWebUI.VPLAY.stop()");*/

	plugin.markLoaded();

};

/*plugin.onRemove = function() {
	theWebUI.VPLAY.stop();
	$('#VPLAY_diag').remove();
}*/

plugin.loadCSS('media');
