@php $groupId = 'group-' . md5($groupName); @endphp
<div class="mb-6 album-group" id="{{ $groupId }}" data-tags="{{ json_encode($group['tags']) }}" data-archived="{{ $group['archived'] ? '1' : '0' }}">
    <button onclick="toggleGroup(this)"
            class="w-full cursor-pointer select-none flex items-center gap-3 py-3 border-b border-gray-200 text-left">
        @if($group['cover'])
        <img src="{{ $group['cover'] }}" class="w-8 h-8 rounded object-cover mr-2" alt="{{ $groupName }}" />
        @endif
        <span class="text-lg text-slate-800">{{ $groupName }}</span>
        <svg class="w-4 h-4 ml-auto text-gray-400 group-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div class="group-content">
        <div>
            {{-- Sub Tabs: 年度 / 其他 --}}
            @if($group['yearly']->isNotEmpty() && $group['other']->isNotEmpty())
            <div class="flex gap-4 mt-3 mb-1">
                <button onclick="switchSubTab(this, '{{ $groupId }}', 'other')"
                        class="sub-tab active text-xs font-medium pb-1 border-b-2 border-transparent transition-colors">
                    特辑 ({{ $group['other']->count() }})
                </button>
                <button onclick="switchSubTab(this, '{{ $groupId }}', 'yearly')"
                        class="sub-tab text-xs font-medium pb-1 border-b-2 border-transparent text-gray-500 transition-colors">
                    年度专辑 ({{ $group['yearly']->count() }})
                </button>
            </div>
            @endif

            {{-- 其他专辑 --}}
            @if($group['other']->isNotEmpty())
            <div class="sub-panel sub-panel-other active">
                <div class="flex flex-wrap gap-4 mt-3">
                    @each('album._card', $group['other'], 'album')
                </div>
            </div>
            @endif

            {{-- 年度专辑 --}}
            @if($group['yearly']->isNotEmpty())
            <div class="sub-panel sub-panel-yearly {{ $group['other']->isEmpty() ? 'active' : '' }}">
                <div class="flex flex-wrap gap-4 mt-3">
                    @each('album._card', $group['yearly'], 'album')
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
