$('.fMan_Start').click(function () {

    var diagid = $(this).parents('.dlg-window:first').attr('id');
    diagid = diagid.split('fMan_');

    switch (diagid[1]) {

        case 'Screenshots':
            theWebUI.fManager.doScreenshots(this, diagid[1]);
            break;
    }

});