<a href="{{ route('album.show', $album->hashId) }}"
   class="group block rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow duration-200"
   style="width: 160px;">
    <div class="bg-gray-100 overflow-hidden" style="width: 160px; height: 160px;">
        {{-- 封面：cover_url = 上传的 avatar → 归属节目 cover（片段型专辑由 resolvedTarget() 从收录的片段反查） --}}
        @if($album->cover_url)
        <img src="{{ $album->cover_url }}" alt="{{ $album->name }}"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" />
        @else
        <div class="w-full h-full flex items-center justify-center text-gray-300">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
        </div>
        @endif
    </div>
    <div class="p-3">
        <h3 class="text-sm font-semibold text-slate-900 truncate group-hover:text-orange-600 transition-colors">
            {{ $album->name }}
        </h3>
        @unless($album->published_at)
        <span class="inline-block mt-1 px-1.5 py-0.5 text-xs font-medium text-orange-600 bg-orange-50 rounded">未发布</span>
        @endunless
        @if($album->description)
        <p class="mt-1 text-xs text-gray-500 line-clamp-2 leading-relaxed">{{ $album->description }}</p>
        @endif
    </div>
</a>
