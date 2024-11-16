<ul data-media-library-component-name="{{ $this->name }}" wire:key="ul-{{ $this->name }}"
    class="{{ $sortable ? 'media-library-sortable-container' : '' }} {{ count($sortedMedia) == 0 ? 'media-library-hidden' : 'media-library-items' }}">
    @foreach($sortedMedia as $mediaItem)
        @include($itemView)
    @endforeach
</ul>

<script>
    function moveLivewireEndMarker(el) {
        const endMorphMarker = Array.from(el.childNodes).filter((childNode) => {
            return childNode.nodeType === 8 && ['[if ENDBLOCK]><![endif]', '__ENDBLOCK__'].includes(childNode.nodeValue?.trim());
        })[0];

        if (endMorphMarker) {
            el.appendChild(endMorphMarker);
        }
    }

    document.addEventListener('media-library-sorted-' + '{{ $this->name }}', (e) => {
        moveLivewireEndMarker(e.detail.el);

        const source = document.querySelector('[data-media-library-component-name="{{ $this->name }}"]');

        const newOrder = [];

        source.querySelectorAll('[data-uuid]').forEach((el, i) =>  newOrder.push({uuid: el.value, order: i}));

        @this.setNewOrder(newOrder);
    })
</script>
