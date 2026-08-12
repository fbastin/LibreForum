function mod_embed_images_initviewer(container, image, link, url, message_id)
{
    var imgpath = LibreForum.http_path +
                  '/mods/embed_images/viewers/jquery_lightbox/code/images/';

    var settings = {
        imageLoading  : imgpath + 'lightbox-ico-loading.png',
        imageBtnPrev  : imgpath + 'lightbox-btn-prev.png',
        imageBtnNext  : imgpath + 'lightbox-btn-next.png',
        imageBtnClose : imgpath + 'lightbox-btn-close.png',
        imageBlank    : imgpath + 'lightbox-blank.png',
    };

    var a = document.createElement('a');
    a.href = url;
    container.appendChild(a);
    a.appendChild(image);
    $PJ(a).lightBox(settings);

    if (link) {
        $PJ(link).lightBox(settings);
    }
}

