<x-player-layout title="专辑列表">
    <nav class="border-gray-200 bg-gray-50 dark:bg-gray-100 dark:border-gray-50">
      <div class="max-w-screen-xl flex flex-wrap items-center justify-between m-8 py-4" style="margin-top: 0;">
        <a href="/" class="">
            <img src="{{asset('logo.png')}}" class="h-10" alt="LY Logo" />
        </a>
      </div>
    </nav>

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 mb-6">专辑列表</h1>

        {{-- 分类标签过滤 --}}
        <div class="mb-6">
            <div class="text-sm text-gray-600 mb-2">分类筛选：</div>
            <div class="flex flex-wrap gap-2">
                @foreach($lyTags as $tag)
                <button onclick="toggleTagFilter('{{ $tag->name }}')"
                        data-tag="{{ $tag->name }}"
                        class="tag-filter px-3 py-1.5 text-sm rounded-full border transition-all duration-200
                               border-gray-300 text-gray-700 bg-white hover:border-orange-400 hover:text-orange-600">
                    {{ $tag->name }}
                </button>
                @endforeach
                <button onclick="toggleTagFilter('__archived__')"
                        data-tag="__archived__"
                        class="tag-filter px-3 py-1.5 text-sm rounded-full border transition-all duration-200
                               border-gray-300 text-gray-700 bg-white hover:border-orange-400 hover:text-orange-600">
                    停播节目
                </button>
            </div>
        </div>

        {{-- 所有节目 --}}
        <div id="panel-all">
            @forelse($allGroups as $groupName => $group)
                @include('album._group', [
                    'groupName' => $groupName . ($group['archived'] ? '（已停播）' : ''),
                    'group' => $group,
                ])
            @empty
                @include('album._empty')
            @endforelse
        </div>
    </main>

    @push('scripts')
    <style>
        .group-content {
            display: grid;
            grid-template-rows: 1fr;
            opacity: 1;
            transition: grid-template-rows 0.3s ease, opacity 0.3s ease;
        }
        .group-content > div {
            overflow: hidden;
        }
        .group-content.collapsed {
            grid-template-rows: 0fr;
            opacity: 0;
        }
        .group-content.collapsed > div {
            margin-top: 0;
        }
        .group-arrow {
            transition: transform 0.3s ease;
        }
        .group-arrow.collapsed {
            transform: rotate(-90deg);
        }
        .sub-tab.active {
            color: #ea580c;
            border-bottom-color: #ea580c;
        }
        .sub-panel {
            display: none;
        }
        .sub-panel.active {
            display: block;
        }
        .tag-filter.active {
            background-color: #ea580c;
            border-color: #ea580c;
            color: white;
        }
        .tag-filter.active:hover {
            background-color: #c2410c;
            border-color: #c2410c;
            color: white;
        }
        .album-group.filtered-hidden {
            display: none;
        }
    </style>
    <script>
        var activeTagFilter = null;

        function toggleGroup(btn) {
            var content = btn.nextElementSibling;
            var arrow = btn.querySelector('.group-arrow');
            content.classList.toggle('collapsed');
            arrow.classList.toggle('collapsed');
        }

        function switchSubTab(btn, groupId, subTab) {
            var group = document.getElementById(groupId);
            group.querySelectorAll('.sub-tab').forEach(function(el) {
                el.classList.remove('active');
            });
            btn.classList.add('active');
            group.querySelectorAll('.sub-panel').forEach(function(el) {
                el.classList.remove('active');
            });
            group.querySelector('.sub-panel-' + subTab).classList.add('active');
        }

        function toggleTagFilter(tagName) {
            document.querySelectorAll('.tag-filter').forEach(function(el) {
                el.classList.remove('active');
            });

            if (activeTagFilter === tagName) {
                activeTagFilter = null;
                applyTagFilter();
                return;
            }

            activeTagFilter = tagName;
            document.querySelector('.tag-filter[data-tag="' + tagName + '"]').classList.add('active');
            applyTagFilter();
        }

        function applyTagFilter() {
            var allGroups = document.querySelectorAll('.album-group');

            if (!activeTagFilter) {
                allGroups.forEach(function(group) {
                    group.classList.remove('filtered-hidden');
                });
                return;
            }

            // "停播节目" fake tag：按 data-archived 属性筛选
            if (activeTagFilter === '__archived__') {
                allGroups.forEach(function(group) {
                    if (group.getAttribute('data-archived') === '1') {
                        group.classList.remove('filtered-hidden');
                    } else {
                        group.classList.add('filtered-hidden');
                    }
                });
                return;
            }

            // 普通标签过滤
            allGroups.forEach(function(group) {
                var tags = JSON.parse(group.getAttribute('data-tags') || '[]');
                if (tags.includes(activeTagFilter)) {
                    group.classList.remove('filtered-hidden');
                } else {
                    group.classList.add('filtered-hidden');
                }
            });
        }
    </script>
    @endpush
</x-player-layout>
