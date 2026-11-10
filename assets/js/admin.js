jQuery(function($){
    let galleryFrame;
    let agentFrame;

    function galleryIds(){
        return $('#gip-gallery-preview .gip-gallery-item').map(function(){
            return $(this).data('id');
        }).get();
    }

    function updateGalleryField(){
        $('#gip_gallery').val(galleryIds().join(','));
    }

    $('.gip-select-gallery').on('click', function(e){
        e.preventDefault();

        galleryFrame = wp.media({
            title: 'Seleccionar imágenes de la propiedad',
            button: { text: 'Añadir a la galería' },
            multiple: true,
            library: { type: 'image' }
        });

        galleryFrame.on('select', function(){
            const selection = galleryFrame.state().get('selection').toJSON();
            const existing = galleryIds().map(String);

            selection.forEach(function(item){
                if(existing.includes(String(item.id))) return;
                const thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
                $('#gip-gallery-preview').append(
                    '<span class="gip-gallery-item" data-id="'+item.id+'">'+
                    '<img src="'+thumb+'" alt="">'+
                    '<button type="button" class="gip-remove-gallery" title="Eliminar">×</button>'+
                    '</span>'
                );
            });

            updateGalleryField();
        });

        galleryFrame.open();
    });

    $(document).on('click', '.gip-remove-gallery', function(){
        $(this).closest('.gip-gallery-item').remove();
        updateGalleryField();
    });

    $('.gip-clear-gallery').on('click', function(){
        $('#gip-gallery-preview').empty();
        updateGalleryField();
    });

    $('.gip-select-agent-photo').on('click', function(e){
        e.preventDefault();

        agentFrame = wp.media({
            title: 'Seleccionar foto del agente',
            button: { text: 'Usar esta foto' },
            multiple: false,
            library: { type: 'image' }
        });

        agentFrame.on('select', function(){
            const item = agentFrame.state().get('selection').first().toJSON();
            const thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;

            $('#gip_agent_photo_id').val(item.id);
            $('#gip-agent-photo-preview').html('<img src="'+thumb+'" alt="">');
        });

        agentFrame.open();
    });
});